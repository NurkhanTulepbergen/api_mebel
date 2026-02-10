<?php

namespace App\Filament\Resources\FtpResource\Pages;

use App\Filament\Resources\FtpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFtps extends ListRecords
{
    protected static string $resource = FtpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
