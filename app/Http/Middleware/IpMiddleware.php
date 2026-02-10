<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IpMiddleware
{
    protected $allowedIps = [
        '94.126.201.195',
        '127.0.0.1',
    ];

    public function handle(Request $request, Closure $next)
    {
        $clientIp = $request->ip();

        if (!in_array($clientIp, $this->allowedIps)) {
            return Response::json([
                'message' => 'Access denied from this IP address.'
            ], 403);
        }

        return $next($request);
    }
}
