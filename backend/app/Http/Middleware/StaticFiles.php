// app/Http/Middleware/StaticFiles.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaticFiles
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->path();
        
        // Пути которые являются статикой
        $staticPaths = ['frontend', 'assets', 'css', 'js', 'images'];
        
        foreach ($staticPaths as $staticPath) {
            if (str_starts_with($path, $staticPath)) {
                $filePath = public_path($path);
                
                if (file_exists($filePath)) {
                    return response()->file($filePath);
                }
                
                abort(404);
            }
        }
        
        return $next($request);
    }
}