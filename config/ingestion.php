<?php

return [

    'reddit' => [
        'client_id' => env('REDDIT_CLIENT_ID'),
        'client_secret' => env('REDDIT_CLIENT_SECRET'),
        'user_agent' => env('REDDIT_USER_AGENT', 'Lightbulb/1.0'),
        'subreddits' => [
            'SaaS', 'indiehackers', 'microsaas', 'startups',
            'freelance', 'webdev', 'laravel', 'Entrepreneur',
            'EntrepreneurRideAlong', 'smallbusiness', 'forhire',
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

    'github' => [
        'token' => env('GITHUB_TOKEN'),
        'repositories' => [
            'laravel/framework',
            'filamentphp/filament',
            'livewire/livewire',
            'laravel/horizon',
            'laravel/telescope',
            'barryvdh/laravel-debugbar',
            'spatie/laravel-permission',
            'spatie/laravel-medialibrary',
            'spatie/laravel-backup',
        ],
        'min_reactions' => 20,
        'min_age_days' => 180,
        'max_per_repo' => 100,
    ],

    'vscode' => [
        'search_queries' => [
            'time tracking',
            'project management',
            'git workflow',
            'api documentation',
            'deployment',
            'code review',
            'client management',
            'database management',
            'reporting',
            'productivity',
        ],
        'min_install_count' => 50000,
        'max_weighted_rating' => 3.9,
        'min_rating_count' => 50,
        'max_per_query' => 25,
    ],

    'stackoverflow' => [
        'key' => env('STACKEXCHANGE_KEY'),
        'tags' => [
            'laravel', 'php', 'vue.js', 'livewire', 'filament',
            'saas', 'stripe', 'automation', 'api', 'webhook',
        ],
        'min_score' => 10,
        'min_view_count' => 500,
        'min_age_days' => 365,
        'max_per_tag' => 100,
    ],

    'producthunt' => [
        'api_key' => env('PRODUCTHUNT_API_KEY'),
        'topics' => [
            'developer-tools',
            'productivity',
            'marketing',
            'saas',
            'automation',
        ],
        'min_comment_votes' => 3,
        'max_age_days' => 30,
        // Kept small deliberately — Product Hunt's GraphQL API enforces a query
        // complexity limit (500,000). max_posts_per_topic * max_comments_per_post
        // must stay well under that; 20x30 (the original values) blew past it at
        // 963,202. Verified live: 10x15 succeeds.
        'max_posts_per_topic' => 10,
        'max_comments_per_post' => 15,
    ],

    'devto' => [
        'tags' => [
            'saas', 'startup', 'productivity', 'entrepreneurship',
            'webdev', 'programming',
        ],
        'min_reactions' => 5,
        'max_age_days' => 90,
        'per_page' => 50,
    ],

    // Layer 4 — Twitter/X pain-point search
    'twitter' => [
        'bearer_token' => env('TWITTER_BEARER_TOKEN'),
        'queries' => [
            'does anyone know a tool that',
            'is there a tool that',
            'i wish there was an app',
            "i've been doing this manually",
            'sick of paying for',
            'alternatives to',
            'just hit $1k mrr',
            'reached $1k mrr',
        ],
        'min_likes' => 5,
        'max_results' => 25,
    ],

    // Layer 3 — "Alternatives to X", Layer 10 — Roadmaps, Layer 19 — Capterra
    'serper' => [
        'alternatives' => [
            'tools' => [
                // Freelancer & Agency
                'Bonsai', 'HoneyBook', 'Dubsado', 'Proposify', 'PandaDoc',
                'Harvest', 'Toggl', 'Clockify',
                // Indie SaaS & Founders
                'Canny', 'Productboard', 'Baremetrics', 'Beamer', 'Loom', 'Crisp',
                // Developer Tools
                'Sentry', 'Datadog', 'Postman', 'Linear', 'Jira',
                // Small Business
                'Calendly', 'Typeform', 'Mailchimp', 'ActiveCampaign', 'Freshdesk',
                // AI & Automation
                'Zapier', 'Make', 'n8n', 'Bubble', 'Webflow',
                // Content & Marketing
                'Buffer', 'Hootsuite', 'Semrush', 'Ahrefs', 'ConvertKit',
            ],
            'query_templates' => [
                'alternatives to {tool}',
                '{tool} alternative',
                '{tool} competitor',
            ],
            'results_per_query' => 10,
        ],
        'roadmaps' => [
            'tools' => [
                'Notion', 'Linear', 'Canny', 'Productboard', 'Jira',
                'Airtable', 'Zapier', 'Intercom', 'Crisp', 'Loom',
                'Sentry', 'Datadog', 'Mailchimp', 'HubSpot', 'Freshdesk',
            ],
            'results_per_tool' => 5,
        ],
        'capterra' => [
            'categories' => [
                'time-tracking', 'project-management', 'invoicing',
                'proposal', 'client-portal', 'crm', 'email-marketing',
                'workflow-automation', 'chatbot', 'saas-reporting',
            ],
            'results_per_category' => 5,
        ],
        // Layer 5 — Indie Hackers. IH has no public API/RSS and is a client-rendered
        // SPA (no scrapable server-rendered HTML), so this searches site:indiehackers.com
        // via Serper.dev rather than direct crawling.
        'indiehackers' => [
            'queries' => [
                'does anyone know a tool that',
                'looking for something that',
                'what tool do you use for',
                'is there a way to automate',
                'I wish there was',
                'just hit $1K MRR',
                'reached $1K MRR',
            ],
            'results_per_query' => 10,
        ],
        // Layer 11 — Stripe customer case studies. stripe.com/customers is
        // client-rendered (confirmed live 2026-07-10, unlike Paddle's equivalent
        // page), so this searches site:stripe.com/customers per category instead
        // of direct crawling.
        'stripe_customers' => [
            'categories' => [
                'SaaS',
                'subscription business',
                'marketplace platform',
                'creator platform',
                'usage-based billing',
                'vertical software',
            ],
            'results_per_query' => 10,
        ],
    ],

    // Layer 2 — G2/Capterra/Trustpilot, Layer 9 — AppSumo,
    // Layer 12 — Gumroad, Layer 14 — Chrome, Layer 6b — Freelance
    'apify' => [
        'token' => env('APIFY_TOKEN'),
        'timeout_secs' => 120,
        'memory_mbytes' => 512,

        'g2' => [
            'actor_id' => env('APIFY_G2_ACTOR', 'memo23/g2-scraper'),
            'categories' => [
                'time-tracking', 'project-management', 'invoicing-and-billing',
                'client-portal', 'error-tracking', 'code-review',
                'workflow-automation', 'no-code-development',
                'email-marketing', 'saas-metrics-and-reporting',
            ],
            'max_reviews_per_category' => 50,
            'max_star_rating' => 3,
        ],

        'appsumo' => [
            'actor_id' => env('APIFY_APPSUMO_ACTOR', 'shahidirfan/appsumo-scraper'),
            'categories' => [
                'productivity-automation', 'marketing-seo',
                'business-sales', 'developer-tools',
            ],
            'max_reviews_per_category' => 100,
            'max_star_rating' => 4,
            'min_review_count' => 50,
        ],

        'chrome' => [
            'actor_id' => env('APIFY_CHROME_ACTOR', 'vujeen/chrome-web-store-scraper'),
            'categories' => ['productivity', 'developer-tools', 'communication'],
            'min_install_count' => 10000,
            'max_star_rating' => 4.0,
            'max_items_per_category' => 50,
        ],

        'gumroad' => [
            'actor_id' => env('APIFY_GUMROAD_ACTOR', 'muhammetakkurtt/gumroad-scraper'),
            'search_terms' => [
                'client reporting template',
                'freelance invoice tracker',
                'saas metrics spreadsheet',
                'project status dashboard',
                'content calendar template',
            ],
            // This actor exposes rating count, not lifetime sales count — repurposed
            // as the closest available traction proxy. See BUG-6 fix notes.
            'min_rating_count' => 20,
        ],

        'freelance' => [
            'actor_id' => env('APIFY_UPWORK_ACTOR', 'getdataforme/upwork-actor'),
            'queries' => [
                'build a custom dashboard',
                'automate client reporting',
                'build an internal tool',
                'workflow automation tool',
                'custom client portal',
            ],
            'min_budget' => 500,
            'max_age_days' => 7,
            'item_limit' => 50,
        ],

        'peopleperhour' => [
            'actor_id' => env('APIFY_PEOPLEPERHOUR_ACTOR', 'getdataforme/peopleperhour-job-scraper'),
            'min_budget' => 200,
            'max_age_days' => 14,
            'item_limit' => 30,
        ],

        'guru' => [
            'actor_id' => env('APIFY_GURU_ACTOR', 'shahidirfan/guru-com-scraper'),
            'min_budget' => 200,
            'max_age_days' => 14,
            'item_limit' => 30,
        ],

        'indeed' => [
            'actor_id' => env('APIFY_INDEED_ACTOR', 'misceres/indeed-scraper'),
            'country' => 'US',
            'max_age_days' => 14,
            'max_items_per_search' => 25,
        ],

        'linkedin' => [
            'actor_id' => env('APIFY_LINKEDIN_ACTOR', 'curious_coder/linkedin-jobs-scraper'),
            'max_age_days' => 14,
            'count' => 25,
        ],
    ],

    // Layer 6 — Job boards (secondary/lagging signal). Query clusters per signal-sources.md.
    'job_boards' => [
        'queries' => [
            'workflow automation',
            'client reporting',
            'saas operations',
            'content operations',
            'ai tools specialist',
            'no-code automation',
        ],
    ],

    // Layer 6b — LaraJobs (direct RSS feed, no Apify/auth needed)
    'larajobs' => [
        'feed_url' => 'https://larajobs.com/feed',
        'max_age_days' => 14,
    ],

    // Layer 11 — Paddle customer case studies (direct HTML scrape, no Apify/auth
    // needed — paddle.com/customers is server-rendered, unlike Stripe's equivalent
    // page). See ingestion.serper.stripe_customers above for the Stripe half of
    // this layer. Lemon Squeezy's Discover directory is not a source — it 404s as
    // of 2026-07 (discontinued post-Stripe-acquisition), see docs/BUGS.md.
    'paddle_customers' => [
        'url' => 'https://www.paddle.com/customers',
    ],

    // Layer 21 — Apify Actor Demand Gaps. Fans out to three existing job shapes
    // rather than one dedicated job: GitHub issues (apify org repos), Reddit
    // (r/webscraping with actor-specific queries), and a new actor-health check
    // reusing the actor IDs this app already depends on. Discord/forum monitoring
    // from the spec is not built — same bot-infra constraint as Layer 20.
    'apify_gaps' => [
        'github_repositories' => [
            'apify/apify-sdk-python',
            'apify/apify-sdk-js',
            'apify/actor-templates',
        ],

        'reddit_subreddit' => 'webscraping',
        'reddit_queries' => [
            'is there an apify actor for',
            'looking for a scraper for',
            'does anyone have a scraper that',
            'need a scraper for',
            'apify actor for',
            'scraper not working anymore',
        ],

        // Reuses the actor IDs this app already depends on (see the apify.* blocks
        // above) — checking our own suppliers for degradation doubles as market-gap
        // discovery: a struggling actor in a category Layers 1-20 already validated
        // as real demand is Layer 21's strongest "compound signal".
        'monitor_actor_ids' => [
            env('APIFY_UPWORK_ACTOR', 'getdataforme/upwork-actor'),
            env('APIFY_APPSUMO_ACTOR', 'shahidirfan/appsumo-scraper'),
            env('APIFY_GURU_ACTOR', 'shahidirfan/guru-com-scraper'),
            env('APIFY_G2_ACTOR', 'memo23/g2-scraper'),
            env('APIFY_CHROME_ACTOR', 'vujeen/chrome-web-store-scraper'),
            env('APIFY_GUMROAD_ACTOR', 'muhammetakkurtt/gumroad-scraper'),
            env('APIFY_PEOPLEPERHOUR_ACTOR', 'getdataforme/peopleperhour-job-scraper'),
            env('APIFY_INDEED_ACTOR', 'misceres/indeed-scraper'),
            env('APIFY_LINKEDIN_ACTOR', 'curious_coder/linkedin-jobs-scraper'),
        ],
        'min_runs_30d' => 20,
        // NOTE: Apify's platform-level "SUCCEEDED" status only means the run didn't
        // crash — it does NOT mean the actor did real work (confirmed live 2026-07-08,
        // see docs/memory/gotchas.md). Treat failure_rate as a weak supplementary
        // signal, not proof of a gap on its own — the Reddit/GitHub request signals
        // above matter more. actorReviewRating is similarly unreliable on tiny
        // sample sizes, hence the review-count floor below.
        'max_failure_rate' => 0.15,
        'max_review_rating' => 2.0,
        'min_review_count_for_rating_signal' => 3,
    ],

];
