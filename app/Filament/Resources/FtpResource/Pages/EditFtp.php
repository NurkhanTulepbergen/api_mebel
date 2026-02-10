<?php

namespace App\Filament\Resources\FtpResource\Pages;

use App\Filament\Resources\FtpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFtp extends EditRecord
{
    protected static string $resource = FtpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
