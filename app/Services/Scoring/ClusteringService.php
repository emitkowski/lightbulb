<?php

namespace App\Services\Scoring;

use App\Models\Idea;
use App\Models\IdeaSignal;
use App\Models\RawSignal;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Groups related raw signals into candidate ideas using keyword overlap.
 *
 * When ANTHROPIC_API_KEY is available, replace extractKeywords() and
 * clusterSignals() with LLM-based semantic clustering for better accuracy.
 */
class ClusteringService
{
    private const STOPWORDS = [
        'a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
        'of', 'with', 'by', 'from', 'is', 'are', 'was', 'were', 'be', 'been',
        'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could',
        'should', 'may', 'might', 'can', 'not', 'no', 'i', 'we', 'you', 'it',
        'this', 'that', 'there', 'my', 'your', 'our', 'their', 'its', 'any',
        'just', 'like', 'really', 'very', 'so', 'also', 'how', 'what', 'when',
        'where', 'who', 'which', 'if', 'then', 'than', 'as', 'up', 'out',
        'about', 'into', 'over', 'after', 'use', 'using', 'get', 'got',
        'want', 'need', 'make', 'one', 'new', 'way', 'time', 'more', 'some',
    ];

    /** @param Collection<int, RawSignal> $signals */
    public function cluster(Collection $signals): Collection
    {
        if ($signals->isEmpty()) {
            return collect();
        }

        $clusters = [];

        foreach ($signals as $signal) {
            $keywords = $this->extractKeywords($signal);
            $placed = false;

            foreach ($clusters as &$cluster) {
                if ($this->overlapScore($keywords, $cluster['keywords']) >= 2) {
                    $cluster['signals'][] = $signal;
                    // Merge keywords, keeping the highest-frequency ones
                    foreach ($keywords as $word => $freq) {
                        $cluster['keywords'][$word] = ($cluster['keywords'][$word] ?? 0) + $freq;
                    }
                    $placed = true;
                    break;
                }
            }
            unset($cluster);

            if (! $placed) {
                $clusters[] = [
                    'keywords' => $keywords,
                    'signals' => [$signal],
                ];
            }
        }

        $minSignals = config('scoring.pipeline.min_signals_per_idea', 2);

        return collect($clusters)
            ->filter(fn ($cluster) => count($cluster['signals']) >= $minSignals)
            ->map(fn ($cluster) => $this->buildIdea($cluster));
    }

    /** @return array<string, mixed> */
    private function extractKeywords(RawSignal $signal): array
    {
        $text = strtolower($signal->title . ' ' . ($signal->content ?? ''));
        $words = preg_split('/\W+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $freq = [];
        foreach ($words as $word) {
            if (strlen($word) >= 4 && ! in_array($word, self::STOPWORDS)) {
                $freq[$word] = ($freq[$word] ?? 0) + 1;
            }
        }

        arsort($freq);

        return array_slice($freq, 0, 15, true);
    }

    /** @param array<string, int> $a @param array<string, int> $b */
    private function overlapScore(array $a, array $b): int
    {
        return count(array_intersect_key($a, $b));
    }

    /** @param array{keywords: array<string, int>, signals: RawSignal[]} $cluster */
    private function buildIdea(array $cluster): Idea
    {
        $signals = collect($cluster['signals']);
        $topKeywords = array_slice(array_keys($cluster['keywords']), 0, 5);

        $title = $this->synthesizeTitle($signals, $topKeywords);
        $summary = $this->synthesizeSummary($signals);

        $idea = Idea::create([
            'title' => $title,
            'signals_summary' => $summary,
            'source_signals_count' => $signals->count(),
            'status' => 'pending',
        ]);

        foreach ($signals as $signal) {
            IdeaSignal::create([
                'idea_id' => $idea->id,
                'raw_signal_id' => $signal->id,
                'weight' => 1.00,
            ]);

            $signal->update(['processed' => true]);
        }

        return $idea;
    }

    /** @param Collection<int, RawSignal> $signals @param string[] $keywords */
    private function synthesizeTitle(Collection $signals, array $keywords): string
    {
        // Use the highest-scored signal's title as the anchor
        $anchor = $signals->sortByDesc('score')->first();
        $topTopic = implode(' / ', array_slice($keywords, 0, 3));

        return Str::limit($anchor->title ?? $topTopic, 120);
    }

    /** @param Collection<int, RawSignal> $signals */
    private function synthesizeSummary(Collection $signals): string
    {
        $lines = $signals->take(5)->map(function (RawSignal $signal) {
            $preview = Str::limit($signal->content ?: $signal->title, 200);

            return "- [{$signal->source}] {$signal->title}: {$preview}";
        });

        return $lines->implode("\n");
    }
}
