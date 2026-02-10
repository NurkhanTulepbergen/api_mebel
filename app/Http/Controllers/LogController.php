<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

use App\Models\Log;
use App\Http\Traits\TelegramTrait;

class LogController extends Controller
{
    use TelegramTrait;

    private function resetFiles($filePath, $fileNames) {
        foreach ($fileNames as $fileName) {
            $fullPath = $filePath . '/' . $fileName;
            if (File::exists($fullPath)) {
                File::put($fullPath, '');
            } else {
                File::put($fullPath, '');
                chmod($fullPath, 777);
            }
        }
    }

    public function sendLogs(Request $request) {
        $isLogAlreadySendToday = Log::whereDate('created_at', Carbon::today())->exists();
        if($isLogAlreadySendToday) {
            return response()->json([
                'response' => 'Logs was already sent today'
            ], 401);
        }
        $filePath = base_path().'/storage/logs/api';
        $fileNames = [
            'general.log',
            'creation.log',
            'update.log',
            'deletion.log',
            'errors.log',
        ];
        $acceptedFileNames = $this->sendFiles($filePath, $fileNames);
        if($acceptedFileNames == null) {
            $this->sendMessage('Файлы не были отправлены');
            return response()->json([
                'message' => 'Файлы не были отправлены',
            ], 402);
        }
        $this->resetFiles($filePath, $acceptedFileNames);
        Log::create();
        return response()->json([
            'message' => 'all good',
            'accepted_files' => $acceptedFileNames
        ], 200);
    }
}
