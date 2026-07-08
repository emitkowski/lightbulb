# Lightbulb

A SaaS idea-discovery pipeline. Lightbulb ingests public pain-point signals from ~20 sources (Reddit, Hacker News, GitHub issues, Product Hunt, G2 reviews, freelance job boards, and more), clusters related signals into candidate product ideas, and scores each idea against a rubric using Claude — surfacing the results in an admin dashboard for review.

## How it works

1. **Ingestion** — scheduled jobs pull public posts/reviews/listings matching pain-point language (e.g. "is there a tool that...", "I wish there was...", "sick of paying for...") from each source and store them as raw signals.
2. **Clustering** — related signals are grouped by keyword overlap into candidate ideas.
3. **Scoring** — each idea passes through a specificity gate, a competitor-landscape search, and an LLM-driven scoring pass (problem strength, distribution path, competition gap, build feasibility, automability, revenue plausibility).
4. **Review** — scored ideas land in a Filament admin panel for the developer to browse, sorted and filtered by score.

## Signal sources

Reddit, Hacker News, GitHub issues, VS Code Marketplace, Stack Overflow, Product Hunt, Dev.to, Twitter/X, Google search (alternatives/roadmaps/Capterra via Serper.dev), G2, AppSumo, Chrome Web Store, Gumroad, and freelance/job boards (Upwork, PeoplePerHour, Guru, LinkedIn, Indeed, LaraJobs).

## Tech stack

- PHP 8.5 / Laravel 13
- Jetstream + Fortify (auth), Inertia.js + Vue 3 (frontend), Filament v3 (admin)
- MySQL, Redis, Laravel Sail (Docker)
- Claude (via the Claude Code CLI or the Anthropic API) for idea scoring

## Requirements

- Docker
- Node.js & npm
- A shared nginx proxy running (`~/code/dev-local/proxy`) with a valid mkcert certificate

## Getting started

```bash
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
./vendor/bin/sail npm run dev
```

See `.env.example` for the full list of environment variables. Most ingestion sources work with no API key at all; a handful require one (Product Hunt, Twitter/X, Apify-based sources) — see the comments in `.env.example` for where to get each one.

## Common commands

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan test --compact
./vendor/bin/sail npm run dev
```

## Running the pipeline

```bash
# Ingest signals from all configured sources (or a specific one via --source=)
./vendor/bin/sail artisan ingestion:run

# Cluster raw signals into candidate ideas, then score them
./vendor/bin/sail artisan scoring:run --cluster
./vendor/bin/sail artisan scoring:run --score
```

Scored ideas are reviewable at `/admin`.

## License

MIT
