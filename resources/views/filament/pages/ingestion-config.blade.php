<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- HackerNews --}}
        <x-filament::section>
            <x-slot name="heading">HackerNews</x-slot>
            <x-slot name="description">
                Min {{ $hackernews['min_points_ask'] }} pts (Ask HN) · {{ $hackernews['min_points_show'] }} pts (Show HN) · last {{ $hackernews['max_age_days'] }} days · no API key required
            </x-slot>

            <div class="space-y-1">
                @foreach ($hackernews['queries'] as $query)
                    <div class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                        <x-heroicon-m-magnifying-glass class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                        {{ $query }}
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Reddit --}}
        <x-filament::section>
            <x-slot name="heading">Reddit</x-slot>
            <x-slot name="description">
                Min {{ $reddit['min_score'] }} upvotes · last {{ $reddit['max_age_days'] }} days · requires API key
            </x-slot>

            <div class="space-y-4">
                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                        Subreddits ({{ count($reddit['subreddits']) }})
                    </p>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($reddit['subreddits'] as $subreddit)
                            <span class="inline-flex items-center rounded-full bg-orange-100 dark:bg-orange-500/10 px-2.5 py-0.5 text-xs font-medium text-orange-700 dark:text-orange-400">
                                r/{{ $subreddit }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="mb-2 text-xs font-medium uppercase tracking-wide text-gray-400">
                        Queries ({{ count($reddit['queries']) }})
                    </p>
                    <div class="space-y-1">
                        @foreach ($reddit['queries'] as $query)
                            <div class="flex items-center gap-2 rounded-lg bg-gray-50 dark:bg-white/5 px-3 py-2 text-sm text-gray-700 dark:text-gray-300">
                                <x-heroicon-m-magnifying-glass class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                {{ $query }}
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-filament::section>

    </div>

    <x-filament::section>
        <x-slot name="heading">Run Summary</x-slot>
        <x-slot name="description">Total jobs dispatched per ingestion run</x-slot>

        <div class="grid grid-cols-3 gap-4 text-center">
            <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-4">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ count($hackernews['queries']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500">HackerNews jobs</p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-4">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ count($reddit['subreddits']) * count($reddit['queries']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500">Reddit jobs</p>
            </div>
            <div class="rounded-lg bg-gray-50 dark:bg-white/5 p-4">
                <p class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ count($hackernews['queries']) + count($reddit['subreddits']) * count($reddit['queries']) }}
                </p>
                <p class="mt-1 text-sm text-gray-500">Total per run</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
