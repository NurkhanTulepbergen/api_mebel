<?php

namespace App\Jobs\OpenCart;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{
    Log,
    File,
    Redis,
    Storage,
    DB,
};

use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;

use App\Http\Traits\DynamicConnectionTrait;

use App\Models\Domain;

class ChangeImagesJob implements ShouldQueue
{
    use Queueable, DynamicConnectionTrait;

    protected $ean;
    protected $images;
    protected $domain;

    public function __construct($ean, array $images, Domain $domain)
    {
        $this->ean = $ean;
        $this->images = $images;
        $this->domain = $domain;
    }

    public function handle(): void
    {
        $db = DB::connection($this->domain->name);
        $adapter = new FtpAdapter(FtpConnectionOptions::fromArray([
            'host' => $this->domain->ftp->host,
            'root' => $this->domain->ftp->path,
            'username' => $this->domain->ftp->username,
            'password' => $this->domain->ftp->password,
            'port' => $this->domain->ftp->port,
            'passive' => false,
            'ssl' => false,
            'timeout' => 30,
        ]));

        $ftp = new Filesystem($adapter);

        if(!$this->isFtpConnected($ftp)) {
            Redis::hset($this->ean, $this->domain->name, 0);

            return;
        }

        $product = $db->table('oc_product')
            ->where('model', $this->ean)
            ->first();

        Log::channel('change-price')->info('images:', [
            'product' => $product,
            'ean' => $this->ean,
            'images' => $this->images,
            'domain' => $this->domain->name
        ]);

        if(!$product) {
            Redis::hset($this->ean, $this->domain->name, 0);
            return;
        }

        $id = $product->product_id;

        $db->table('oc_product_image')
            ->where('product_id', $id)
            ->delete();

        if (!$ftp->directoryExists("/{$this->ean}"))
            $ftp->createDirectory("/{$this->ean}");

        $count = 1;
        $isFirst = true;
        foreach ($this->images as $image) {
            $extension = $this->getExtension($image);
            $content = Storage::get($image);
            if($isFirst) {
                $imagePath = "{$this->ean}/{$this->ean}-0.{$extension}";
                $db->table('oc_product')
                    ->where('product_id', $id)
                    ->update([
                        'image' => "catalog/{$imagePath}"
                    ]);
                $isFirst = false;
            }
            else {
                $imagePath = "{$this->ean}/{$this->ean}-{$count}.{$extension}";
                $db->table('oc_product_image')
                    ->insert([
                        'product_id' => $id,
                        'image' => "catalog/{$imagePath}",
                        'sort_order' => $count - 1
                    ]);
                $count++;
            }
            $ftp->write($imagePath, $content);
        }

        Redis::hset($this->ean, $this->domain->name, 1);
    }

    private function getExtension($filename)
    {
        $count = substr_count($filename, '.');
        return explode('.', $filename)[$count];
    }

    private function isFtpConnected(Filesystem $ftp): bool {
        try {
            $ftp->listContents('.', false);
            return true;
        }
        catch (\Throwable $e) {
            return false;
        }
    }

}
