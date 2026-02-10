<?php

namespace App\Jobs\ChangePrice;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Models\{
    Domain,
    LogInstance,
};
use App\Http\Traits\ChangePriceTrait;

/**
 * Executes price updates for a chunk of products within a single domain.
 */
class ProcessChunkJob implements ShouldQueue
{
    use Queueable, ChangePriceTrait;

    protected $products;
    protected $domain;
    protected $log;
    public function __construct($products, Domain $domain, $log)
    {
        $this->products = $products;
        $this->domain = $domain;
        $this->log = $log;
    }

    /**
     * Apply price changes for the chunk and record results in Redis/logs.
     */
    public function handle(): void
    {
        try {
            if ($this->getDomainType() == 'jv')
                $result = $this->jvChangePrice($this->products, $this->domain);
            else
                $result = $this->xlChangePrice($this->products, $this->domain);


            $products = [];
            foreach($result as $product) {
                $products[] = [
                    'log_input_id' => $product['log_input_id'],
                    'domain_id' => $this->domain->id,
                ];
            }
            LogInstance::insert($products);

            $count = count($result);
            $currentJobCount = Redis::incr('change_price_job:pending_jobs');
            $this->addMessage(message:
                "[{$currentJobCount}] proccessed {$count} products in {$this->domain->name}"
                .$this->resultToString($result)
            );
            Log::channel('change-price')->info('Change price: ', [
                'products' => $this->products,
                'database' => $this->domain->name,
                'result' => $result
            ]);
        }
        catch(\Exception $e) {
            $result = [];
            Log::channel('change-price')->error('Change price ERROR: ', [
                'requested_products' => $this->products,
                'database' => $this->domain->name,
                'error' => $e->getTrace()
            ]);
            $currentJobCount = Redis::incr('change_price_job:pending_jobs');
        }

    }

    /**
     * Determine which change price strategy to apply.
     */
    private function getDomainType() {
        return str_contains($this->domain->name, 'jv.') ? 'jv' : 'xl';
    }

    /**
     * Append a message to the shared Redis queue.
     */
    private function addMessage($message): void {
        Redis::rpush('change_price_job:messages', $message);
    }

    /**
     * Render processed product info for debug output.
     */
    private function resultToString(array $result) {
        $response = '[';
        foreach($result as $data) {
            $response .= "{$data['ean']} - {$data['price']}, ";
        }
        $response .= ']';
        return $response;
    }
}
