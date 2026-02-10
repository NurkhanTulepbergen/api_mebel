<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Redis;

class ClearCacheController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $prefix = (string) config('database.redis.options.prefix', '');

        $pattern = $prefix . '*';
        $connection = Redis::connection();

        foreach ((array) $connection->keys($pattern) as $key) {
            $connection->del($key);
        }

        return back();
    }
}
