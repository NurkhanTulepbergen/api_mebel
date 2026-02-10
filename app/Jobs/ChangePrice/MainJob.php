<?php

namespace App\Jobs\ChangePrice;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\EanWithPriceImport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Models\Log as LogModel;

use App\Models\{
    Currency,
    Domain,
};
use Carbon\Carbon;
use App\Http\Traits\CurrencyTrait;
use App\Http\Traits\CosmoShop\CookieTrait;

/**
 * Coordinates the change-price workflow end to end:
 *  - Loads selected domains and refreshes currency rates when needed
 *  - Imports EAN/price pairs from Excel or manual input
 *  - Dispatches chunk jobs to update prices per domain
 *  - Tracks progress/status via Redis and the logs table
 */
class MainJob implements ShouldQueue
{
    use Queueable, CurrencyTrait, CookieTrait;

    protected $filePath;
    protected $baseIds;
    protected $products;
    protected $domains;
    protected $jobsCount = 0;
    protected $userId;
    protected $log;

    /**
     * @param string|null $filePath  Uploaded Excel path relative to the public disk
     * @param array       $products  Manual EAN/price input fallback
     * @param array       $baseIds   Selected domain identifiers
     * @param int|null    $userId    Initiating user
     */
    public function __construct($filePath, $products, $baseIds, $userId)
    {
        $this->filePath = $filePath;
        $this->products = $products;
        $this->baseIds = $baseIds;
        $this->userId = $userId;
    }

