# Trison Topup

Game top-up / PPOB platform built with Laravel 12, Inertia.js v2, and Vue 3.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.2+, tested with PHP 8.4), Spatie Permission/MediaLibrary/Sluggable/Activitylog
- **Frontend**: Inertia.js v2, Vue 3 (Composition API), TailwindCSS v4, Vite, Laravel Wayfinder (typed route/action helpers)
- **Payments**: LinkQu and Midtrans, with automatic failover between the two for automatic checkout, plus a member wallet balance payment option
- **Topup providers**: Digiflazz and LapakGaming, plus manual/gift order flows for products without an automated provider

## Features

- Storefront with brand/category browsing, checkout (automatic gateway, manual transfer, or wallet balance), transaction tracking, and reviews
- Member wallet: balance top-up (deposit) via LinkQu/Midtrans, balance mutation ledger, balance checkout
- Admin panel: product/brand/category management with Excel bulk import (including automatic image assignment by SKU or URL), order management (Topup/Gift/Manual/Archive, plus a unified "All Orders" view), member management, deposit tracking, sales reports, role/permission/menu management

## Requirements

- PHP 8.2+ (developed and tested against PHP 8.4)
- Composer
- Node.js 18+ and npm
- MySQL (or another Laravel-supported database)

## Local Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Configure DB_* and any payment/provider credentials in .env, then:
php artisan migrate --seed
php artisan storage:link

npm install
npm run build   # or `npm run dev` for local development
```

Or in one step: `composer run setup` (installs dependencies, copies `.env`, generates the app key, migrates, and builds the frontend).

To run locally with the queue worker and Vite dev server together:

```bash
composer run dev
```

## Environment Variables

Copy `.env.example` to `.env` and fill in credentials for whichever integrations you use:

- `LINKQU_*` — LinkQu payment gateway (checkout + wallet deposits)
- `MIDTRANS_*` — Midtrans payment gateway (checkout + wallet deposits)
- `DIGIFLAZZ_*` — Digiflazz topup provider
- `LAPAKGAMING_*` — LapakGaming topup provider (disabled by default until a reseller account is issued)
- `APIAGAME_*`, `GAME_PROXY_URL` — game account ID resolution (e.g. Mobile Legends nickname lookup)
- `VODA_*` — WhatsApp notification provider (optional)

The active payment gateway (LinkQu vs Midtrans) and manual transfer bank details are configured from the admin Settings page, not `.env`.

## Deployment Notes

- Run `php artisan migrate --force` and `npm run build` as part of your deploy step (the Wayfinder-generated `resources/js/actions` / `resources/js/routes` files are gitignored and regenerate automatically during `npm run build`).
- `php artisan storage:link` is required for uploaded media (product/brand images, payment proofs) to be publicly reachable.
- A queue worker (`php artisan queue:work`) is required — order confirmation emails and the payment/deposit expiry jobs are dispatched onto the queue.
- The scheduler must be running (`php artisan schedule:work` or a cron entry calling `php artisan schedule:run` every minute) to expire pending payments/deposits hourly (`routes/console.php`).
- Set `APP_ENV=production` and `APP_DEBUG=false` before going live.
