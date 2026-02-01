<?php

namespace App\Http\Controllers\Api;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

//[TODO] Обход авторизации Для этого контроллера нет проверки юзера совсем. Сделать как в остальных. 
//[TODO] В некоторых контроллерах - какая-то фигня с - $event->date->format('Y-m-d'), Вроде работает, но надо разобраться. 

class EventController
{
    /**
     * GET /api/events - Получить список мероприятий
     * Поддерживает: поиск, фильтры, пагинацию, сортировку
     */
    public function index(Request $request)
    {
        try {
            $query = Event::query();
            Log::info('Запрос на получение мероприятий', $request->toArray());

            // 1. ПОИСК по нескольким полям (в основном для EventList.vue)
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%")
                      ->orWhere('location', 'like', "%{$search}%")
                      ->orWhere('discipline', 'like', "%{$search}%");
                });
                             
            }
            
            // 2. ФИЛЬТР по статусу (upcoming, active, completed, draft)
            if ($status = $request->get('status')) {
                $query->where('status', $status);                
            }
            
            // 3. СОРТИРОВКА (date, title, created_at)
            $sortField = $request->get('sort', 'date');
            $sortDirection = $request->get('order', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // 4. ПАГИНАЦИЯ (как в EventList.vue: limit = 6 или 10)
            $limit = $request->get('limit', $request->get('itemsPerPage', 10));
            $page = $request->get('page', 1);
            
            // Получаем данные с пагинацией
            $events = $query->paginate($limit, ['*'], 'page', $page);
            
            // 5. Форматируем ответ ТОЧНО как ожидает EventList.vue
            $formattedEvents = $events->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'description' => $event->description,
                    'date' => $event->date->format('Y-m-d'), // Важно: строка 'Y-m-d'
                    'location' => $event->location,
                    'discipline' => $event->discipline,
                    'status' => $event->status,
                    'participants_count' => (int) $event->participants_count,
                    'registrations_count' => (int) $event->registrations_count,
                    'max_participants' => $event->max_participants ? (int) $event->max_participants : null,
                    'distances' => $this->getDistances($event->discipline), // Доп поле (проверитьь!!!)
                    'categories' => $this->getCategories($event->discipline), // Доп поле (проверитьь!!!)
                    'created_at' => $event->created_at->toISOString(),
                    'updated_at' => $event->updated_at->toISOString(),
                ];
            });
            
            return response()->json([
                'data' => $formattedEvents,
                'meta' => [
                    'total' => $events->total(),
                    'page' => $events->currentPage(),
                    'limit' => $events->perPage(),
                    'pages' => $events->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/events/{id} - Получить одно мероприятие
     */
    public function show($id)
    {
        try {
            $event = Event::findOrFail($id);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'location' => $event->location,
                'discipline' => $event->discipline,
                'status' => $event->status,
                'status_text' => $this->getStatusText($event->status),
                'participants_count' => (int) $event->participants_count,
                'registrations_count' => (int) $event->registrations_count,
                'max_participants' => $event->max_participants ? (int) $event->max_participants : null,
                'distances' => $this->getDistances($event->discipline),
                'categories' => $this->getCategories($event->discipline),
                'created_at' => $event->created_at->toISOString(),
                'updated_at' => $event->updated_at->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Мероприятие не найдено'
            ], 404);
        }
    }
    
    /**
     * POST /api/events - Создать мероприятие
     * Для админки: создание нового мероприятия
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'date' => 'required|date|after_or_equal:today',
                'location' => 'required|string|max:255',
                'discipline' => 'required|string|max:100',
                'status' => 'in:draft,upcoming,active,completed',
                'max_participants' => 'nullable|integer|min:1',
            ]);
            
            // Устанавливаем значения по умолчанию
            $validated['participants_count'] = 0;
            $validated['registrations_count'] = 0;
            
            $event = Event::create($validated);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'location' => $event->location,
                'discipline' => $event->discipline,
                'status' => $event->status,
                'participants_count' => 0,
                'registrations_count' => 0,
                // [todo] - max_participants убрать, никто не ограничивает. 
                'max_participants' => $event->max_participants,
                // 
                'message' => 'Мероприятие создано успешно'
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
    
    /**
     * PUT /api/events/{id} - Обновить мероприятие
     * Для админки: редактирование
     */
    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'date' => 'sometimes|date',
                'location' => 'sometimes|string|max:255',
                'discipline' => 'sometimes|string|max:100',
                'status' => 'sometimes|in:draft,upcoming,active,completed',
                'max_participants' => 'nullable|integer|min:1',
                'participants_count' => 'sometimes|integer|min:0',
                'registrations_count' => 'sometimes|integer|min:0',
            ]);
            
            $event->update($validated);
            
            return response()->json([
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'location' => $event->location,
                'discipline' => $event->discipline,
                'status' => $event->status,
                'participants_count' => (int) $event->participants_count,
                'registrations_count' => (int) $event->registrations_count,
                'max_participants' => $event->max_participants,
                'message' => 'Мероприятие обновлено'
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
    
    /**
     * DELETE /api/events/{id} - Удалить мероприятие
     * Для админки: удаление
     */
    public function destroy($id)
    {
        try {
            $event = Event::findOrFail($id);
            $event->delete();
            
            return response()->json([
                'message' => 'Мероприятие удалено успешно',
                'deleted_id' => $id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * GET /api/events/stats - Статистика для Dashboard
     * Dashboard.vue  убрал, - удалить код после теста. [todo]
     */
    // public function stats()
    // {
    //     try {
    //         $stats = [
    //             'events' => Event::count(),
    //             'activeEvents' => Event::where('status', 'active')->count(),
    //             'participants' => (int) Event::sum('participants_count'),
    //             'newParticipants' => (int) Event::whereDate('created_at', today())->sum('participants_count'),
    //             'registrations' => (int) Event::sum('registrations_count'),
    //             'pendingRegistrations' => 5, // Заглушка для MVP
    //             'results' => 156, // Заглушка для MVP
    //             'todayResults' => 8, // Заглушка для MVP
    //         ];
            
    //         return response()->json($stats);
            
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'events' => 0,
    //             'activeEvents' => 0,
    //             'participants' => 0,
    //             'newParticipants' => 0,
    //             'registrations' => 0,
    //             'pendingRegistrations' => 0,
    //             'results' => 0,
    //             'todayResults' => 0,
    //         ]);
    //     }
    // }
    
    // /**
    //  * GET /api/events/recent - Недавние мероприятия для Dashboard
    //  * ТОЧНО как ожидает Dashboard.vue (limit=3)
    //  */
    // public function recent(Request $request)
    // {
    //     try {
    //         $limit = $request->get('limit', 3);
            
    //         $events = Event::orderBy('created_at', 'desc')
    //             ->limit($limit)
    //             ->get()
    //             ->map(function ($event) {
    //                 return [
    //                     'id' => $event->id,
    //                     'title' => $event->title,
    //                     'date' => $event->date->format('Y-m-d'),
    //                     'location' => $event->location,
    //                     'status' => $event->status,
    //                     'participants_count' => (int) $event->participants_count,
    //                     'registrations_count' => (int) $event->registrations_count,
    //                 ];
    //             });
            
    //         return response()->json($events);
            
    //     } catch (\Exception $e) {
    //         return response()->json([]);
    //     }
    // }
    
    /**
     * Вспомогательные методы для дополнительных полей
     * (distances, categories - как в демо-данных EventList.vue)
     */
    private function getDistances($discipline)
    {
        $distances = [
            'Лыжные гонки' => ['5км', '10км', '21км'],
            'Бег' => ['5км', '10км', '21км', '42км'],
            'Ориентирование' => ['Короткая', 'Длинная'],
            'Велоспорт' => ['30км', '60км', '100км'],
            'Триатлон' => ['Спринт', 'Олимпийская', 'Полужелезная'],
        ];
        
        return $distances[$discipline] ?? ['Стандартная'];
    }
    
    private function getCategories($discipline)
    {
        $categories = [
            'Лыжные гонки' => ['Мужчины', 'Женщины', 'Юниоры'],
            'Бег' => ['Общий зачет', 'Возрастные группы'],
            'Ориентирование' => ['18-25 лет', '26-40 лет', '40+ лет'],
        ];
        
        return $categories[$discipline] ?? ['Общий зачет'];
    }
    
    private function getStatusText($status)
    {
        $texts = [
            'draft' => 'Черновик',
            'upcoming' => 'Предстоящее',
            'active' => 'Активное',
            'completed' => 'Завершено',
        ];
        
        return $texts[$status] ?? $status;
    }
}