<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Redis;

class TestRedisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $jobId;

    public function __construct($jobId)
    {
        $this->jobId = $jobId;
    }

    public function handle()
    {
        $total = 100;
        for ($i = 1; $i <= $total; $i++) {
            // Записываем число в Redis
            Redis::set("test_number_{$this->jobId}_{$i}", $i);
            // Обновляем прогресс в кэше
            Cache::put("test_progress_{$this->jobId}", ($i / $total) * 100, now()->addMinutes(10));
            // Имитация длительной операции
            usleep(50000); // Задержка 50ms
        }
        // Очищаем прогресс после завершения
        Cache::forget("test_progress_{$this->jobId}");
    }
}
