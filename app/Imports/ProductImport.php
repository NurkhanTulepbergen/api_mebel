<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Importable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use App\Models\LogInput;

class ProductImport implements ToArray, WithChunkReading
{
    use Importable;
    protected $log;
    public function __construct($log) {
        $this->log = $log;
    }

    public function array(array $rows)
    {
        $filteredRows = array_filter($rows, function ($row) {
            // Удаляет строки, где всё пусто
            if (empty(array_filter($row))) {
                return false;
            }

            // Удаляет строки, где нет EAN в первой колонке
            return isset($row[0]) && !is_null($row[0]) && trim($row[0]) !== '';
        });


        // Теперь извлекаем EAN из отфильтрованных строк
        $eanList = array_column($filteredRows, 0);


        // Пропускаем пустой чанк
        if (empty($eanList))
            return;

        $insertBatch = [];

        $now   = date('Y-m-d H:i:s');       // быстрее, чем Carbon в массивах
        $logId = $this->log->getKey();      // FK, который обычно ставит relation

        $rows = array_map(
            static fn($ean) => [
                'log_id'     => $logId,
                'key_type'   => 'ean',
                'key'        => $ean,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $eanList
        );

        LogInput::insert($rows);

        Log::channel('mass-delete')->info(
            "Сбор ean с Excel:",
            [
                'eanList' => $eanList,
                'log_id' => $this->log->id,
                'insertBatch' => $insertBatch,
            ]
        );
    }

    public function chunkSize(): int
    {
        return 100;
    }
}
