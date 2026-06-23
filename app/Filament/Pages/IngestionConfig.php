<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class IngestionConfig extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Ingestion Config';

    protected static ?string $title = 'Ingestion Configuration';

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationGroup = 'Settings';

    protected static string $view = 'filament.pages.ingestion-config';

    public function getViewData(): array
    {
        return [
            'reddit' => config('ingestion.reddit'),
            'hackernews' => config('ingestion.hackernews'),
        ];
    }
}
