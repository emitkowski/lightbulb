<?php

return [

    'reddit' => [
        'client_id' => env('REDDIT_CLIENT_ID'),
        'client_secret' => env('REDDIT_CLIENT_SECRET'),
        'user_agent' => env('REDDIT_USER_AGENT', 'Lightbulb/1.0'),
        'subreddits' => [
            'SaaS', 'indiehackers', 'microsaas', 'startups',
            'freelance', 'webdev', 'laravel', 'Entrepreneur',
            'EntrepreneurRideAlong', 'smallbusiness',
        ],
        'queries' => [
            'does anyone know a tool that',
            'is there a way to automate',
            'looking for something that',
            'I wish there was',
            'anyone else frustrated with',
            'alternatives to',
            'sick of paying for',
            'I built this because',
            'just hit $1K MRR',
            'reached $1K MRR',
            "I've been doing this manually",
        ],
        'min_score' => 10,
        'max_age_days' => 7,
        'rate_limit_sleep_ms' => 1000,
    ],

    'hackernews' => [
        'base_url' => 'https://hn.algolia.com/api/v1',
        'queries' => [
            'Ask HN: Is there a tool',
            'Ask HN: What do you use for',
            'Ask HN: Does anyone know',
            'Show HN: I built this because',
            "we couldn't find a tool that",
            "I've been doing this manually",
            '$1K MRR',
            'launched because nothing existed',
        ],
        'min_points_ask' => 50,
        'min_points_show' => 100,
        'max_age_days' => 30,
    ],

];
