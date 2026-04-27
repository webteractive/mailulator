# Mailulator

Self-hosted, Laravel-native email testing. One package ships both sides:

- **Receiver** — ingests email via HTTP, stores it in an isolated database (default SQLite; any Laravel-supported driver works), serves a Vue 3 inbox UI at `/mailulator`.
- **Driver** — a Symfony Mailer transport registered as `mailulator`. Set `MAIL_MAILER=mailulator` on the sender app and outbound email forwards to a Mailulator receiver over HTTP.

Where the inbox UI lives is what splits the two deployment shapes — see [Deployment modes](#deployment-modes).

## Requirements

- PHP `^8.3`
- Laravel `^10 || ^11 || ^12 || ^13`

## Install

```bash
composer require webteractive/mailulator
php artisan mailulator:install
```

`mailulator:install` publishes:

- `app/Providers/MailulatorServiceProvider.php` — customize the auth gate here.
- `config/mailulator.php` — receiver + driver config.

It then runs migrations against the isolated `mailulator` connection and creates a protected `Default` inbox. The Default inbox cannot be renamed or deleted; Mailulator requires at least one inbox to exist at all times.

The install command prints a token. You only need it for [standalone](#2-standalone--ui-lives-in-a-dedicated-app-shared-by-many-senders) installs — [in-app](#1-in-app--ui-lives-with-the-app-it-captures) installs ignore it. Save it either way; it isn't shown again.

Publish compiled UI assets:

```bash
php artisan vendor:publish --tag=mailulator-assets
```

Re-run this tag after each `composer update`.

## Deployment modes

Two shapes, distinguished by where the inbox UI lives.

### 1. In-app — UI lives with the app it captures

The app sending the mail is also the app you read it from. Install Mailulator there, set `MAIL_MAILER=mailulator`, and the captured mail shows up at `/mailulator` on the same host. One app, one inbox, no plumbing.

```bash
# .env
MAIL_MAILER=mailulator
```

No URL, no token. The transport detects in-app mode (driver enabled + receiver enabled + no `MAILULATOR_URL`) and writes directly to the `Default` inbox via `StoreIncomingEmail`, bypassing HTTP entirely.

Best for: local development, staging, demo environments — any app that just wants to "swallow" its own outbound mail and read it back in place.

### 2. Standalone — UI lives in a dedicated app, shared by many senders

A single Laravel app exists just to host inboxes and the UI. Any number of other apps point at it via `MAILULATOR_URL` and route to the right inbox via `MAILULATOR_TOKEN`. One UI, many senders.

**On the standalone receiver** — install the package as receiver-only:

```bash
# .env
MAILULATOR_RECEIVER_ENABLED=true
MAILULATOR_DRIVER_ENABLED=false
```

Create one inbox per sender app from the UI (or reuse the seeded `Default`). Each inbox has its own bearer token; that token is the only thing that ties a sender to its inbox.

**On each sender app** — install the package as driver-only and point it at the receiver with the inbox's token:

```bash
# .env
MAIL_MAILER=mailulator
MAILULATOR_URL=https://mailulator.your-domain.test
MAILULATOR_TOKEN=<token for this app's inbox>
MAILULATOR_RECEIVER_ENABLED=false
MAILULATOR_DRIVER_ENABLED=true
```

To onboard another sender app, repeat the sender-side `.env` with a different inbox token. The receiver doesn't care how many apps point at it.

## Quickstart

```php
Mail::to('test@example.com')->send(new OrderShipped($order));
```

Open `/mailulator` on the receiver — the email lands within the polling interval.

## Gate

The SPA is gated. Edit `app/Providers/MailulatorServiceProvider.php`:

```php
protected function gate(): void
{
    Gate::define('viewMailulator', fn ($user) =>
        in_array(optional($user)->email, [
            'you@example.com',
        ])
    );
}
```

Default: local environment only. Non-local without a customized gate → 403.

Optional hooks in the same provider:

```php
public function boot(): void
{
    parent::boot();

    Mailulator::canViewInbox(fn ($user, $inboxId) =>
        $user->inboxes()->where('inboxes.id', $inboxId)->exists()
    );

    Mailulator::manage(fn ($user) => $user->is_admin ?? false);
}
```

## Sender-side (.env)

| Variable | Default | Purpose |
| --- | --- | --- |
| `MAIL_MAILER` | — | Set to `mailulator` to route outbound email through this driver. |
| `MAILULATOR_URL` | — | Base URL of the receiver. |
| `MAILULATOR_TOKEN` | — | Per-inbox bearer token printed by `mailulator:install` or the admin UI. |
| `MAILULATOR_TIMEOUT` | `5` | HTTP timeout (seconds) for ingest calls. |
| `MAILULATOR_ON_FAILURE` | `log` | `log` (warn + return), `silent` (return), or `throw` (raise `TransportException`). |
| `MAILULATOR_DRIVER_ENABLED` | `true` | Set `false` to install as receiver-only. |

A receiver outage **will not** break the sender app's request unless `MAILULATOR_ON_FAILURE=throw`.

## Receiver-side (.env)

| Variable | Default | Purpose |
| --- | --- | --- |
| `MAILULATOR_RECEIVER_ENABLED` | `true` | Turn receiver off to install as driver-only. |
| `MAILULATOR_DB_CONNECTION` | `mailulator` | Connection **name** to use. Set to any connection defined in your host app's `config/database.php` (e.g. `mysql`) to share that DB; leave as `mailulator` for an isolated, package-managed connection. |
| `MAILULATOR_DB_DRIVER` | `sqlite` | Driver for the auto-managed connection — only used when `MAILULATOR_DB_CONNECTION=mailulator` and the host hasn't pre-defined it. |
| `MAILULATOR_SQLITE_PATH` | `database_path('mailulator.sqlite')` | SQLite file, auto-touched. |
| `MAILULATOR_DB_HOST` / `_PORT` / `_DATABASE` / `_USERNAME` / `_PASSWORD` / `_CHARSET` | — | Credentials for the auto-managed connection (non-SQLite drivers). |
| `MAILULATOR_ATTACHMENTS_DISK` | `local` | Filesystem disk for attachment bytes. |
| `MAILULATOR_RATE_LIMIT` | `600` | Ingest requests/min per inbox. |
| `MAILULATOR_RETENTION_DAYS` | `30` | Default retention for newly created inboxes. Per-inbox override available; `null` keeps forever. |
| `MAILULATOR_UI_PATH` | `mailulator` | SPA path prefix. |
| `MAILULATOR_UI_DOMAIN` | — | Optional subdomain (e.g. `mail.your-staging.com`). |
| `MAILULATOR_REALTIME_ENABLED` | `true` | Master switch for realtime updates. |
| `MAILULATOR_REALTIME` | `polling` | `polling` or `broadcast`. |
| `MAILULATOR_POLL_INTERVAL` | `3` | Polling interval (seconds). |
| `MAILULATOR_BROADCASTER` | `reverb` | `reverb` or `pusher` when `MAILULATOR_REALTIME=broadcast`. |

### Database isolation

By default, Mailulator manages its own `mailulator` connection — pointed at SQLite (`database/mailulator.sqlite`) but configurable to MySQL/Postgres/etc. via the `MAILULATOR_DB_*` env vars. The package never touches your host app's primary DB.

To share an existing connection from `config/database.php`, set `MAILULATOR_DB_CONNECTION` to that connection's **name** (e.g. `mysql`). Mailulator then uses the host-defined connection as-is and ignores the `MAILULATOR_DB_DRIVER` / host / credentials env vars.

## Ingest API

`POST /api/emails` — bearer-token authenticated, rate-limited per inbox.

Accepts JSON (base64 attachments) or `multipart/form-data` (UploadedFile attachments). Returns `201 { "id": <email_id> }`.

## Realtime

Three states, controlled by two env vars:

| `MAILULATOR_REALTIME_ENABLED` | `MAILULATOR_REALTIME` | Behavior |
| --- | --- | --- |
| `true` (default) | `polling` (default) | UI polls every `MAILULATOR_POLL_INTERVAL` seconds. Zero extra deps. |
| `true` | `broadcast` | Echo subscribes to `mailulator.inbox.{id}` private channels. |
| `false` | — | Static UI. No polling, no broadcast. |

To enable broadcasting, install Reverb / Pusher in the host app, then:

```
MAILULATOR_REALTIME=broadcast
MAILULATOR_BROADCASTER=reverb
MAILULATOR_ECHO_KEY=...
MAILULATOR_ECHO_CLUSTER=...      # Pusher only
MAILULATOR_ECHO_HOST=...          # Reverb only
MAILULATOR_ECHO_PORT=...
MAILULATOR_ECHO_SCHEME=https
```

`EmailReceived` dispatches to `mailulator.inbox.{id}` on every ingest. Channel authorization routes through `Mailulator::canViewInbox`. If `mode=broadcast` is set without a configured `MAILULATOR_ECHO_KEY`, the client logs a warning and falls back to polling.

## Inboxes

- Each inbox has a name, optional retention period, optional color (UI accent), and a hashed bearer token.
- The seeded `Default` inbox is protected — it cannot be renamed or deleted.
- The last remaining inbox cannot be deleted regardless of name.
- Regenerating a key invalidates the previous token immediately at the ingest boundary.

## Retention

Set `retention_days` per inbox; the daily `PruneEmails` job deletes older emails and cleans their attachment files. `null` = keep forever.

The job is auto-scheduled. Ensure your host app runs `schedule:run` via cron or `schedule:work`.

## Upgrade

```bash
composer update webteractive/mailulator
php artisan vendor:publish --tag=mailulator-assets --force
php artisan migrate --database=mailulator
```

## License

MIT.
