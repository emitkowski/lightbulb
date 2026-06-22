# lightbulb

A SaaS Idea Pipeline

- Docker
- Node.js & npm
- Shared nginx proxy running (`~/code/dev-local/proxy`)

## Getting Started

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --seed
```

## Common Commands

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan test --compact
./vendor/bin/sail npm run dev
```
