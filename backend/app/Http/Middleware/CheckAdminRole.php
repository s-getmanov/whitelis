<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, авторизован ли пользователь
        if (!auth()->check()) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Требуется авторизация'
            ], 401);
        }

        // Проверяем, является ли пользователь администратором
        if (auth()->user()->role !== 'admin') {
            return response()->json([
                'error' => 'Forbidden',
                'message' => 'Доступ запрещен. Требуются права администратора.'
            ], 403);
        }

        return $next($request);
    }
}