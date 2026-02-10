<?php

namespace App\Http\Controllers;

use App\Jobs\TestRedisJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TestRedisController extends Controller
{
    public function startJob()
    {
        $jobId = uniqid('test_');
        TestRedisJob::dispatch($jobId)->onQueue('default');
        return response()->json(['jobId' => $jobId]);
    }

    public function getProgress($jobId)
    {
        $progress = Cache::get("test_progress_{$jobId}", 0);
        return response()->json(['percentage' => $progress]);
    }
}
