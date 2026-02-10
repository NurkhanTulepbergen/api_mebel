<?php

namespace App\Filament\Resources\UserCredentialResource\Pages;

use App\Filament\Resources\UserCredentialResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserCredential extends EditRecord
{
    protected static string $resource = UserCredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
