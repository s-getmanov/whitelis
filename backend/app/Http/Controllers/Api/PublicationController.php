<?php

namespace App\Http\Controllers\Api;

use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class PublicationController
{
    public function index(Request $request)
    {
        try {
            $query = Publication::with('author');
            
            // Фильтр по типу
            if ($type = $request->get('type')) {
                $query->where('type', $type);
            }
            
            // Фильтр по статусу (для админов - все, для публики - только опубликованные)
            $user = $request->user();
            if (!$user || !$user->isAdmin()) {
                $query->published();
            } elseif ($status = $request->get('status')) {
                $query->where('status', $status);
            }
            
            // Поиск
            if ($search = $request->get('search')) {
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%");
                });
            }
            
            // Сортировка
            $sortField = $request->get('sort', 'published_at');
            $sortDirection = $request->get('order', 'desc');
            
            // Сначала закрепленные, потом по дате публикации
            $query->orderBy('is_pinned', 'desc')
                  ->orderBy($sortField, $sortDirection);
            
            // Пагинация
            $limit = $request->get('limit', 10);
            $page = $request->get('page', 1);
            
            $publications = $query->paginate($limit, ['*'], 'page', $page);
            
            $formattedPublications = $publications->map(function ($publication) {
                return [
                    'id' => $publication->id,
                    'title' => $publication->title,
                    'slug' => $publication->slug,
                    'excerpt' => $publication->excerpt,
                    'content' => $publication->content,
                    'author_id' => $publication->author_id,
                    'author_name' => $publication->author->name ?? null,
                    'type' => $publication->type,
                    'type_text' => $publication->type_text,
                    'status' => $publication->status,
                    'status_text' => $publication->status_text,
                    'is_pinned' => $publication->is_pinned,
                    'is_published' => $publication->is_published,
                    'views_count' => $publication->views_count,
                    'published_at' => $publication->published_at ? $publication->published_at->format('Y-m-d H:i:s') : null,
                    'created_at' => $publication->created_at->toISOString(),
                ];
            });
            
            return response()->json([
                'data' => $formattedPublications,
                'meta' => [
                    'total' => $publications->total(),
                    'page' => $publications->currentPage(),
                    'limit' => $publications->perPage(),
                    'pages' => $publications->lastPage(),
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show($slug)
    {
        try {
            $publication = Publication::with('author')->where('slug', $slug)->firstOrFail();
            
            // Проверка доступа
            $user = request()->user();
            if (!$publication->is_published && (!$user || !$user->isAdmin())) {
                return response()->json(['error' => 'Not found'], 404);
            }
            
            // Увеличиваем счетчик просмотров
            $publication->incrementViews();
            
            return response()->json([
                'id' => $publication->id,
                'title' => $publication->title,
                'slug' => $publication->slug,
                'content' => $publication->content,
                'excerpt' => $publication->excerpt,
                'author_id' => $publication->author_id,
                'author' => $publication->author ? [
                    'id' => $publication->author->id,
                    'name' => $publication->author->name,
                    'email' => $publication->author->email,
                ] : null,
                'type' => $publication->type,
                'type_text' => $publication->type_text,
                'status' => $publication->status,
                'status_text' => $publication->status_text,
                'is_pinned' => $publication->is_pinned,
                'views_count' => $publication->views_count,
                'published_at' => $publication->published_at ? $publication->published_at->format('Y-m-d H:i:s') : null,
                'created_at' => $publication->created_at->toISOString(),
                'updated_at' => $publication->updated_at->toISOString(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Not found',
                'message' => 'Публикация не найдена'
            ], 404);
        }
    }
    
    public function store(Request $request)
    {
        try {
            $user = $request->user();
            
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && !$user->isAdmin()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'content' => 'required|string',
                'excerpt' => 'nullable|string',
                'type' => 'required|in:news,announcement,article,page',
                'status' => 'sometimes|in:draft,published,archived',
                'is_pinned' => 'sometimes|boolean',
                'published_at' => 'nullable|date',
            ]);
            
            // Генерация slug
            $slug = Str::slug($validated['title']);
            $counter = 1;
            $originalSlug = $slug;
            
            while (Publication::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            $validated['slug'] = $slug;
            $validated['author_id'] = $user->id;
            
            // Если публикуем, устанавливаем дату публикации
            if ($validated['status'] === 'published' && empty($validated['published_at'])) {
                $validated['published_at'] = now();
            }
            
            $publication = Publication::create($validated);
            
            return response()->json([
                'id' => $publication->id,
                'slug' => $publication->slug,
                'title' => $publication->title,
                'message' => 'Публикация создана успешно'
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
            $user = $request->user();

            
            //[TODO] Обход авторизации для разработки. Допускаем user=null.
            if ($user && !$user->isAdmin()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            
            $publication = Publication::findOrFail($id);
            
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'content' => 'sometimes|string',
                'excerpt' => 'nullable|string',
                'type' => 'sometimes|in:news,announcement,article,page',
                'status' => 'sometimes|in:draft,published,archived',
                'is_pinned' => 'sometimes|boolean',
                'published_at' => 'nullable|date',
            ]);
            
            // Если меняем заголовок, обновляем slug
            if (isset($validated['title']) && $validated['title'] !== $publication->title) {
                $slug = Str::slug($validated['title']);
                $counter = 1;
                $originalSlug = $slug;
                
                while (Publication::where('slug', $slug)->where('id', '!=', $publication->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $validated['slug'] = $slug;
            }
            
            // Если меняем статус на published и нет даты публикации
            if (isset($validated['status']) && $validated['status'] === 'published' && !$publication->published_at) {
                $validated['published_at'] = now();
            }
            
            $publication->update($validated);
            
            return response()->json([
                'id' => $publication->id,
                'title' => $publication->title,
                'slug' => $publication->slug,
                'message' => 'Публикация обновлена'
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
            $user = request()->user();
            if (!$user || !$user->isAdmin()) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
            
            $publication = Publication::findOrFail($id);
            $publication->delete();
            
            return response()->json([
                'message' => 'Публикация удалена успешно',
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