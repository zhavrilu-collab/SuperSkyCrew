<?php

return [

    'webhook_url' => env('ADMIN_CONSOLE_WEBHOOK_URL'),
    'webhook_secret' => env('ADMIN_CONSOLE_WEBHOOK_SECRET'),
    'api_url' => env('ADMIN_CONSOLE_API_URL', 'http://127.0.0.1:8001'),
    'application_slug' => env('ADMIN_CONSOLE_APPLICATION_SLUG', 'hr-saas'),

    /*
    | When false, HTTP client skips TLS verification for console API calls.
    | Use only when app and admin share a host with a broken/local CA trust store.
    */
    'http_verify' => filter_var(env('ADMIN_CONSOLE_HTTP_VERIFY', true), FILTER_VALIDATE_BOOL),

    /*
    | Force console HTTPS calls to resolve to 127.0.0.1 (same-VPS, bypass hairpin/WAF).
    */
    'http_resolve_loopback' => filter_var(env('ADMIN_CONSOLE_HTTP_RESOLVE_LOOPBACK', false), FILTER_VALIDATE_BOOL),

];
