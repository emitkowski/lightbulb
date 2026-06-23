<?php

namespace App\Filament\Resources\RawSignalResource\Pages;

use App\Filament\Resources\RawSignalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRawSignals extends ListRecords
{
    protected static string $resource = RawSignalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
