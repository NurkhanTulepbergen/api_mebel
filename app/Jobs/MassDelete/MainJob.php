<?php

namespace App\Jobs\MassDelete;

use App\Http\Traits\ProductRemoveTrait;
use App\Imports\ProductImport;
use App\Models\Domain;
use App\Models\Log as LogModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * Coordinates the lifecycle of a mass delete run:
 *  - Collects EAN inputs (from Excel or manual list)
 *  - Schedules chunk jobs per selected domain
 *  - Tracks progress and completion via Redis/logging
 */
class MainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, ProductRemoveTrait, SerializesModels;

    protected $filePath;

    protected $base_ids;

    protected $userId;

    protected $bases;

    protected $jobsCount = 0;

    protected $log;
    protected $eans;

    public function __construct($filePath, $eans, $base_ids, $userId)
    {
        $this->filePath = $filePath;
        $this->eans = $eans;
        $this->base_ids = $base_ids;
        $this->userId = $userId;
    }

    /**
     * Execute the job and orchestrate sub-tasks.
     */
    public function handle(): void
    {
        try {
            if($this->alreadyRunning())
                return;
            $this->initLog();
            $this->bases = $this->getDomains();
            Log::channel('mass-delete')->info('-=-=-=-=-=-=-=-= Process Started =-=-=-=-=-=-=-=-');
            $this->initializeRedis();
            $this->getEans();
            if(!$this->isLogEmpty()) {
                $this->processProducts();
                $this->waitForAllChunksToFinish();
            }
            else $this->deleteLog();
            $this->cleanup();

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Prevent overlapping runs within a short window.
     */
    private function alreadyRunning() {
        return LogModel::where('status', 'in_progress')
            ->where('created_at', '>=', Carbon::now()->subMinutes(15))
            ->exists();
    }

    /**
     * Create log record and associate selected domains.
     */
    private function initLog()
    {
        $this->log = LogModel::create([
            'type' => 'mass-delete',
            'user_id' => $this->userId,
        ]);

        $this->log->domains()->attach($this->base_ids);
    }

    /**
     * Persist EANs from file or manual input to the log.
     */
    private function getEans() {
        if($this->filePath) {
            $fullPath = $this->getFullPath();
            $this->validateFileExists($fullPath);
            $this->importExcel($fullPath);
        }
        else {
            $insertBatch = [];
            foreach($this->eans as $ean) {
                $insertBatch[] = [
                    'key_type' => 'ean',
                    'key' => $ean,
                ];
            }
            $this->log->inputs()->createMany($insertBatch);
        }
    }

    /**
     * Resolve domains selected by the user along with DB connections.
     */
    private function getDomains()
    {
        return Domain::with('database')->whereIn('id', $this->base_ids)->get();
    }

    /**
     * Build absolute disk path to uploaded Excel file.
     */
    private function getFullPath(): string
    {
        return storage_path('app/public/'.$this->filePath);
    }

    /**
     * Guard against missing temporary files.
     */
    private function validateFileExists(string $fullPath): void
    {
        if (! File::exists($fullPath))
            throw new \Exception('Файл не найден: '.$fullPath);
    }

    /**
     * Reset Redis keys that power the progress screen.
     */
    private function initializeRedis(): void
    {
        Redis::del(['mass_delete_job:messages', 'mass_delete_job:status']);
        Redis::set('job_mass_delete_count', 0);
        Redis::set('mass_delete_job:status', 'started');
        Redis::set('mass_delete_job:pending_jobs', 0);
        $this->addMessage('Initialized Redis');
    }

    /**
     * Import EANs from Excel and clean up the uploaded file.
     */
    private function importExcel(string $fullPath): void
    {
        try {
            $this->addMessage('Importing excel file');
            $import = new ProductImport($this->log);
            Excel::import($import, $fullPath);
            File::delete($fullPath);
        } catch (\Throwable $e) {
            throw new \Exception('Ошибка при импорте Excel: '.$e->getMessage());
        }
    }

    /**
     * Dispatch chunk jobs per domain for the collected EANs.
     */
    private function processProducts(): void
    {
        $this->addMessage('Parse json files');
        $this->log->inputs()
            ->orderBy('id', 'asc')
            ->chunkById(100, function ($inputs) {
                foreach ($this->bases as $base) {
                    $this->jobsCount++;
                    ChunkJob::dispatch($inputs, $base);
                }
            });
    }

    /**
     * Remove temporary artifacts and expire Redis keys on completion.
     */
    private function cleanup(): void
    {
        try {
            Storage::disk('public')->deleteDirectory('uploads/job_mass_delete');
        } catch (\Throwable $e) {
            throw new \Exception('Не удалось удалить временную директорию: '.$e->getMessage());
        }
        $this->log->update([
            'status' => 'done',
            'finished_at' => Carbon::now(),
        ]);
        Redis::del(['mass_delete_job:pending_jobs', 'job_mass_delete_count']);
        Redis::expire('mass_delete_job:messages', 60);
        Redis::expire('mass_delete_job:status', 60);
    }

    /**
     * Centralized exception handling & logging.
     */
    private function handleException(\Exception $e): void
    {
        Log::channel('mass-delete')->error('Ошибка в ProductMassDeleteViaExcelJob: '.$e->getMessage(), [
            'file' => $this->filePath,
            'stack' => $e->getTraceAsString(),
        ]);
        $this->log->update([
            'status' => 'error',
            'finished_at' => Carbon::now(),
        ]);
        $this->fail($e);
    }

    /**
     * Poll Redis until all chunk jobs report completion or timeout.
     */
    private function waitForAllChunksToFinish(int $timeoutSeconds = 60 * 60): void {
        $start = time();

        while (true) {
            $currentJobsCount = (int) Redis::get('mass_delete_job:pending_jobs');

            if ($currentJobsCount == $this->jobsCount) {
                Redis::set('mass_delete_job:status', 'done');
                $this->log->update([
                    'status' => 'done',
                    'finished_at' => Carbon::now(),
                ]);
                break;
            }

            if ((time() - $start) > $timeoutSeconds) {
                $this->log->update([
                    'status' => 'timeout',
                    'finished_at' => Carbon::now(),
                ]);
                Redis::set('mass_delete_job:status', 'timeout');
                Redis::rpush('mass_delete_job:messages', 'Превышено время ожидания завершения подзадач.');
                break;
            }

            sleep(1);
        }
    }

    /**
     * Append a human-readable message to the job progress feed.
     */
    private function addMessage($message): void
    {
        Redis::rpush('mass_delete_job:messages', $message);
    }

    /**
     * Mark the job as failed inside Redis and write message to the feed.
     */
    private function setError($message): void
    {
        Redis::set('mass_delete_job:status', 'error');
        $this->addMessage($message);
    }
}
