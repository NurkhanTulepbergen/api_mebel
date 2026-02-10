<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;

class ProfileJsonResponse
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if (! app()->bound('debugbar') || ! app('debugbar')->isEnabled()) {
            return $response;
        }

        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $data['_debugbar'] = app('debugbar')->getData();
            $response->setData($data);
        }

        return $response;
    }
}
