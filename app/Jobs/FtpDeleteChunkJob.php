<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Concurrency;
use App\Models\Domain;

class FtpDeleteChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $eanList;
    protected string $base;
    protected string $databaseName;
    protected string $ftpName;
    protected Domain $domain;
    protected array $failedPaths = [];

    public int $concurrency = 10;

    public function __construct(array $eanList, string $base)
    {
        $this->eanList = $eanList;
        $this->base = $base;
        $uuid = (string) \Str::uuid();
        $this->databaseName = 'db_'.$uuid;
        $this->ftpName = 'ftp_'.$uuid;
    }

    public function handle(): void
    {
        try {
            $this->domain = Domain::with(['ftps','databases'])
                ->where('name', $this->base)
                ->withCount(['ftps','databases'])
                ->first();

            $this->setupDatabase();
            $this->setupFtp();

            $paths = $this->getImagePaths();
            if (empty($paths)) {
                Log::channel('mass-delete')->info("Нет файлов для удаления в базе {$this->base}");
                return;
            }

            $closures = collect($paths)->map(fn(string $path) => function () use ($path) {
                if (Storage::disk($this->ftpName)->exists($path)) {
                    Storage::disk($this->ftpName)->delete($path);
                    return ['path' => $path, 'deleted' => true];
                }
                return ['path' => $path, 'deleted' => false];
            })->all();

            $results = Concurrency::driver('process')
                ->run($closures, concurrency: $this->concurrency);

            $success = 0;
            foreach ($results as $res) {
                if (isset($res['deleted']) && $res['deleted']) {
                    $success++;
                } else {
                    $this->failedPaths[] = $res['path'];
                }
            }

            Log::channel('mass-delete')->info("FTP‑удаление завершено", [
                'base' => $this->base,
                'total' => count($paths),
                'deleted' => $success,
                'failed' => count($this->failedPaths),
                'failed_paths' => $this->failedPaths,
            ]);

        } catch (\Exception $e) {
            $this->handleException($e);
        }
    }

    private function setupDatabase(): void
    {
        if ($this->domain->databases_count === 0) {
            throw new \Exception("Database для базы {$this->base} не найден");
        }
        $db = $this->domain->databases[0];
        config(["database.connections.{$this->databaseName}" => [
            'driver'    => $db->driver,
            'host'      => $db->host,
            'port'      => $db->port,
            'database'  => $db->database,
            'username'  => $db->username,
            'password'  => $db->password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]]);
    }

    private function setupFtp(): void
    {
        if ($this->domain->ftps_count === 0) {
            throw new \Exception("FTP для базы {$this->base} не найден");
        }
        $ftp = $this->domain->ftps[0];
        config(["filesystems.disks.{$this->ftpName}" => [
            'driver'   => $ftp->protocol,
            'host'     => $ftp->host,
            'username' => $ftp->username,
            'password' => $ftp->password,
            'root'     => $ftp->path,
            'port'     => $ftp->port,
            'timeout'  => 30,
            'passive'  => true,
        ]]);
    }

    private function getImagePaths(): array
    {
        $productNumbers = DB::connection($this->databaseName)
            ->table('shopartikel')
            ->whereIn('ean', $this->eanList)
            ->pluck('artikelnr')
            ->toArray();



        $images = DB::connection($this->databaseName)
            ->table('shopmedia')
            ->whereIn('key', $productNumbers)
            ->where('art', 'artikel')
            ->get();

        Log::channel('mass-delete')->error("SQL:", [
            'base' => $this->base,
            'productNumbers' => $productNumbers,
            'images' => $images,
        ]);

        return $images->map(fn($img) => $this->buildPath($img))->toArray();
    }

    private function buildPath($img): string
    {
        $type = $img->typ;
        $filename = "{$img->dateiname}.{$img->endung}";
        $path = match ($type) {
            'zg' => "z/{$img->key}g/{$filename}",
            'z'  => "z/{$img->key}/{$filename}",
            default => "{$type}/{$filename}",
        };
        return $path;
    }

    private function handleException(\Throwable $e): void
    {
        Log::channel('mass-delete')->error("Ошибка FtpDeleteChunkJob: {$e->getMessage()}", [
            'base' => $this->base,
            'eanList' => $this->eanList,
            'stack' => $e->getTraceAsString(),
        ]);
        $this->fail($e);
    }
}
