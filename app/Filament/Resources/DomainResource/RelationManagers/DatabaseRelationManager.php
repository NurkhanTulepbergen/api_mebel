<?php

namespace App\Filament\Resources\DomainResource\RelationManagers;

use App\Models\Database;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\{
    Action,
    CreateAction,
    EditAction,
};

class DatabaseRelationManager extends RelationManager
{
    protected static string $relationship = 'database';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('host')->required(),
            Forms\Components\TextInput::make('database')->required(),
            Forms\Components\TextInput::make('username')->required(),
            Forms\Components\TextInput::make('password')->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->columns([
            Tables\Columns\TextColumn::make('host'),
            Tables\Columns\TextColumn::make('database'),
            Tables\Columns\TextColumn::make('username'),
            Tables\Columns\TextColumn::make('password'),
        ])
        ->headerActions([
            CreateAction::make()
                ->visible(fn ($livewire) => $livewire->ownerRecord->database === null)
                ->mutateFormDataUsing(function (array $data, $livewire) {
                    $data['domain_id'] = $livewire->ownerRecord->getKey();
                    return $data;
                }),
        ])
        ->actions([
            EditAction::make()
                ->visible(fn ($record) => $record !== null),

            Action::make('goToExternal')
                ->label('Open')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url(fn ($record) => "https://{$record->host}/mysqladmin/?auth_tag=" . $record->domain->name)
                ->openUrlInNewTab()
                ->visible(fn ($record) => $record !== null),
        ])
        ->emptyStateActions([
            CreateAction::make()
                ->visible(fn ($livewire) => $livewire->ownerRecord->database === null)
                ->mutateFormDataUsing(function (array $data, $livewire) {
                    $data['domain_id'] = $livewire->ownerRecord->getKey();
                    return $data;
                }),
        ]);
    }
}