    /**
     * Execute the job, orchestrating import, chunking and completion logic.
     */
    public function handle(): void
    {
        try {
            Log::channel('change-price')->info('-=-=-=-=-=-=-= Started Process =-=-=-=-=-=-=-');
            $this->initLog();
            $this->getDomains();
            $this->handleCurrencies();
            $fullPath = $this->getFullPath();
            $this->initializeRedis();
            $this->validateFileExists($fullPath);
            $this->importExcel($fullPath);
            // $jsonFiles = $this->getJsonChunkFiles();
            $this->processProducts();
            $this->waitForAllChunksToFinish();
            $this->cleanup();

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    /**
     * Create log record and associate chosen domains.
     */
    private function initLog(): void
    {
        $this->log = LogModel::create([
            'type' => 'change-price',
            'user_id' => $this->userId,
        ]);

        $this->log->domains()->attach($this->baseIds);
    }

    /**
     * Load domains with all required relations for price updates.
     */
    private function getDomains(): void
    {
        $this->domains = Domain::with('database', 'currency', 'userCredentials', 'cookies')
            ->whereIn('id', $this->baseIds)
            ->get();
    }

    /**
     * Refresh currency rates when they are stale.
     */
    private function handleCurrencies(): void
    {
        $currencyLastUpdate = Currency::getLastUpdate();

        if ($currencyLastUpdate > 1) {
            $this->addMessage("Currencies was updated {$currencyLastUpdate} hours ago. Getting new prices");
            Log::channel('change-price')->info('Currency:', [
                'currencyLastUpdate' => "{$currencyLastUpdate} hours"
            ]);
            $this->updateCurrencyRates();
        }
    }

    /**
     * Build absolute filesystem path to the uploaded Excel file.
     */
    private function getFullPath(): string
    {
        return storage_path('app/public/' . $this->filePath);
    }

    /**
     * Ensure the uploaded Excel file exists before processing.
     *
     * @throws \Exception
     */
    private function validateFileExists(string $fullPath): void
    {
        if (!File::exists($fullPath)) {
            throw new \Exception('Файл не найден: ' . $fullPath);
        }
    }

    /**
     * Reset Redis tracking keys for a fresh run.
     */
    private function initializeRedis(): void
    {
        Redis::del(['change_price_job:messages', 'change_price_job:status']);

        Redis::set("job_change_price_count", 0);
        Redis::set('change_price_job:status', 'started');
        Redis::set('change_price_job:pending_jobs', 0);
        $this->addMessage('Initialized Redis');
    }


    /**
     * Import the Excel file and persist rows into the log inputs table.
     *
     * @throws \Exception
     */
    private function importExcel(string $fullPath): void
    {
        try {
            $this->addMessage('Importing excel file');
            $import = new EanWithPriceImport($this->log);
            Excel::import($import, $fullPath);
            File::delete($fullPath);
        } catch (\Throwable $e) {
            throw new \Exception("Ошибка при импорте Excel: " . $e->getMessage());
        }
    }

    /**
     * List generated JSON chunk files (debug helper).
     */
    private function getJsonChunkFiles(): array
    {
        $files = Storage::disk('public')->files("uploads/job_change_price");
        return $files;
    }


    /**
     * Dispatch chunk jobs for each set of 100 imported rows per domain.
     */
    private function processProducts(): void
    {
        $this->addMessage('Parse json files');
        $this->log->inputs()
            ->orderBy('id')
            ->chunkById(100, function ($inputs) {
                $products = [];
                foreach($inputs as $input) {
                    $products[] = [
                        'ean' => $input->key,
                        'price' => (float) $input->provided_value,
                        'log_input_id' => $input->id,
                    ];
                }
                foreach ($this->domains as $domain) {
                    $this->jobsCount++;
                    ProcessChunkJob::dispatch($products, $domain, $this->log);
                }
            });
    }

    /**
     * Remove temporary storage artifacts and expire Redis keys.
     *
     * @throws \Exception
     */
    private function cleanup(): void
    {
        try {
            Storage::disk('public')->deleteDirectory("uploads/job_change_price");
        } catch (\Throwable $e) {
            throw new \Exception("Не удалось удалить временную директорию: " . $e->getMessage());
        }
        Redis::del(['change_price_job:pending_jobs', 'job_change_price_count']);
        Redis::expire('change_price_job:messages', 60);
        Redis::expire('change_price_job:status', 60);
        $this->log?->update([
            'status' => 'done',
            'finished_at' => Carbon::now(),
        ]);
    }

    /**
     * Log an error and mark the job as failed.
     */
    private function handleException(\Exception $e): void
    {
        Log::channel('change-price')->error('Ошибка в change price job: ' . $e->getMessage(), [
            'file' => $this->filePath,
            'stack' => $e->getTraceAsString(),
        ]);
        $this->log?->update([
            'status' => 'error',
            'finished_at' => Carbon::now(),
        ]);
        $this->fail($e);
    }

    /**
     * Wait until all chunk jobs finish or the timeout is exceeded.
     *
     * @param int $timeoutSeconds
     */
    private function waitForAllChunksToFinish(int $timeoutSeconds = 60 * 60): void
    {
        $start = time();
        Log::channel('change-price')->error('change price job wait: ', [
            'jobsCount' => $this->jobsCount,
        ]);
        while (true) {
            $currentJobsCount = (int) Redis::get('change_price_job:pending_jobs');

            if ($currentJobsCount == $this->jobsCount) {
                Redis::set('change_price_job:status', 'done');
                $this->log?->update([
                    'status' => 'done',
                    'finished_at' => Carbon::now(),
                ]);
                break;
            }

            if ((time() - $start) > $timeoutSeconds) {
                Redis::set('change_price_job:status', 'timeout');
                Redis::rpush('change_price_job:messages', 'Превышено время ожидания завершения подзадач.');
                $this->log?->update([
                    'status' => 'timeout',
                    'finished_at' => Carbon::now(),
                ]);
                break;
            }

            sleep(1);
        }
    }

    /**
     * Append a message to the job progress feed.
     */
    private function addMessage($message): void
    {
        Redis::rpush('change_price_job:messages', $message);
    }

    /**
     * Mark job as errored in Redis and push a message.
     */
    private function setError($message): void
    {
        Redis::set('change_price_job:status', 'error');
        $this->addMessage($message);
    }
}
