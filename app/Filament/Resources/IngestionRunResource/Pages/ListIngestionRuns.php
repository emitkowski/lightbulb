<?php

namespace App\Filament\Resources\IngestionRunResource\Pages;

use App\Filament\Resources\IngestionRunResource;
use App\Jobs\Ingestion\IngestHackerNewsSignalsJob;
use App\Jobs\Ingestion\IngestRedditSignalsJob;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListIngestionRuns extends ListRecords
{
    protected static string $resource = IngestionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('run_ingestion')
                ->label('Run Ingestion')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->form([
                    Select::make('source')
                        ->label('Source')
                        ->options([
                            'all' => 'All sources',
                            'hackernews' => 'HackerNews only',
                            'reddit' => 'Reddit only',
                        ])
                        ->default('all')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $source = $data['source'];
                    $count = 0;

                    if ($source === 'all' || $source === 'hackernews') {
                        foreach (config('ingestion.hackernews.queries', []) as $query) {
                            IngestHackerNewsSignalsJob::dispatch($query);
                            $count++;
                        }
                    }

                    if ($source === 'all' || $source === 'reddit') {
                        foreach (config('ingestion.reddit.subreddits', []) as $subreddit) {
                            foreach (config('ingestion.reddit.queries', []) as $query) {
                                IngestRedditSignalsJob::dispatch($subreddit, $query);
                                $count++;
                            }
                        }
                    }

                    Notification::make()
                        ->title("{$count} ingestion jobs queued")
                        ->body('Signals will appear as jobs are processed by the queue worker.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
