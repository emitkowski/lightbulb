<div class="space-y-4">
    <div class="flex items-center gap-3 text-sm text-gray-500">
        <span class="font-medium">{{ $signal->source }}</span>
        <span>·</span>
        <span>{{ $signal->author }}</span>
        <span>·</span>
        <span>{{ $signal->score }} points</span>
        <span>·</span>
        <span>{{ $signal->comment_count }} comments</span>
        @if ($signal->published_at)
            <span>·</span>
            <span>{{ $signal->published_at->diffForHumans() }}</span>
        @endif
    </div>

    @if ($signal->source_url)
        <a href="{{ $signal->source_url }}" target="_blank" class="text-sm text-blue-600 hover:underline break-all">
            {{ $signal->source_url }}
        </a>
    @endif

    <div class="prose prose-sm max-w-none whitespace-pre-wrap rounded border p-4 bg-gray-50 dark:bg-gray-900">{{ $signal->content }}</div>
</div>
