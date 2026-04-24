<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Receiver
    |--------------------------------------------------------------------------
    |
    | The receiver side of Mailulator: ingests email via HTTP, stores it in
    | an isolated database connection, and exposes the Vue SPA UI.
    |
    */

    'receiver' => [

        'enabled' => env('MAILULATOR_RECEIVER_ENABLED', true),

        'database' => [
            'connection' => env('MAILULATOR_DB_CONNECTION', 'sqlite'),
            'sqlite_path' => env('MAILULATOR_SQLITE_PATH'),
            'host' => env('MAILULATOR_DB_HOST'),
            'port' => env('MAILULATOR_DB_PORT'),
            'database' => env('MAILULATOR_DB_DATABASE'),
            'username' => env('MAILULATOR_DB_USERNAME'),
            'password' => env('MAILULATOR_DB_PASSWORD'),
            'charset' => env('MAILULATOR_DB_CHARSET', 'utf8mb4'),
        ],

        'storage' => [
            'attachments_disk' => env('MAILULATOR_ATTACHMENTS_DISK', 'local'),
        ],

        'rate_limit' => [
            'max_attempts' => (int) env('MAILULATOR_RATE_LIMIT', 600),
            'decay_seconds' => 60,
        ],

        'retention' => [
            'default_days' => env('MAILULATOR_RETENTION_DAYS', 30),
        ],

        'ui' => [
            'path' => env('MAILULATOR_UI_PATH', 'mailulator'),
            'domain' => env('MAILULATOR_UI_DOMAIN'),
        ],

        'realtime' => [

            // Master switch. When false, the UI is static — no polling, no
            // broadcast, no auto-refresh. Useful for read-only audits or when
            // running on constrained infrastructure.
            'enabled' => (bool) env('MAILULATOR_REALTIME_ENABLED', true),

            // 'polling' (default, zero-dep) or 'broadcast' (requires a
            // broadcaster installed in the host app: Reverb, Pusher, etc.).
            'mode' => env('MAILULATOR_REALTIME', 'polling'),

            'poll_interval' => (int) env('MAILULATOR_POLL_INTERVAL', 3),

            // Only read when mode=broadcast. Determines how Echo boots on the
            // client. Broadcaster-specific config is read from the host app's
            // own broadcasting.php — we just pass through the values the Vue
            // SPA needs for Laravel Echo.
            'broadcaster' => env('MAILULATOR_BROADCASTER', 'reverb'),

            'echo' => [
                'key' => env('MAILULATOR_ECHO_KEY'),
                'cluster' => env('MAILULATOR_ECHO_CLUSTER'),
                'host' => env('MAILULATOR_ECHO_HOST'),
                'port' => env('MAILULATOR_ECHO_PORT'),
                'scheme' => env('MAILULATOR_ECHO_SCHEME', 'https'),
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Driver
    |--------------------------------------------------------------------------
    |
    | The driver side of Mailulator: a Symfony Mailer transport registered
    | as `mailulator` via Mail::extend(). Set MAIL_MAILER=mailulator on the
    | sender app to forward outbound email to a Mailulator receiver.
    |
    */

    'driver' => [

        'enabled' => env('MAILULATOR_DRIVER_ENABLED', true),

        'url' => env('MAILULATOR_URL'),

        'token' => env('MAILULATOR_TOKEN'),

        'timeout' => (int) env('MAILULATOR_TIMEOUT', 5),

        'on_failure' => env('MAILULATOR_ON_FAILURE', 'log'),

    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware applied to the Mailulator UI routes. The Authenticate
    | middleware (which checks the gate defined in the published
    | MailulatorServiceProvider) is always appended to this list.
    |
    */

    'middleware' => [
        'web',
    ],

];
