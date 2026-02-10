<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Http\Traits\ProductRemoveTrait;
use Illuminate\Support\Facades\Redis;

class MassDeleteChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, ProductRemoveTrait;

    protected array $eanList;
    protected string $base;

    public function __construct(array $eanList, string $base) {
        $this->eanList = $eanList;
        $this->base = $base;
    }


    public function handle(): void {
        try {
            if ($this->getBase() == 'jv')
                $ids = $this->massDeleteJV($this->eanList, $this->base);
            else
                $ids = $this->massDeleteXl($this->eanList, $this->base);
            $strIds = implode(', ', $ids);
            $count = count($ids);
            $this->addMessage("From {$this->base} deleted {$count} products: [{$strIds}]");


        } catch (\Exception $e) {
            Log::channel('mass-delete-log')->error("Ошибка в MassDeleteChunkJob: " . $e->getMessage(), [
                'base' => $this->base,
            ]);
        }
    }

    private function getBase() {
        return str_contains($this->base, 'jv') ? 'jv' : 'xl';
    }

    private function addMessage($message): void {
        Redis::rpush('mass_delete_job:messages', $message);
    }
}
