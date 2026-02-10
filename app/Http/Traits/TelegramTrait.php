<?php
namespace App\Http\Traits;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

trait TelegramTrait
{
    protected function sendMessage($message)
    {
        $token = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');
        $url = "https://api.telegram.org/bot{$token}/sendMessage";
        $response = Http::get($url, ['chat_id' => $chatId, 'text' => $message]);
        return $response;
    }

    protected function sendFiles($filePath, $fileNames)
    {
        $token = config('telegram.bot_token');
        $chatId = config('telegram.chat_id');

        $url = "https://api.telegram.org/bot{$token}/sendDocument";
        $message = '';
        $sentFiles = [];

        // лимит 49.9 МБ (в байтах)
        $chunkSize = 49.9 * 1024 * 1024;

        foreach ($fileNames as $fileName) {
            $fullPath = "{$filePath}/{$fileName}";

            if (!file_exists($fullPath)) {
                $message .= $fileName . " not found\n";
                continue;
            }

            $fileSize = filesize($fullPath);

            // если файл ≤ 50 МБ — отправляем сразу
            if ($fileSize <= $chunkSize) {
                $response = Http::attach('document', file_get_contents($fullPath), $fileName)
                    ->post($url, ['chat_id' => $chatId]);

                if ($response->failed()) {
                    $errorMessage = "Telegram API failed: " . $response->body() . "\nStatus: " . $response->status();
                    $this->sendMessage($errorMessage);
                } else {
                    $sentFiles[] = $fileName;
                }
                continue;
            }

            // если файл больше — режем на части
            $handle = fopen($fullPath, 'rb');
            if (!$handle) {
                $message .= $fileName . " cannot be opened\n";
                continue;
            }

            $part = 1;
            while (!feof($handle)) {
                $chunkContent = fread($handle, $chunkSize);
                if ($chunkContent === false) {
                    $message .= "Error reading chunk from $fileName\n";
                    break;
                }

                $chunkName = pathinfo($fileName, PATHINFO_FILENAME) . "-{$part}." . pathinfo($fileName, PATHINFO_EXTENSION);
                $tempPath = sys_get_temp_dir() . '/' . $chunkName;

                file_put_contents($tempPath, $chunkContent);

                $response = Http::attach('document', file_get_contents($tempPath), $chunkName)
                    ->post($url, ['chat_id' => $chatId]);

                if ($response->failed()) {
                    $errorMessage = "Telegram API failed: " . $response->body() . "\nStatus: " . $response->status();
                    $this->sendMessage($errorMessage);
                } else {
                    $sentFiles[] = $chunkName;
                }

                unlink($tempPath); // удаляем временный файл
                $part++;
            }

            fclose($handle);
        }

        if ($message) {
            $this->sendMessage($message);
        }

        return $sentFiles;
    }


    protected function sendFilesAsMediaGroup($filePath, $fileNames): array|null
    {
        try {
            $token = config('telegram.bot_token');
            $chatId = config('telegram.chat_id');
            $url = "https://api.telegram.org/bot{$token}/sendMediaGroup";
            $media = [];
            $multipart = [];
            $message = '';
            foreach ($fileNames as $index => $fileName) {
                $fullPath = $filePath . '/' . $fileName;
                if (!file_exists($fullPath) || filesize($fullPath) === 0) {
                    $message .= "File {$fileName} does not exist at path: {$fullPath}\n";
                    continue;
                }
                if (filesize($fullPath) === 0) {
                    $message .= "File {$fileName} is empty at path: {$fullPath}\n";
                    continue;
                }
                $inputName = "file{$index}";
                $media[] = [
                    'type' => 'document',
                    'media' => "attach://{$inputName}",
                ];
                $multipart[] = [
                    'name' => $inputName,
                    'contents' => fopen($fullPath, 'r'),
                    'filename' => $fileName,
                ];
            }
            if (empty($media))
                return null;
            $multipart[] = [
                'name' => 'media',
                'contents' => json_encode($media),
            ];
            $response = Http::attach($multipart)->post($url, [
                'chat_id' => $chatId,
            ]);
            if ($message)
                $this->sendMessage($message);

            if ($response->failed()) {
                $errorMessage = "Telegram API failed: " . $response->body() . "\nStatus: " . $response->status();
                $this->sendMessage($errorMessage);
                return null;
            }
            $response = $response->body();
            $response = json_decode($response);
            $sentFiles = [];

            foreach ($response->result as $file) {
                array_push($sentFiles, $file->document->file_name);
            }
            return $sentFiles;
        } catch (\Exception $e) {
            return null;
        }
    }
}
