# Duka Store - Snippe Payments Demo (Laravel 13)

> Duka Store is a small demo storefront that shows developers how to accept
> payments with the Snippe Payments API. It collects payments via Mobile Money,
> Cards, and QR Codes using raw cURL - no SDK. This is the Laravel 13 edition.

## What Is Duka Store?

A tiny clothes shop (8 products, 500 TZS each) wired to Snippe end to end:
customers register, add items to a cart, check out, and pay through Snippe's
hosted flows, while the store tracks orders and reconciles them with webhooks.
Use it as a reference when integrating Snippe into any Laravel or PHP project.

## What It Demonstrates

- Mobile Money - USSD push sent to the customer's phone
- Card payments - hosted checkout redirect (Snippe handles card entry and 3D Secure)
- Dynamic QR - QR code the customer scans with a mobile money app
- Webhooks - async payment status updates (the source of truth)
- Order reconciliation - matching payments to orders via reference and metadata

## Editions

The same store is available in three frameworks. Pick your stack:

| Stack | Location |
|---|---|
| Laravel 13 | this repository |
| Node.js / Express | ../duka-store-nodejs |
| Django | ../duka_store_django |

## Quick Start

```bash
composer install
npm install && npm run build
cp .env.example .env        # set SNIPPE_API_KEY, APP_URL (https)
php artisan serve
# -> http://localhost:8000
```

Snippe requires a reachable HTTPS URL for webhooks and redirects. For local
testing, run `ngrok http 8000` and set `APP_URL` to the https URL it gives you.

## Project Structure

```
duka-store-laravel/
|-- routes/web.php              # routes
|-- app/Http/Controllers/       # checkout, order, webhook controllers
|-- app/Services/               # SnippeApiService (cURL), StorageService
|-- config/snippe.php           # SNIPPE_* configuration
|-- resources/views/            # Blade templates
`-- .env
```

## Series Guides

Deep dives live in per-series guides:

- [Series 3 - Hosted Checkout with Payment Sessions](SERIES-3-README.md) -
  create session -> redirect -> return URL, what belongs in a session, and why
  the redirect is not proof of payment

More series guides will be added here as the series grows.

## Documentation

- [Snippe API documentation](https://docs.snippe.sh)
- [Payment Sessions](https://docs.snippe.sh/docs/2026-01-25/sessions)
- [Payment Profiles](https://docs.snippe.sh/docs/2026-01-25/sessions/profiles)
- [Payment Links](https://docs.snippe.sh/docs/2026-01-25/sessions/payment-links)
- [Webhooks](https://docs.snippe.sh/docs/2026-01-25/webhooks)
