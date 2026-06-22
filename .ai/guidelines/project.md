# Project

This is a Laravel 13 application. It uses Jetstream with Inertia.js and Vue 3 for the frontend.

## Development Workflow

- Start the environment: `./vendor/bin/sail up -d`
- Build assets: `npm run dev` (HMR) or `npm run build` (production)
- The app runs at `https://{APP_DOMAIN}` — requires the shared nginx proxy (`~/code/dev-local/proxy`) and a valid mkcert cert in `docker/certs/`
