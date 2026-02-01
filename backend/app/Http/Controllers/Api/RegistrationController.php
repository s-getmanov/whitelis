<?php

namespace App\Http\Controllers\Api;

use App\Models\Registration;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class RegistrationController
{
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $query = Registration::with(['user', 'event']);

            // РОЛЕВАЯ ФИЛЬТРАЦИЯ (без middleware, проверка в контроллере)
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if (!$user) {
                // Если user=null (для разработки), показываем все
                // В будущем: return response()->json(['error' => 'Unauthorized'], 401);
            } elseif ($user->role === 'participant') {
                // Участник видит только свои заявки
                $query->where('user_id', $user->id);
            } elseif ($user->role === 'team_manager') {
                // Менеджер команды видит заявки своей команды
                if ($user->team_id) {
                    $query->where('team_id', $user->team_id);
                } else {
                    // Если team_manager без команды, показываем пустой список
                    $query->whereRaw('1=0');
                }
            }
            // Администратор видит все (без фильтра)

            // Фильтр по мероприятию
            if ($eventId = $request->get('event_id')) {
                $query->where('event_id', $eventId);
            }

            // Фильтр по пользователю (только для админов)
            if ($userId = $request->get('user_id') && (!$user || $user->role === 'admin')) {
                $query->where('user_id', $userId);
            }

            // Фильтр по статусу
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            // Поиск
            if ($search = $request->get('search')) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                        ->orWhere('team', 'like', "%{$search}%")
                        ->orWhere('bib_number', 'like', "%{$search}%");
                });
            }

            // Сортировка
            $sortField = $request->get('sort', 'created_at');
            $sortDirection = $request->get('order', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // Пагинация
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);

            $registrations = $query->paginate($limit, ['*'], 'page', $page);

            $formattedRegistrations = $registrations->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'user_id' => $registration->user_id,
                    'event_id' => $registration->event_id,
                    'user_name' => $registration->user->name ?? 'Удален',
                    'user_email' => $registration->user->email ?? '',
                    'event_title' => $registration->event->title ?? 'Удалено',
                    'event_date' => $registration->event->date->format('Y-m-d') ?? '',
                    'discipline' => $registration->discipline,
                    'category' => $registration->category,
                    'team_id' => $registration->team_id, // 
                    'team_name' => $registration->team->name ?? null, // 
                    'team' => $registration->team,
                    'bib_number' => $registration->bib_number,
                    'status' => $registration->status,
                    'status_text' => $registration->getStatusText(),
                    'status_color' => $registration->getStatusColor(),
                    'notes' => $registration->notes,
                    'created_at' => $registration->created_at->toISOString(),
                    'can_edit' => true, // Для фронтенда
                ];
            });

            return response()->json([
                'data' => $formattedRegistrations,
                'meta' => [
                    'total' => $registrations->total(),
                    'page' => $registrations->currentPage(),
                    'limit' => $registrations->perPage(),
                    'pages' => $registrations->lastPage(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $registration = Registration::with(['user', 'event'])->findOrFail($id);

            // Базовая проверка доступа
            $user = request()->user();

            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && $user->role === 'participant' && $registration->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            return response()->json([
                'id' => $registration->id,
                'user_id' => $registration->user_id,
                'event_id' => $registration->event_id,
                'user' => [
                    'id' => $registration->user->id ?? null,
                    'name' => $registration->user->name ?? 'Удален',
                    'email' => $registration->user->email ?? '',
                    'phone' => $registration->user->phone ?? '',
                ],
                'event' => [
                    'id' => $registration->event->id ?? null,
                    'title' => $registration->event->title ?? 'Удалено',
                    'date' => $registration->event->date->format('Y-m-d') ?? '',
                    'location' => $registration->event->location ?? '',
                    'discipline' => $registration->event->discipline ?? '',
                ],
                'discipline' => $registration->discipline,
                'category' => $registration->category,
                'team_id' => $registration->team_id, // добавить
                'team_name' => $registration->team->name ?? null, // добавить
                'team' => $registration->team->name ?? null, // для совместимости
                'bib_number' => $registration->bib_number,
                'status' => $registration->status,
                'status_text' => $registration->getStatusText(),
                'notes' => $registration->notes,
                'created_at' => $registration->created_at->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Регистрация не найдена'
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->user();

            // Автоматически определяем user_id для участников
            $userId = $user && $user->role === 'participant' ? $user->id : $request->get('user_id');


            //Новая валидачия с командой по ИД
            $validated = $request->validate([
                'event_id' => 'required|exists:events,id',
                'discipline' => 'nullable|string|max:100',
                'category' => 'nullable|string|max:100',
                'team_id' => 'nullable|exists:teams,id', // ИЗМЕНИТЬ 'team' на 'team_id'
                'notes' => 'nullable|string',
            ]);

            // Добавляем user_id в валидированные данные
            $validated['user_id'] = $userId;

            //[TODO] Это проверить когда запустим авторизацию. Хз как работает в плане ролей. 
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && $user->role === 'team_manager' && $user->team_id) {
                $validated['team_id'] = $user->team_id;
            }
            /////////////////////////////////////////////////////

            if (!$userId) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Не указан участник'
                ], 422);
            }

            // Проверяем, не зарегистрирован ли уже пользователь на это мероприятие
            $exists = Registration::where('user_id', $userId)
                ->where('event_id', $validated['event_id'])
                ->exists();

            if ($exists) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Пользователь уже зарегистрирован на это мероприятие'
                ], 422);
            }

            // Проверяем, что мероприятие не прошло
            $event = Event::find($validated['event_id']);
            if ($event->date < Carbon::now()) {
                return response()->json([
                    'error' => 'Validation failed',
                    'message' => 'Нельзя регистрироваться на прошедшие мероприятия'
                ], 422);
            }

            // Статус по умолчанию
            $validated['status'] = 'pending';

            $registration = Registration::create($validated);

            return response()->json([
                'id' => $registration->id,
                'message' => 'Заявка создана успешно'
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




    public function update(Request $request, $id)
    {
        try {
            $registration = Registration::findOrFail($id);
            $user = $request->user();

            // Проверка доступа
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && $user->role === 'participant' && $registration->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }



            $validated = $request->validate([
                'discipline' => 'nullable|string|max:100',
                'category' => 'nullable|string|max:100',
                'team_id' => 'nullable|exists:teams,id', // ИЗМЕНИТЬ
                'bib_number' => ['nullable', 'string', Rule::unique('registrations')->ignore($registration->id)],
                'status' => 'sometimes|in:pending,approved,rejected,cancelled,completed',
                'notes' => 'nullable|string',
            ]);

            $registration->update($validated);

            return response()->json([
                'id' => $registration->id,
                'message' => 'Заявка обновлена'
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

    public function destroy($id)
    {
        try {
            $registration = Registration::findOrFail($id);
            $user = request()->user();

            // Проверка доступа
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && $user->role === 'participant' && $registration->user_id !== $user->id) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $registration->delete();

            return response()->json([
                'message' => 'Заявка удалена успешно',
                'deleted_id' => $id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Массовое обновление статусов (только для админов)
    public function bulkUpdate(Request $request)
    {
        try {
            $user = $request->user();
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && $user->role !== 'admin') {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $validated = $request->validate([
                'ids' => 'required|array',
                'ids.*' => 'exists:registrations,id',
                'status' => 'required|in:pending,approved,rejected,cancelled,completed',
            ]);

            $registrations = Registration::whereIn('id', $validated['ids'])->get();

            foreach ($registrations as $registration) {
                $registration->update(['status' => $validated['status']]);
            }

            return response()->json([
                'message' => 'Статусы обновлены успешно',
                'updated_count' => $registrations->count()
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Bulk update failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}