<?php

return [
    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),
        'base_url' => 'https://api.anthropic.com/v1',
        'timeout' => 60,
    ],

    'serper' => [
        'api_key' => env('SERPER_API_KEY'),
        'base_url' => 'https://google.serper.dev',
        'timeout' => 15,
        'results_count' => 10,
    ],

    'pipeline' => [
        // Signals per clustering batch
        'cluster_batch_size' => 50,
        // Min signals needed to form a cluster/idea
        'min_signals_per_idea' => 2,
        // Ideas scored per run
        'score_batch_size' => 10,
        // Score thresholds from idea-scoring-criteria.md Section 8
        'thresholds' => [
            'strong' => 75,
            'worth_investigating' => 60,
            'weak' => 45,
        ],
    ],
];
