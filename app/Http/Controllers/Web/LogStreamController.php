<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;

class LogStreamController extends Controller
{
    public function __invoke()
    {
        $logFile = storage_path('logs/mass-delete.log');

        return response()->stream(function () use ($logFile) {
            // Открываем файл один раз и сразу ставим указатель в конец
            $fp = fopen($logFile, 'r');
            if (!$fp) {
                echo "data: Файл лога не найден\n\n";
                return;
            }
            clearstatcache(true, $logFile);
            fseek($fp, 0, SEEK_END);

            // Бесконечный цикл, пока соединение живо
            while (!connection_aborted()) {
                // Читаем все новые строки
                while (($line = fgets($fp)) !== false) {
                    $trimmed = rtrim($line, "\r\n");
                    echo "data: " . addslashes($trimmed) . "\n\n";
                    ob_flush();
                    flush();
                }

                // Делаем паузу, чтобы не нагружать CPU
                sleep(1);

                // Проверяем, не удалили ли файл или не сбросился ли указатель
                clearstatcache(true, $logFile);
                if (!file_exists($logFile)) {
                    break;
                }
                // Если файл уменьшился (перезаписали), сбрасываем указатель в начало
                $currentSize = ftell($fp);
                $realSize    = filesize($logFile);
                if ($realSize < $currentSize) {
                    fseek($fp, 0, SEEK_SET);
                }
            }

            fclose($fp);
        }, 200, [
            'Content-Type'        => 'text/event-stream',
            'Cache-Control'       => 'no-cache, no-store',
            'X-Accel-Buffering'   => 'no',         // для nginx: отключаем буферизацию
            'Connection'          => 'keep-alive',
        ]);
    }
}
