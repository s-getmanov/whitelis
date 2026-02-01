<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use App\Models\Team;

class UserController
{
    public function index(Request $request)
    {
        try {
            $query = User::query();
            
            // Поиск
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            }
            
            // Фильтр по роли
            if ($role = $request->get('role')) {
                $query->where('role', $role);
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
            
            $users = $query->paginate($limit, ['*'], 'page', $page);
            
            $formattedUsers = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role,
                    'role_text' => $user->getRoleText(),
                    'status' => $user->status,
                    'status_text' => $user->getStatusText(),
                    'created_at' => $user->created_at->toISOString(),
                    'registrations_count' => $user->registrations()->count(),
                ];
            });
            
            return response()->json([
                'data' => $formattedUsers,
                'meta' => [
                    'total' => $users->total(),
                    'page' => $users->currentPage(),
                    'limit' => $users->perPage(),
                    'pages' => $users->lastPage(),
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
            $user = User::findOrFail($id);
            
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role,
                'role_text' => $user->getRoleText(),
                'status' => $user->status,
                'status_text' => $user->getStatusText(),
                'created_at' => $user->created_at->toISOString(),
                'updated_at' => $user->updated_at->toISOString(),
                'registrations_count' => $user->registrations()->count(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Пользователь не найден'
            ], 404);
        }
    }
    
    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'role' => 'sometimes|in:admin,participant,judge,volunteer,team_manager', // добавили team_manager
            'status' => 'sometimes|in:active,blocked,pending',
            'team_name' => 'nullable|string|max:100' // новое поле
        ]);
        
        // Устанавливаем статус по умолчанию, если не передан
        if (!isset($validated['role'])) {
            $validated['role'] = 'participant';
        }

        // Устанавливаем статус по умолчанию
        if (!isset($validated['status'])) {
            $validated['status'] = 'active';
        }
        
        $validated['password'] = Hash::make($validated['password']);
        
        $user = User::create($validated);

        // Если указано название команды, создаем команду
        if (!empty($validated['team_name'])) {
            $team = Team::create([
                'name' => $validated['team_name'],
                'slug' => \Str::slug($validated['team_name']),
                'captain_id' => $user->id,
                'status' => 'active'
            ]);
            
            // Назначаем пользователю роль team_manager и привязываем к команде
            $user->update([
                'role' => 'team_manager',
                'team_id' => $team->id
            ]);
        }
        
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'team_id' => $user->team_id,
            'message' => 'Пользователь создан успешно'
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
            $user = User::findOrFail($id);
            
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
                'password' => 'nullable|string|min:6',
                'phone' => 'nullable|string|max:20',
                'role' => 'sometimes|in:admin,participant,judge,volunteer',
                'status' => 'sometimes|in:active,blocked,pending',
            ]);
            
            // Хешируем пароль если он был передан
            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }
            
            $user->update($validated);
            
            return response()->json([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'message' => 'Пользователь обновлен'
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
            $user = User::findOrFail($id);
            $user->delete();
            
            return response()->json([
                'message' => 'Пользователь удален успешно',
                'deleted_id' => $id
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Delete failed',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}