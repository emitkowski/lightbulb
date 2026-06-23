<?php

namespace App\Filament\Resources\RawSignalResource\Pages;

use App\Filament\Resources\RawSignalResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRawSignal extends EditRecord
{
    protected static string $resource = RawSignalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
