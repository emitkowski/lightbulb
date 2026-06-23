<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IngestionRunResource\Pages;
use App\Models\IngestionRun;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IngestionRunResource extends Resource
{
    protected static ?string $model = IngestionRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Ingestion Runs';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'warning' => 'reddit',
                        'info' => 'hackernews',
                    ]),
                Tables\Columns\TextColumn::make('query')
                    ->limit(60)
                    ->searchable(),
                Tables\Columns\TextColumn::make('signals_found')
                    ->label('Found')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signals_inserted')
                    ->label('Inserted')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('signals_skipped')
                    ->label('Skipped')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'success',
                        'danger' => 'failed',
                        'warning' => 'partial',
                    ]),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration (ms)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Run at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['success' => 'Success', 'failed' => 'Failed', 'partial' => 'Partial']),
            ])
            ->actions([
                Tables\Actions\Action::make('view_signals')
                    ->label('Signals')
                    ->icon('heroicon-o-signal')
                    ->url(fn (IngestionRun $record) => \App\Filament\Resources\RawSignalResource::getUrl('index', ['tableFilters[ingestion_run][value]' => $record->id]))
                    ->visible(fn (IngestionRun $record) => $record->signals_inserted > 0),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIngestionRuns::route('/'),
        ];
    }
}
