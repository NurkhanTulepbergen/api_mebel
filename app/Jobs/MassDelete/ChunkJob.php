<?php

namespace App\Jobs\MassDelete;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ProductRemoveTrait;
use Illuminate\Support\Facades\Redis;
use App\Models\{
    Domain,
    LogInput,
    Database,
};

/**
 * Processes a slice of EANs for a single domain.
 * Responsible for deleting products in the target database
 * and recording progress messages/log relations.
 */
class ChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ProductRemoveTrait;

    protected array $eanList;
    protected Domain $domain;
    protected Database $database;
    protected bool $isIncreased = false;
    protected $inputs;

    /**
     * @param \Illuminate\Support\Collection $inputs
     * @param Domain $domain
     */
    public function __construct($inputs, Domain $domain) {
        $this->inputs = $inputs;
        $this->domain = $domain;
    }

    /**
     * Delete products from the assigned domain and update Redis counters.
     */
    public function handle(): void {
        try {
            $this->database = $this->domain->database;
            $eanList = $this->inputs->pluck('key')->values()->all();
            if ($this->getBase() == 'jv')
                $processedEans = $this->massDeleteJV($eanList, $this->domain);
            else
                $processedEans = $this->massDeleteXl($eanList, $this->domain);

            $data = LogInput::whereIn('key', $processedEans)
                ->pluck('id')
                ->map(fn($id) => [
                    'log_input_id' => $id,
                    'domain_id'    => $this->domain->id,
                ])
                ->all();

            $this->domain->logInstances()->insert($data);

            $strEans = implode(', ', $processedEans);
            $productCount = count($processedEans);
            $currentJobCount = Redis::incr('mass_delete_job:pending_jobs');
            $this->isIncreased = true;
            $this->addMessage("[{$currentJobCount}] From {$this->domain->name} deleted {$productCount} products: [{$strEans}]");

        } catch (\Exception $e) {
            Log::channel('mass-delete')->error("Ошибка в MassDeleteChunkJob: ", [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            if(!$this->isIncreased)
                Redis::incr('mass_delete_job:pending_jobs');
        }
    }

    /**
     * Determine which deletion strategy to use for the domain.
     */
    private function getBase() {
        return str_contains($this->domain->name, 'jv') ? 'jv' : 'xl';
    }

    /**
     * Write progress message to Redis for live updates.
     */
    private function addMessage($message): void {
        Redis::rpush('mass_delete_job:messages', $message);
    }
}
