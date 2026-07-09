<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use App\Filament\Resources\RawSignalResource\Pages\ListRawSignals;
use App\Filament\Resources\RawSignalResource\Pages\CreateRawSignal;
use App\Filament\Resources\RawSignalResource\Pages\EditRawSignal;
use App\Filament\Resources\RawSignalResource\Pages;
use App\Models\RawSignal;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RawSignalResource extends Resource
{
    protected static ?string $model = RawSignal::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Raw Signals';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews'])
                    ->required(),
                TextInput::make('source_id')->maxLength(255),
                TextInput::make('source_url')->maxLength(255)->url(),
                TextInput::make('title')->maxLength(255)->columnSpanFull(),
                Textarea::make('content')->required()->columnSpanFull()->rows(6),
                TextInput::make('author')->maxLength(255),
                TextInput::make('score')->numeric()->default(0),
                TextInput::make('comment_count')->numeric()->default(0),
                TextInput::make('category')->maxLength(255),
                Toggle::make('processed'),
                Toggle::make('flagged'),
                DateTimePicker::make('published_at'),
            ]);
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
                TextColumn::make('title')
                    ->limit(80)
                    ->searchable()
                    ->tooltip(fn (RawSignal $record) => $record->title),
                TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('comment_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('category')
                    ->searchable(),
                IconColumn::make('processed')->boolean(),
                TextColumn::make('ingestionRun.query')
                    ->label('Ingestion Query')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('ingestionRun.created_at')
                    ->label('Ingested At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                TextColumn::make('published_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews']),
                SelectFilter::make('ingestion_run')
                    ->label('Ingestion Run')
                    ->relationship('ingestionRun', 'query'),
                TernaryFilter::make('processed'),
                TernaryFilter::make('flagged'),
                Filter::make('high_score')
                    ->label('Score ≥ 100')
                    ->query(fn ($query) => $query->where('score', '>=', 100)),
            ])
            ->recordActions([
                Action::make('view_content')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (RawSignal $record) => view('filament.modals.raw-signal-content', ['signal' => $record]))
                    ->modalHeading(fn (RawSignal $record) => $record->title ?? 'Signal Content')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Action::make('toggle_flagged')
                    ->label(fn (RawSignal $record) => $record->flagged ? 'Unflag' : 'Flag')
                    ->icon('heroicon-o-flag')
                    ->color(fn (RawSignal $record) => $record->flagged ? 'gray' : 'warning')
                    ->action(fn (RawSignal $record) => $record->update(['flagged' => ! $record->flagged])),
                Action::make('mark_processed')
                    ->label('Mark Processed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RawSignal $record) => ! $record->processed)
                    ->action(fn (RawSignal $record) => $record->update(['processed' => true])),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_processed')
                        ->label('Mark Processed')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['processed' => true])),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRawSignals::route('/'),
            'create' => CreateRawSignal::route('/create'),
            'edit' => EditRawSignal::route('/{record}/edit'),
        ];
    }
}
