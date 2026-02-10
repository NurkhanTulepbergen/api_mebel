<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Flysystem\Filesystem;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

class UploadFileToFtp implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $localFile;
    protected $remoteFilePath;
    protected $fileName;
    protected $ftpConnection;

    public function __construct($localFile, $remoteFilePath, $fileName, $ftpConnection) {
        $this->localFile = $localFile;
        $this->remoteFilePath = $remoteFilePath;
        $this->fileName = $fileName;
        $this->ftpConnection = $ftpConnection;
    }

    public function handle(): void
    {
        // dd($this->localFile, $this->remoteFilePath, $this->fileName, $this->ftpConnection);
        $connectionOptions = FtpConnectionOptions::fromArray([
            'host'     => 'w01da567.kasserver.com',
            'root'     => '/',
            'username' => 'f0176050',
            'password' => 'P6wBdgjtsSkTxmNZxshC',
            'port'     => 21,
            'passive'  => true,
            'timeout'  => 30,
        ]);

        $adapter    = new FtpAdapter($connectionOptions);
        $filesystem = new Filesystem($adapter);

        $contents = Storage::get($this->localFile);
        $filesystem->write($this->remoteFilePath.'/'.$this->fileName, $contents);
    }
}
