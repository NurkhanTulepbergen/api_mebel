<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAPIToken
{
    public function handle(Request $request, Closure $next)
    {
        $staticToken = env('API_TOKEN');
        $authorization = $request->header('Authorization');

        // Если токен передается с префиксом "Bearer ", удаляем его
        if (strpos($authorization, 'Bearer ') === 0) {
            $authorization = substr($authorization, 7);
        }

        if ($authorization !== $staticToken) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }
}
