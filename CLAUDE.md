# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Status

Greenfield. Only `PRD.md` exists — no code, no `composer.json`, no migrations. Anything beyond the PRD has yet to be built. Read `PRD.md` before making architectural decisions; it's the source of truth.

## Product

Mailulator is a self-hosted, Laravel-native Mailtrap alternative. Two deliverables:

1. **Receiver App** — standalone Laravel app (Horizon/Telescope-style: installable into an existing app or deployed alone) that ingests emails over HTTP, stores them, and renders a Livewire inspection UI.
2. **Driver Package** — Composer package registering a custom Symfony Mailer transport via `Mail::extend('mailulator', ...)`. Serializes `SentMessage` to JSON and POSTs to the receiver. No UI, no storage, no routes.

End-to-end path: sender Laravel app → driver package → `POST /api/emails` (Bearer token) → receiver resolves token to inbox → stores email + attachments → Livewire UI.

## Architectural Constraints (from PRD — do not violate without updating PRD)

- **HTTP-only.** No SMTP. Non-goal for v1.
- **Token-to-inbox routing.** The bearer token alone determines which inbox an email lands in. Tokens are hashed at rest (Sanctum-style). One token = one inbox.
- **Isolated database connection.** Receiver uses its own `mailulator` connection — never the host app's primary DB. Default SQLite at `database/mailulator.sqlite`; swappable to any Laravel driver via `MAILULATOR_DB_*` env vars. All migrations run against `mailulator` connection only.
- **Realtime is configurable, not assumed.** Default `polling` (`wire:poll`); `broadcast` via Reverb/Pusher is opt-in. Keep the default install dependency-free.
- **First registered user becomes admin.** Two roles: admin (manages inboxes/keys/users/retention) and user (views assigned inboxes).
- **Attachments stored on a configurable disk** (`MAILULATOR_ATTACHMENTS_DISK`), not inlined in the DB.

## Data Model Summary

- `inboxes` — name, hashed `api_key`, `retention_days` (nullable = forever), `last_used_at`.
- `emails` — `inbox_id` FK, from/to/cc/bcc, subject, `html_body`, `text_body`, raw `headers` JSON, `read_at`.
- `attachments` — `email_id` FK, filename, mime_type, size, disk, path.

## Build Order (PRD §17)

1. Data model + `POST /api/emails` with token auth and storage.
2. Driver package (unlocks end-to-end testing).
3. Basic Livewire UI (list + detail).
4. Inbox management (create/revoke keys, key shown once).
5. Realtime + polling toggle.
6. QoL: search, bulk actions, retention pruning.
7. Polish + docs.

## Resolved Decisions (PRD §18)

- **License:** MIT.
- **Driver failure mode:** `MAILULATOR_ON_FAILURE` — `log` (default) | `throw` | `silent`. A receiver outage must never break the sender app's request.
- **API payload formats:** Accept both JSON (base64 attachments) and `multipart/form-data`. Driver defaults to JSON.
- **Admin seeding:** `php artisan mailulator:install` only. Idempotent, headless-friendly, no first-run web wizard.
