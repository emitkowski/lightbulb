<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use App\Filament\Resources\IngestionRunResource\Pages\ListIngestionRuns;
use App\Filament\Resources\IngestionRunResource\Pages;
use App\Models\IngestionRun;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IngestionRunResource extends Resource
{
    protected static ?string $model = IngestionRun::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'Ingestion Runs';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('source')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'reddit' => 'warning',
                        'hackernews' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('query')
                    ->limit(60)
                    ->searchable(),
                TextColumn::make('signals_found')
                    ->label('Found')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('signals_inserted')
                    ->label('Inserted')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('signals_skipped')
                    ->label('Skipped')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'failed' => 'danger',
                        'partial' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('duration_ms')
                    ->label('Duration (ms)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Run at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews']),
                SelectFilter::make('status')
                    ->options(['success' => 'Success', 'failed' => 'Failed', 'partial' => 'Partial']),
            ])
            ->recordActions([
                Action::make('view_signals')
                    ->label('Signals')
                    ->icon('heroicon-o-signal')
                    ->url(fn (IngestionRun $record) => RawSignalResource::getUrl('index', ['filters[ingestion_run][value]' => $record->id]))
                    ->visible(fn (IngestionRun $record) => $record->signals_inserted > 0),
            ])
            ->toolbarActions([]);
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
            'index' => ListIngestionRuns::route('/'),
        ];
    }
}
