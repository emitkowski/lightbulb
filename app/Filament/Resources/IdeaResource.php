<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IdeaResource\Pages;
use App\Models\Idea;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IdeaResource extends Resource
{
    protected static ?string $model = Idea::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationLabel = 'Ideas';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('score_overall', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->limit(70)
                    ->tooltip(fn ($record) => $record->title),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'scored',
                        'warning' => 'pending',
                        'info' => 'scoring',
                        'danger' => fn ($state) => in_array($state, ['gate_failed', 'discarded']),
                    ]),

                Tables\Columns\TextColumn::make('score_overall')
                    ->label('Score')
                    ->numeric()
                    ->sortable()
                    ->color(fn ($state) => match (true) {
                        $state >= 75 => 'success',
                        $state >= 60 => 'warning',
                        $state > 0 => 'danger',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('score_problem_strength')
                    ->label('Problem')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score_distribution_path')
                    ->label('Distribution')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score_competition_gap')
                    ->label('Competition')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score_build_feasibility')
                    ->label('Feasibility')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score_automability')
                    ->label('Automability')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('score_revenue_plausibility')
                    ->label('Revenue')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('source_signals_count')
                    ->label('Signals')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('success_pattern_confidence')
                    ->label('Pattern %')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('processed_at')
                    ->label('Scored')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'scoring' => 'Scoring',
                        'scored' => 'Scored',
                        'gate_failed' => 'Gate Failed',
                        'discarded' => 'Discarded',
                    ]),
                Tables\Filters\Filter::make('strong_signal')
                    ->label('Strong signal (75+)')
                    ->query(fn ($query) => $query->where('score_overall', '>=', 75)),
                Tables\Filters\Filter::make('worth_investigating')
                    ->label('Worth investigating (60+)')
                    ->query(fn ($query) => $query->where('score_overall', '>=', 60)),
            ])
            ->actions([
                Tables\Actions\Action::make('view_analysis')
                    ->label('Analysis')
                    ->icon('heroicon-o-document-text')
                    ->modalContent(fn (Idea $record) => view('filament.modals.idea-analysis', ['idea' => $record]))
                    ->modalHeading(fn (Idea $record) => $record->title)
                    ->modalWidth('4xl')
                    ->visible(fn (Idea $record) => $record->isScored()),

                Tables\Actions\Action::make('rescore')
                    ->label('Re-score')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn (Idea $record) => $record->update(['status' => 'pending']))
                    ->visible(fn (Idea $record) => in_array($record->status, ['scored', 'gate_failed', 'discarded'])),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('rescore_selected')
                    ->label('Re-score selected')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each->update(['status' => 'pending'])),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIdeas::route('/'),
        ];
    }
}
