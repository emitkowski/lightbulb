<?php

namespace App\Filament\Widgets;

use App\Jobs\BroadcastPingJob;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class BroadcastPingWidget extends Widget
{
    protected static string $view = 'filament.widgets.broadcast-ping-widget';

    protected static ?int $sort = 100;

    public string $status = 'idle';

    public ?string $receivedAt = null;

    public string $userId = '';

    public function mount(): void
    {
        $this->userId = (string) Auth::id();
    }

    public function ping(): void
    {
        $this->status = 'waiting';
        $this->receivedAt = null;
        BroadcastPingJob::dispatch($this->userId, now()->toISOString());
    }

    public function onPing(): void
    {
        $this->status = 'received';
        $this->receivedAt = now()->format('H:i:s');
    }
}
