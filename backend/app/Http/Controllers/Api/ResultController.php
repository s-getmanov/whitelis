<?php

namespace App\Http\Controllers\Api;

use App\Models\Result;
use App\Models\Registration;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ResultController
{
    // Получить результаты мероприятия
    public function index(Request $request)
    {
        try {
            $query = Result::with(['registration.user', 'registration.event', 'judge']);
            
            // Фильтр по мероприятию
            if ($eventId = $request->get('event_id')) {
                $query->whereHas('registration', function ($q) use ($eventId) {
                    $q->where('event_id', $eventId);
                });
            }
            
            // Фильтр по статусу регистрации
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }
            
            // Поиск по номеру участника или имени
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('result_time', 'like', "%{$search}%")
                      ->orWhereHas('registration', function ($q) use ($search) {
                          $q->where('bib_number', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($q) use ($search) {
                                $q->where('name', 'like', "%{$search}%");
                            });
                      });
                });
            }
            
            // Сортировка по времени (лучшие результаты first)
            $sortField = $request->get('sort', 'result_time');
            $sortDirection = $request->get('order', 'asc');

            if ($sortField === 'result_time') {
                // Проверяем тип БД
                $connection = config('database.default');
                
                if ($connection === 'mysql') {
                    // MySQL версия
                    $query->orderByRaw('
                        CAST(SUBSTRING_INDEX(result_time, ":", 1) AS UNSIGNED) * 60 + 
                        CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(result_time, ":", -1), ".", 1) AS DECIMAL(10,2))
                        ' . $sortDirection
                    );
                } else {
                    // SQLite версия (или другая)
                    $query->orderByRaw('
                        CAST(substr(result_time, 1, instr(result_time, ":") - 1) AS INTEGER) * 60 + 
                        CAST(substr(result_time, instr(result_time, ":") + 1) AS REAL)
                        ' . $sortDirection
                    );
                }
            } else {
                $query->orderBy($sortField, $sortDirection);
            }
            
            
            // // Специальная сортировка для времени
            // if ($sortField === 'result_time') {
            //     // Для корректной сортировки времени в формате MM:SS.ss
            //     // Создаем вычисляемое поле для сортировки
            //     $query->orderByRaw('
            //         CAST(SUBSTRING_INDEX(result_time, ":", 1) AS UNSIGNED) * 60 + 
            //         CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(result_time, ":", -1), ".", 1) AS DECIMAL(10,2))
            //         ' . $sortDirection
            //     );
            // } else {
            //     $query->orderBy($sortField, $sortDirection);
            // }
            
            // Пагинация
            $limit = $request->get('limit', 50);
            $page = $request->get('page', 1);
            
            $results = $query->paginate($limit, ['*'], 'page', $page);
            
            $formattedResults = $results->map(function ($result) {
                return [
                    'id' => $result->id,
                    'registration_id' => $result->registration_id,
                    'bib_number' => $result->registration->bib_number ?? '',
                    'user_name' => $result->registration->user->name ?? 'Удален',
                    'user_id' => $result->registration->user_id ?? null,
                    'event_id' => $result->registration->event_id ?? null,
                    'event_title' => $result->registration->event->title ?? 'Удалено',
                    'finish_time' => $result->finish_time ? $result->finish_time->format('Y-m-d H:i:s') : null,
                    'result_time' => $result->result_time,
                    'formatted_time' => $result->getFormattedTime(),
                    'time_in_seconds' => $result->getTimeInSeconds(),
                    'judge_id' => $result->judge_id,
                    'judge_name' => $result->judge->name ?? null,
                    'status' => $result->status,
                    'status_text' => $result->getStatusText(),
                    'status_color' => $result->getStatusColor(),
                    'notes' => $result->notes,
                    'synced' => $result->synced,
                    'created_at' => $result->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'data' => $formattedResults,
                'meta' => [
                    'total' => $results->total(),
                    'page' => $results->currentPage(),
                    'limit' => $results->perPage(),
                    'pages' => $results->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Фиксация результата
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            $validated = $request->validate([
                'registration_id' => 'required|exists:registrations,id',
                'result_time' => 'required|string|max:20',
                'finish_time' => 'nullable|date',
                'status' => 'sometimes|in:pending,confirmed,disqualified',
                'notes' => 'nullable|string',
            ]);
            
            // Проверяем, что регистрация существует
            $registration = Registration::findOrFail($validated['registration_id']);
            
            // Проверяем, что у регистрации есть стартовый номер
            if (empty($registration->bib_number)) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'У участника не назначен стартовый номер'
                ], 422);
            }
            
            // Проверяем, не зафиксирован ли уже результат
            $existingResult = Result::where('registration_id', $validated['registration_id'])->first();
            if ($existingResult) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Результат для этого участника уже зафиксирован'
                ], 422);
            }
            
            // Устанавливаем время финиша, если не передано
            if (!isset($validated['finish_time'])) {
                $validated['finish_time'] = Carbon::now();
            }
            
            // Устанавливаем судью
            $validated['judge_id'] = $user ? $user->id : null;
            
            // Статус по умолчанию
            if (!isset($validated['status'])) {
                $validated['status'] = 'pending';
            }
            
            $result = Result::create($validated);
            
            return response()->json([
                'id' => $result->id,
                'registration_id' => $result->registration_id,
                'result_time' => $result->result_time,
                'formatted_time' => $result->getFormattedTime(),
                'status' => $result->status,
                'message' => 'Результат зафиксирован успешно'
            ], 201);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Create failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Массовая синхронизация (для офлайн-режима)
    public function bulkSync(Request $request)
    {
        try {
            $user = $request->user();
            $judgeId = $user ? $user->id : null;
            
            $validated = $request->validate([
                'results' => 'required|array',
                'results.*.registration_id' => 'required|exists:registrations,id',
                'results.*.result_time' => 'required|string|max:20',
                'results.*.finish_time' => 'required|date',
                'results.*.status' => 'sometimes|in:pending,confirmed,disqualified',
                'results.*.notes' => 'nullable|string',
            ]);
            
            $syncedResults = [];
            $errors = [];
            
            foreach ($validated['results'] as $index => $resultData) {
                try {
                    // Проверяем, не существует ли уже результат
                    $existingResult = Result::where('registration_id', $resultData['registration_id'])->first();
                    
                    if ($existingResult) {
                        // Если результат уже есть, пропускаем или обновляем
                        // Простой вариант: пропускаем (конфликтов нет)
                        continue;
                    }
                    
                    // Создаем результат
                    $result = Result::create([
                        'registration_id' => $resultData['registration_id'],
                        'result_time' => $resultData['result_time'],
                        'finish_time' => Carbon::parse($resultData['finish_time']),
                        'judge_id' => $judgeId,
                        'status' => $resultData['status'] ?? 'pending',
                        'notes' => $resultData['notes'] ?? null,
                        'synced' => true,
                    ]);
                    
                    $syncedResults[] = $result->id;
                    
                } catch (\Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'registration_id' => $resultData['registration_id'],
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            return response()->json([
                'message' => 'Синхронизация завершена',
                'synced_count' => count($syncedResults),
                'synced_ids' => $syncedResults,
                'errors' => $errors,
                'has_errors' => !empty($errors)
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Sync failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Обновление результата
    public function update(Request $request, $id)
    {
        try {
            $result = Result::findOrFail($id);
            
            $validated = $request->validate([
                'result_time' => 'sometimes|string|max:20',
                'status' => 'sometimes|in:pending,confirmed,disqualified',
                'notes' => 'nullable|string',
            ]);
            
            $result->update($validated);
            
            return response()->json([
                'id' => $result->id,
                'result_time' => $result->result_time,
                'formatted_time' => $result->getFormattedTime(),
                'status' => $result->status,
                'message' => 'Результат обновлен'
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Удаление результата
    public function destroy($id)
    {
        try {
            $result = Result::findOrFail($id);
            $result->delete();
            
            return response()->json([
                'message' => 'Результат удален успешно',
                'deleted_id' => $id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Получить незавершенные результаты (для офлайн-синхронизации)
    public function getUnsynced(Request $request)
    {
        try {
            $user = $request->user();
            
            $results = Result::where('synced', false)
                ->where('judge_id', $user ? $user->id : null)
                ->get()
                ->map(function ($result) {
                    return [
                        'registration_id' => $result->registration_id,
                        'result_time' => $result->result_time,
                        'finish_time' => $result->finish_time->format('Y-m-d H:i:s'),
                        'status' => $result->status,
                        'notes' => $result->notes,
                        'created_at' => $result->created_at->toISOString(),
                    ];
                });
            
            return response()->json([
                'data' => $results,
                'count' => $results->count()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    // Подтвердить все результаты мероприятия
    public function confirmAll(Request $request, $eventId)
    {
        try {
            $count = Result::whereHas('registration', function ($q) use ($eventId) {
                $q->where('event_id', $eventId);
            })->update(['status' => 'confirmed']);
            
            return response()->json([
                'message' => 'Результаты подтверждены',
                'confirmed_count' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Confirm failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}