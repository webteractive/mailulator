# Mailulator

Self-hosted, Laravel-native email testing. One package ships both sides:

- **Receiver** — ingests email via HTTP, stores it in an isolated SQLite (or MySQL/Postgres) database, serves a Vue 3 inbox UI at `/mailulator`.
- **Driver** — a Symfony Mailer transport registered as `mailulator`. Set `MAIL_MAILER=mailulator` on the sender app and outbound email forwards to a Mailulator receiver over HTTP.

Either side can be disabled via config — install the same package as a pure sender, a pure receiver, or both.

---

## Install

```bash
composer require webteractive/mailulator
php artisan mailulator:install
```

`mailulator:install` publishes:

- `app/Providers/MailulatorServiceProvider.php` — customize the auth gate here.
- `config/mailulator.php` — receiver + driver config.

It then runs migrations against the isolated `mailulator` connection and optionally seeds a first inbox (API key printed once — save it).

Publish compiled UI assets:

```bash
php artisan vendor:publish --tag=mailulator-assets
```

Re-run this tag after each `composer update`.

---

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

---

## Sender-side (.env)

```
MAIL_MAILER=mailulator
MAILULATOR_URL=https://mail.your-staging.com
MAILULATOR_TOKEN=your-inbox-api-key
```

On failure (unreachable receiver, non-2xx response), the driver branches on `MAILULATOR_ON_FAILURE`:

- `log` (default) — `Log::warning`, return normally.
- `silent` — return normally.
- `throw` — re-raise as a Symfony `TransportException`.

A receiver outage **will not** break the sender app's request.

---

## Receiver-side (.env)

| Variable | Default | Purpose |
| --- | --- | --- |
| `MAILULATOR_DB_CONNECTION` | `sqlite` | DB driver (`sqlite`, `mysql`, `pgsql`, …). |
| `MAILULATOR_SQLITE_PATH` | `database_path('mailulator.sqlite')` | SQLite file, auto-touched. |
| `MAILULATOR_DB_HOST` / `_PORT` / `_DATABASE` / `_USERNAME` / `_PASSWORD` | — | Non-SQLite connection settings. |
| `MAILULATOR_ATTACHMENTS_DISK` | `local` | Filesystem disk for attachment bytes. |
| `MAILULATOR_RATE_LIMIT` | `600` | Ingest requests/min per inbox. |
| `MAILULATOR_UI_PATH` | `mailulator` | SPA path prefix. |
| `MAILULATOR_REALTIME` | `polling` | `polling` or `broadcast`. |
| `MAILULATOR_POLL_INTERVAL` | `3` | Polling interval (s). |
| `MAILULATOR_RECEIVER_ENABLED` | `true` | Turn receiver off to install as driver-only. |
| `MAILULATOR_DRIVER_ENABLED` | `true` | Turn driver off to install as receiver-only. |

---

## Ingest API

`POST /api/emails` — bearer-token authenticated, rate-limited per inbox.

Accepts JSON (base64 attachments) or `multipart/form-data` (UploadedFile attachments). Returns `201 { "id": <email_id> }`.

---

## Realtime

Three states, controlled by two env vars:

| `MAILULATOR_REALTIME_ENABLED` | `MAILULATOR_REALTIME` | Behavior |
| --- | --- | --- |
| `true` (default) | `polling` (default) | UI polls every `MAILULATOR_POLL_INTERVAL` seconds. Zero extra deps. |
| `true` | `broadcast` | Echo subscribes to `mailulator.inbox.{id}` private channels; polling is skipped. |
| `false` | — | Static UI. No polling, no broadcast. |

To enable broadcasting, install Reverb / Pusher / Soketi in the host app, then:

```
MAILULATOR_REALTIME=broadcast
MAILULATOR_BROADCASTER=reverb
MAILULATOR_ECHO_KEY=...
MAILULATOR_ECHO_CLUSTER=...      # Pusher only
MAILULATOR_ECHO_HOST=...          # Reverb only
MAILULATOR_ECHO_PORT=...
MAILULATOR_ECHO_SCHEME=https
```

`EmailReceived` dispatches to `mailulator.inbox.{id}` on every ingest. Channel authorization routes through `Mailulator::canViewInbox`. If `MAILULATOR_ECHO_KEY` is missing when `mode=broadcast`, the client logs a warning and does nothing (no polling fallback — if you asked for broadcast, you get broadcast).

---

## Retention

Set `retention_days` per inbox; the daily `PruneEmails` job deletes older emails and cleans their attachment files. `null` = keep forever.

The job is auto-scheduled. Ensure your host app runs `schedule:run` via cron or `schedule:work`.

---

## Upgrade

```bash
composer update webteractive/mailulator
php artisan vendor:publish --tag=mailulator-assets --force
php artisan migrate --database=mailulator
```

---

## License

MIT.
