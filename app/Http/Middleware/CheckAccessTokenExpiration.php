<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccessTokenExpiration
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        if(!$token) {
            return response()->json([
                'message' => 'Access token was not found',
            ], 401);
        }

        if($token->expires_at == null)
            return $next($request);

        if(now()->greaterThan($token->expires_at)) {
            if($token) $token->delete();
            return response()->json([
                'message' => 'Access token has expired',
            ], 401);
        }

        return $next($request);
    }
}
