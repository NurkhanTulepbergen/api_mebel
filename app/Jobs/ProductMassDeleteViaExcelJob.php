<?php
namespace App\Jobs;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ProductImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

use App\Http\Traits\ProductRemoveTrait;

class ProductMassDeleteViaExcelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels, ProductRemoveTrait;

    protected $filePath;
    protected $bases;

    public function __construct($filePath, $bases)
    {
        $this->filePath = $filePath;
        $this->bases = $bases;
    }

    public function handle(): void
    {
        try {
            Log::channel('mass-delete')->info('Bases', [
                'bases' => $this->bases,
            ]);
            $fullPath = $this->getFullPath();
            $this->validateFileExists($fullPath);

            $this->initializeRedis();
            $this->importExcel($fullPath);
            $jsonFiles = $this->getJsonChunkFiles();
            $this->processProducts($jsonFiles);
            $this->waitForAllChunksToFinish();
            $this->cleanup();

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function getFullPath(): string
    {
        return storage_path('app/public/' . $this->filePath);
    }

    private function validateFileExists(string $fullPath): void
    {
        if (!File::exists($fullPath)) {
            throw new \Exception('Файл не найден: ' . $fullPath);
        }
    }

    private function initializeRedis(): void
    {
        Redis::set("job_mass_delete_count", 0);
        Redis::hmset('mass_delete_job', [
            'status' => 'Started',
            'messages' => json_encode([]),
        ]);
        $this->addMessage('Initialized Redis');
    }


    private function importExcel(string $fullPath): void
    {
        try {
            $this->addMessage('Importing excel file');
            $import = new ProductImport();
            Excel::import($import, $fullPath);
            File::delete($fullPath);
        } catch (\Throwable $e) {
            throw new \Exception("Ошибка при импорте Excel: " . $e->getMessage());
        }
    }


    private function getJsonChunkFiles(): array
    {
        $files = Storage::disk('public')->files("uploads/job_mass_delete");
        return $files;
    }


    private function processProducts(array $jsonFiles): void
    {
        $this->addMessage('Parse json files');
        foreach ($jsonFiles as $filePath) {
            $jsonContent = Storage::disk('public')->get($filePath);
            $eanList = json_decode($jsonContent, true);

            foreach ($this->bases as $base) {
                MassDeleteChunkJob::dispatch($eanList, $base);
            }
        }
    }


    private function cleanup(): void
    {
        try {
            Storage::disk('public')->deleteDirectory("uploads/job_mass_delete");
        } catch (\Throwable $e) {
            throw new \Exception("Не удалось удалить временную директорию: " . $e->getMessage());
        }
        Redis::del(['mass_delete_job:messages', 'mass_delete_job:status', 'mass_delete_job',]);
    }

    private function handleException(\Exception $e): void
    {
        Log::channel('mass-delete')->error('Ошибка в ProductMassDeleteViaExcelJob: ' . $e->getMessage(), [
            'file' => $this->filePath,
            'stack' => $e->getTraceAsString(),
        ]);
        $this->fail($e);
    }

    private function waitForAllChunksToFinish(int $timeoutSeconds = 60 * 60): void
    {
        $start = time();

        while (true) {
            $pending = (int) Redis::get('mass_delete_job:pending_jobs');

            if ($pending === 0) {
                Redis::set('mass_delete_job:status', 'done');
                break;
            }

            if ((time() - $start) > $timeoutSeconds) {
                Redis::set('mass_delete_job:status', 'timeout');
                Redis::rpush('mass_delete_job:messages', 'Превышено время ожидания завершения подзадач.');
                break;
            }

            sleep(1);
        }
    }


    private function addMessage($message): void
    {
        Redis::rpush('mass_delete_job:messages', $message);
    }

    private function setError($message): void
    {
        Redis::set('mass_delete_job:status', 'error');
        $this->addMessage($message);
    }
}
