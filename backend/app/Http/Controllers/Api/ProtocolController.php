<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use App\Models\Registration;
use App\Models\Result;
use App\Models\User;
use App\Models\Distance;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProtocolController
{
    /**
     * GET /api/protocols/start/{eventId}
     * Формирование стартового протокола
     */
    public function startProtocol($eventId)
    {
        try {
            $event = Event::with(['distances', 'categories'])->findOrFail($eventId);
            
            // Получаем всех участников мероприятия
            $registrations = Registration::with(['user', 'team'])
                ->where('event_id', $eventId)
                ->where('status', 'approved') // Только подтвержденные заявки
                ->orderBy('bib_number')
                ->get();
            
            // Группируем по дистанциям
            $distances = [];
            foreach ($event->distances as $distance) {
                $distanceRegistrations = $registrations->where('discipline', $distance->name);
                
                // Внутри дистанции группируем по категориям
                $categories = [];
                foreach ($event->categories as $category) {
                    $categoryRegistrations = $distanceRegistrations->where('category', $category->name);
                    
                    if ($categoryRegistrations->count() > 0) {
                        $categories[] = [
                            'category_id' => $category->id,
                            'category_name' => $category->name,
                            'description' => $category->description ?? $category->name,
                            'participants' => $categoryRegistrations->map(function ($registration) {
                                return $this->formatParticipant($registration);
                            })->values()->toArray(),
                            'count' => $categoryRegistrations->count()
                        ];
                    }
                }
                
                if ($distanceRegistrations->count() > 0) {
                    $distances[] = [
                        'distance_id' => $distance->id,
                        'distance_name' => $distance->name,
                        'full_name' => $distance->full_name,
                        'categories' => $categories,
                        'count' => $distanceRegistrations->count()
                    ];
                }
            }
            
            // Участники без дистанции (если есть)
            $noDistanceRegistrations = $registrations->whereNull('discipline')->where('discipline', '');
            $noDistance = [];
            if ($noDistanceRegistrations->count() > 0) {
                $noDistance = $noDistanceRegistrations->map(function ($registration) {
                    return $this->formatParticipant($registration);
                })->values()->toArray();
            }
            
            return response()->json([
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date' => $event->date->format('Y-m-d'),
                    'location' => $event->location,
                    'discipline' => $event->discipline,
                    'status' => $event->status
                ],
                'distances' => $distances,
                'no_distance' => $noDistance,
                'total_participants' => $registrations->count(),
                'generated_at' => now()->toISOString(),
                'type' => 'start'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/protocols/final/{eventId}
     * Формирование итогового протокола
     */
    public function finalProtocol($eventId)
    {
        try {
            $event = Event::with(['distances', 'categories'])->findOrFail($eventId);
            
            // Получаем всех участников с результатами
            $registrations = Registration::with(['user', 'team', 'result'])
                ->where('event_id', $eventId)
                ->where('status', 'approved')
                ->get();
            
            // Группируем по дистанциям
            $distances = [];
            foreach ($event->distances as $distance) {
                $distanceRegistrations = $registrations->where('discipline', $distance->name);
                
                // Внутри дистанции группируем по категориям
                $categories = [];
                foreach ($event->categories as $category) {
                    $categoryRegistrations = $distanceRegistrations->where('category', $category->name);
                    
                    if ($categoryRegistrations->count() > 0) {
                        // Сортируем по времени (лучшие first)
                        $sortedParticipants = $categoryRegistrations->sortBy(function ($registration) {
                            return $registration->result ? $registration->result->getTimeInSeconds() : PHP_FLOAT_MAX;
                        });
                        
                        // Присваиваем места
                        $place = 1;
                        $previousTime = null;
                        $previousPlace = 1;
                        
                        $participants = [];
                        foreach ($sortedParticipants as $registration) {
                            $participantData = $this->formatParticipant($registration);
                            
                            // Добавляем результат
                            if ($registration->result) {
                                $participantData['result'] = [
                                    'id' => $registration->result->id,
                                    'result_time' => $registration->result->result_time,
                                    'formatted_time' => $registration->result->getFormattedTime(),
                                    'time_in_seconds' => $registration->result->getTimeInSeconds(),
                                    'finish_time' => $registration->result->finish_time ? 
                                        $registration->result->finish_time->format('Y-m-d H:i:s') : null,
                                    'status' => $registration->result->status,
                                    'status_text' => $registration->result->getStatusText(),
                                ];
                                $currentTime = $registration->result->getTimeInSeconds();
                            } else {
                                $participantData['result'] = null;
                                $currentTime = PHP_FLOAT_MAX;
                                $participantData['dns'] = true; // Did Not Start
                            }
                            
                            // Логика присвоения мест (при равенстве времени - одинаковые места)
                            if ($participantData['result']) {
                                if ($previousTime !== null && abs($currentTime - $previousTime) < 0.01) {
                                    // Равное время - одинаковое место
                                    $participantData['place'] = $previousPlace;
                                } else {
                                    $participantData['place'] = $place;
                                    $previousPlace = $place;
                                }
                                $previousTime = $currentTime;
                            } else {
                                // DNS - без места
                                $participantData['place'] = null;
                            }
                            
                            $participants[] = $participantData;
                            $place++;
                        }
                        
                        $categories[] = [
                            'category_id' => $category->id,
                            'category_name' => $category->name,
                            'description' => $category->description ?? $category->name,
                            'participants' => $participants,
                            'count' => $categoryRegistrations->count(),
                            'finished_count' => $categoryRegistrations->where(function ($reg) {
                                return $reg->result !== null;
                            })->count()
                        ];
                    }
                }
                
                if ($distanceRegistrations->count() > 0) {
                    $distances[] = [
                        'distance_id' => $distance->id,
                        'distance_name' => $distance->name,
                        'full_name' => $distance->full_name,
                        'categories' => $categories,
                        'count' => $distanceRegistrations->count(),
                        'finished_count' => $distanceRegistrations->where(function ($reg) {
                            return $reg->result !== null;
                        })->count()
                    ];
                }
            }
            
            // Статистика по мероприятию
            $totalParticipants = $registrations->count();
            $finishedParticipants = $registrations->where(function ($reg) {
                return $reg->result !== null;
            })->count();
            
            return response()->json([
                'event' => [
                    'id' => $event->id,
                    'title' => $event->title,
                    'date' => $event->date->format('Y-m-d'),
                    'location' => $event->location,
                    'discipline' => $event->discipline,
                    'status' => $event->status
                ],
                'distances' => $distances,
                'statistics' => [
                    'total_participants' => $totalParticipants,
                    'finished_participants' => $finishedParticipants,
                    'dns_participants' => $totalParticipants - $finishedParticipants,
                    'finish_percentage' => $totalParticipants > 0 ? 
                        round(($finishedParticipants / $totalParticipants) * 100, 1) : 0
                ],
                'generated_at' => now()->toISOString(),
                'type' => 'final'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/protocols/{eventId}/assign-numbers
     * Назначение стартовых номеров
     */
    public function assignNumbers(Request $request, $eventId)
    {
        try {
            $validated = $request->validate([
                'method' => 'required|in:random,manual', // random - случайно, manual - сохранить существующие
                'start_from' => 'sometimes|integer|min:1',
                'shuffle_categories' => 'sometimes|boolean' // Перемешивать внутри категорий
            ]);
            
            $event = Event::findOrFail($eventId);
            $registrations = Registration::where('event_id', $eventId)
                ->where('status', 'approved')
                ->get();
            
            DB::beginTransaction();
            
            if ($validated['method'] === 'random') {
                // Случайная жеребьевка
                $startFrom = $validated['start_from'] ?? 1;
                $shuffleCategories = $validated['shuffle_categories'] ?? false;
                
                // Если нужно перемешивать внутри категорий
                if ($shuffleCategories) {
                    $groupedRegistrations = $registrations->groupBy('category');
                    
                    foreach ($groupedRegistrations as $category => $categoryRegistrations) {
                        $shuffled = $categoryRegistrations->shuffle();
                        foreach ($shuffled as $index => $registration) {
                            $registration->bib_number = $startFrom + $index;
                            $registration->save();
                        }
                        $startFrom += $categoryRegistrations->count();
                    }
                } else {
                    // Просто перемешиваем всех
                    $shuffled = $registrations->shuffle();
                    foreach ($shuffled as $index => $registration) {
                        $registration->bib_number = $startFrom + $index;
                        $registration->save();
                    }
                }
                
                $message = 'Стартовые номера назначены случайным образом';
                
            } else {
                // manual - просто проверяем уникальность существующих номеров
                $bibNumbers = $registrations->pluck('bib_number')->filter()->toArray();
                $uniqueNumbers = array_unique($bibNumbers);
                
                if (count($bibNumbers) !== count($uniqueNumbers)) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'Validation failed',
                        'message' => 'Обнаружены дублирующиеся стартовые номера'
                    ], 422);
                }
                
                $message = 'Существующие номера проверены';
            }
            
            DB::commit();
            
            return response()->json([
                'message' => $message,
                'assigned_count' => $registrations->count(),
                'method' => $validated['method']
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Assignment failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * POST /api/protocols/{eventId}/clear-numbers
     * Очистка стартовых номеров
     */
    public function clearNumbers($eventId)
    {
        try {
            $count = Registration::where('event_id', $eventId)
                ->whereNotNull('bib_number')
                ->update(['bib_number' => null]);
            
            return response()->json([
                'message' => 'Стартовые номера очищены',
                'cleared_count' => $count
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Clear failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/protocols/{eventId}/export/pdf
     * Экспорт протокола в PDF
     */
    public function exportPDF(Request $request, $eventId)
    {
        try {
            $type = $request->get('type', 'final'); // start или final
            $distanceId = $request->get('distance_id');
            $categoryId = $request->get('category_id');
            
            // Здесь будет реализация генерации PDF
            // Пока возвращаем JSON с данными для PDF
            
            if ($type === 'start') {
                $protocolData = $this->startProtocol($eventId)->getData(true);
            } else {
                $protocolData = $this->finalProtocol($eventId)->getData(true);
            }
            
            return response()->json([
                'message' => 'PDF generation would be here',
                'data' => $protocolData,
                'export_params' => [
                    'event_id' => $eventId,
                    'type' => $type,
                    'distance_id' => $distanceId,
                    'category_id' => $categoryId,
                    'format' => 'pdf',
                    'generated_at' => now()->toISOString()
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Export failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Вспомогательный метод: форматирование участника
     */
    private function formatParticipant($registration)
    {
        $user = $registration->user;
        
        return [
            'registration_id' => $registration->id,
            'user_id' => $user->id,
            'name' => $user->name,
            'birth_year' => $user->birth_date ? $user->birth_date->format('Y') : null,
            'birth_date' => $user->birth_date ? $user->birth_date->format('Y-m-d') : null,
            'team_id' => $registration->team_id,
            'team_name' => $registration->team->name ?? null,
            'bib_number' => $registration->bib_number,
            'discipline' => $registration->discipline,
            'category' => $registration->category,
            'status' => $registration->status,
            'notes' => $registration->notes
        ];
    }
}