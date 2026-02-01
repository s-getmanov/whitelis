<?php

namespace App\Http\Controllers\Api;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController
{
    public function index(Request $request)
    {
        try {
            $query = Team::with(['captain', 'members']);
            
            // Поиск
            if ($search = $request->get('search')) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            }
            
            // Фильтр по статусу
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }
            
            // Сортировка
            $sortField = $request->get('sort', 'name');
            $sortDirection = $request->get('order', 'asc');
            $query->orderBy($sortField, $sortDirection);
            
            // Пагинация
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            
            $teams = $query->paginate($limit, ['*'], 'page', $page);
            
            $formattedTeams = $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name,
                    'slug' => $team->slug,
                    'description' => $team->description,
                    'captain_id' => $team->captain_id,
                    'captain_name' => $team->captain->name ?? null,
                    'status' => $team->status,
                    'status_text' => $team->getStatusText(),
                    'members_count' => $team->members_count ?? $team->members()->count(),
                    'created_at' => $team->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'data' => $formattedTeams,
                'meta' => [
                    'total' => $teams->total(),
                    'page' => $teams->currentPage(),
                    'limit' => $teams->perPage(),
                    'pages' => $teams->lastPage(),
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
            $team = Team::with(['captain', 'members'])->findOrFail($id);
            
            return response()->json([
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'description' => $team->description,
                'captain_id' => $team->captain_id,
                'captain' => $team->captain ? [
                    'id' => $team->captain->id,
                    'name' => $team->captain->name,
                    'email' => $team->captain->email,
                ] : null,
                'status' => $team->status,
                'status_text' => $team->getStatusText(),
                'members' => $team->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'role' => $member->role,
                    ];
                }),
                'created_at' => $team->created_at->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Команда не найдена'
            ], 404);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:100|unique:teams',
                'description' => 'nullable|string',
                'captain_id' => 'nullable|exists:users,id',
                'status' => 'sometimes|in:active,inactive',
            ]);
            
            // Генерация slug
            $validated['slug'] = \Str::slug($validated['name']);
            
            $team = Team::create($validated);
            
            return response()->json([
                'id' => $team->id,
                'name' => $team->name,
                'slug' => $team->slug,
                'message' => 'Команда создана успешно'
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
            $team = Team::findOrFail($id);
            
            $validated = $request->validate([
                'name' => ['sometimes', 'string', 'max:100', Rule::unique('teams')->ignore($team->id)],
                'description' => 'nullable|string',
                'captain_id' => 'nullable|exists:users,id',
                'status' => 'sometimes|in:active,inactive',
            ]);
            
            // Обновляем slug если изменилось имя
            if (isset($validated['name']) && $validated['name'] !== $team->name) {
                $validated['slug'] = \Str::slug($validated['name']);
            }
            
            $team->update($validated);
            
            return response()->json([
                'id' => $team->id,
                'name' => $team->name,
                'message' => 'Команда обновлена'
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
            $team = Team::findOrFail($id);
            
            // Проверяем, нет ли заявок у команды
            if ($team->registrations()->exists()) {
                return response()->json([
                    'error' => 'Cannot delete',
                    'message' => 'Невозможно удалить команду, так как существуют связанные заявки'
                ], 422);
            }
            
            // Обнуляем team_id у участников команды
            User::where('team_id', $team->id)->update(['team_id' => null]);
            
            $team->delete();
            
            return response()->json([
                'message' => 'Команда удалена успешно',
                'deleted_id' => $id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function members($id)
    {
        try {
            $team = Team::findOrFail($id);
            $members = $team->members()->paginate(50);
            
            $formattedMembers = $members->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role,
                    'status' => $member->status,
                    'created_at' => $member->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'data' => $formattedMembers,
                'meta' => [
                    'total' => $members->total(),
                    'page' => $members->currentPage(),
                    'limit' => $members->perPage(),
                    'pages' => $members->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Команда не найдена'
            ], 404);
        }
    }
}