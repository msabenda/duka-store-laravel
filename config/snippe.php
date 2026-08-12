<?php

return [
    'api_key' => env('SNIPPE_API_KEY', ''),
    'api_base' => env('SNIPPE_API_BASE', 'https://api.snippe.sh/v1'),
    // Checkout session creation lives on a different base than payments:
    // /v1 → POST /payments (payments endpoint; custom checkout flow, kept for later)
    // /api/v1 → POST /sessions (checkout session creation; used by the hosted checkout)
    'api_base_sessions' => env('SNIPPE_API_BASE_SESSIONS', 'https://api.snippe.sh/api/v1'),
    'webhook_secret' => env('SNIPPE_WEBHOOK_SECRET', ''),
    'version' => '2026-01-25',
];
