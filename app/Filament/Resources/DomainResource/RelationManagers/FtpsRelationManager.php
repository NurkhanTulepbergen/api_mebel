<?php

namespace App\Filament\Resources\DomainResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Filament\Resources\FtpResource;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

class FtpsRelationManager extends RelationManager
{
    protected static string $relationship = 'ftps';
    public function isReadOnly(): bool {
        return false;
    }

    public function form(Form $form): Form {
        return $form
            ->schema([
                Forms\Components\TextInput::make('ftp')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('host'),
                Tables\Columns\TextColumn::make('username'),
                Tables\Columns\TextColumn::make('protocol'),
                Tables\Columns\TextColumn::make('password'),
            ])
            ->headerActions([
                CreateAction::make()
                    ->url(fn ($livewire) => FtpResource::getUrl('create', [
                        'ownerRecord' => $livewire->ownerRecord->getKey(),
                    ])),
            ])
            ->actions([
                EditAction::make()
                    ->url(fn ($record) => FtpResource::getUrl('edit', ['record' => $record])),
                ViewAction::make()
                    ->url(fn ($record) => FtpResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
