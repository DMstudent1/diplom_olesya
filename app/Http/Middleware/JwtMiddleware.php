<?php

namespace App\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle($request, Closure $next)
    {
        try {
            // Если токен есть в cookie, переносим его в заголовок Authorization
            if ($request->hasCookie('access_token')) {
                $token = $request->cookie('access_token');
                $request->headers->set('Authorization', 'Bearer ' . $token);
                
                // Логируем для отладки
                \Log::info('JWT token found in cookie', [
                    'token_prefix' => substr($token, 0, 30) . '...'
                ]);
            }
            
            // Пытаемся аутентифицировать пользователя
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                throw new Exception('User not found');
            }
            
        } catch (Exception $e) {
            \Log::warning('JWT authentication failed', [
                'error' => $e->getMessage(),
                'has_cookie' => $request->hasCookie('access_token'),
                'path' => $request->path()
            ]);
            
            return response()->json([
                'error' => 'Неавторизован',
            ], 401);
        }

        return $next($request);
    }
}