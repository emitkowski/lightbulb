<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawSignalResource\Pages;
use App\Models\RawSignal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RawSignalResource extends Resource
{
    protected static ?string $model = RawSignal::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Raw Signals';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews'])
                    ->required(),
                Forms\Components\TextInput::make('source_id')->maxLength(255),
                Forms\Components\TextInput::make('source_url')->maxLength(255)->url(),
                Forms\Components\TextInput::make('title')->maxLength(255)->columnSpanFull(),
                Forms\Components\Textarea::make('content')->required()->columnSpanFull()->rows(6),
                Forms\Components\TextInput::make('author')->maxLength(255),
                Forms\Components\TextInput::make('score')->numeric()->default(0),
                Forms\Components\TextInput::make('comment_count')->numeric()->default(0),
                Forms\Components\TextInput::make('category')->maxLength(255),
                Forms\Components\Toggle::make('processed'),
                Forms\Components\Toggle::make('flagged'),
                Forms\Components\DateTimePicker::make('published_at'),
            ]);
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
                Tables\Columns\TextColumn::make('title')
                    ->limit(80)
                    ->searchable()
                    ->tooltip(fn (RawSignal $record) => $record->title),
                Tables\Columns\TextColumn::make('score')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('comment_count')
                    ->label('Comments')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category')
                    ->searchable(),
                Tables\Columns\IconColumn::make('processed')->boolean(),
                Tables\Columns\TextColumn::make('ingestionRun.query')
                    ->label('Ingestion Query')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('ingestionRun.created_at')
                    ->label('Ingested At')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('Posted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options(['reddit' => 'Reddit', 'hackernews' => 'HackerNews']),
                Tables\Filters\SelectFilter::make('ingestion_run')
                    ->label('Ingestion Run')
                    ->relationship('ingestionRun', 'query'),
                Tables\Filters\TernaryFilter::make('processed'),
                Tables\Filters\TernaryFilter::make('flagged'),
                Tables\Filters\Filter::make('high_score')
                    ->label('Score ≥ 100')
                    ->query(fn ($query) => $query->where('score', '>=', 100)),
            ])
            ->actions([
                Tables\Actions\Action::make('view_content')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->modalContent(fn (RawSignal $record) => view('filament.modals.raw-signal-content', ['signal' => $record]))
                    ->modalHeading(fn (RawSignal $record) => $record->title ?? 'Signal Content')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
                Tables\Actions\Action::make('toggle_flagged')
                    ->label(fn (RawSignal $record) => $record->flagged ? 'Unflag' : 'Flag')
                    ->icon('heroicon-o-flag')
                    ->color(fn (RawSignal $record) => $record->flagged ? 'gray' : 'warning')
                    ->action(fn (RawSignal $record) => $record->update(['flagged' => ! $record->flagged])),
                Tables\Actions\Action::make('mark_processed')
                    ->label('Mark Processed')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RawSignal $record) => ! $record->processed)
                    ->action(fn (RawSignal $record) => $record->update(['processed' => true])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_processed')
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
            'index' => Pages\ListRawSignals::route('/'),
            'create' => Pages\CreateRawSignal::route('/create'),
            'edit' => Pages\EditRawSignal::route('/{record}/edit'),
        ];
    }
}
