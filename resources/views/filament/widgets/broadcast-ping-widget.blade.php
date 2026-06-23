<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Queue + WebSocket</x-slot>

        <div class="flex items-center gap-4">
            <x-filament::button
                wire:click="ping"
                :disabled="$status === 'waiting'"
                color="gray"
                size="sm"
            >
                WebSocket Ping
            </x-filament::button>

            @if ($status === 'waiting')
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    Waiting for queue + WebSocket…
                </span>
            @elseif ($status === 'received')
                <span class="text-sm font-medium text-success-600 dark:text-success-400">
                    WebSocket OK — received at {{ $receivedAt }}
                </span>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

@script
<script>
    (function waitForEcho() {
        if (!window.Echo) {
            setTimeout(waitForEcho, 100);
            return;
        }
        window.Echo.channel('broadcast-ping.' + @js($userId))
            .listen('.ping', () => {
                $wire.onPing();
            });
    })();
</script>
@endscript
