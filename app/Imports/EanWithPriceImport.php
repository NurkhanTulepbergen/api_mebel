<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

use App\Models\LogInput;

/**
 * Handles chunked ingestion of EAN/price data from Excel uploads.
 */
class EanWithPriceImport implements ToArray, WithChunkReading
{
    use Importable;

    /**
     * @var \App\Models\Log
     */
    protected $log;

    public function __construct($log) {
        $this->log = $log;
    }

    /**
     * Persist a chunk of rows into the log inputs table and store a JSON mirror.
     *
     * @param array $rows
     */
    public function array(array $rows)
    {
        $chunkIndex = (int) Redis::get("job_change_price_count");

        $filteredRows = array_filter($rows, function ($row) {
            // Удаляем полностью пустые строки
            if (empty(array_filter($row))) {
                return false;
            }

            // Проверка: EAN должен быть непустым
            if (!isset($row[0]) || is_null($row[0]) || trim($row[0]) === '') {
                return false;
            }

            // Проверка: Price должен быть числом
            if (!isset($row[1]) || !is_numeric($row[1])) {
                return false;
            }

            return true;
        });

        // Преобразуем отфильтрованные строки в массив вида ['ean' => ..., 'price' => ...]
        $result = array_map(function ($row) {
            return [
                'ean' => trim($row[0]),
                'price' => (float) $row[1],
            ];
        }, $filteredRows);

        if (empty($result))
            return;

        $insertBatch = [];
        foreach($result as $product) {
            $insertBatch[] = [
                'log_id' => $this->log->id,
                'key_type' => 'ean',
                'key' => $product['ean'],
                'provided_value' => $product['price'],
            ];
        }

        LogInput::insert($insertBatch);

        Log::channel('change-price')->info(
            "Сбор ean и price с Excel:",
            [
                'chunk_id' => $chunkIndex,
                'products' => $result,
            ]
        );

        $directory = "uploads/job_change_price";
        $fileName = "products_{$chunkIndex}.json";

        Storage::disk('public')->makeDirectory($directory);

        Storage::disk('public')->put("{$directory}/{$fileName}", json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        Redis::set("job_change_price_count", $chunkIndex + 1);
    }

    /**
     * Configure chunk size for the Excel import.
     */
    public function chunkSize(): int
    {
        return 50;
    }

}
