<?php

namespace App\Filament\Resources\FtpResource\Pages;

use App\Filament\Resources\FtpResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewFtp extends ViewRecord
{
    protected static string $resource = FtpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
