# Changelog

All notable changes to `webteractive/mailulator` will be documented in this file.

## Unreleased

## 0.1.2 - 2026-04-27

### Fixed
- README Laravel constraint corrected to `^11 || ^12 || ^13` (Laravel 10 was dropped during 0.1.0 prep but the README still listed it).
- README intro and Realtime section no longer claim ingest is HTTP-only or reference an outdated `mode=broadcast` env var.

### Changed
- Tightened README — removed redundant Quickstart and Database-isolation prose now covered by the deployment-modes section and config table. Reworded the post-install token note to explain why in-app installs should still save it.

## 0.1.1 - 2026-04-27

### Changed
- Tighten `composer.json` to `minimum-stability: stable`. All dependencies are tagged stable releases; no behavior change.

## 0.1.0 - 2026-04-27

First public release.

### Receiver
- Bearer-token ingest API at `POST /api/emails` (JSON or multipart). Per-inbox rate limiting.
- Isolated `mailulator` DB connection with auto-touch SQLite default; any Laravel driver supported. `MAILULATOR_DB_CONNECTION` also accepts any pre-defined connection name from the host app's `config/database.php` to share an existing DB.
- Vue 3 SPA at `/mailulator` (shadcn-vue + Tailwind). Three-pane layout, HTML/text/headers/attachments tabs, sandboxed iframe preview, device preview toggle, dark mode.
- Admin inbox management — create / rename / delete / regenerate key. Plaintext key shown once.
- Per-inbox color (UI accent) stored in JSON `settings` column with allowlisted keys.
- Protected `Default` inbox — cannot be renamed or deleted. Last-remaining-inbox guard regardless of name.
- Realtime toggle: polling (default, dep-free), broadcast (Reverb/Pusher), or static.
- `EmailReceived` event broadcasts to private `mailulator.inbox.{id}` channels (when enabled).
- `PruneEmails` daily job — per-inbox retention, cleans attachment files.
- Search across subject/from/to, bulk mark-read, bulk delete.

### Driver
- Symfony Mailer transport registered as `mailulator`. Failure modes: `log` (default), `silent`, `throw`.
- Zero-config **in-app** delivery: when receiver and driver are both enabled and `MAILULATOR_URL` is unset, the transport persists directly to the `Default` inbox via `StoreIncomingEmail` and bypasses HTTP.

### Deployment shapes
- **In-app** — UI lives in the same app that sends mail. Just `MAIL_MAILER=mailulator`, no URL or token.
- **Standalone** — dedicated receiver app shared by many sender apps via `MAILULATOR_URL` + per-inbox `MAILULATOR_TOKEN`.

### Tooling
- Horizon-style published `MailulatorServiceProvider` for gate / `canViewInbox` / `manage` customization.
- `mailulator:install` — idempotent, headless-friendly, seeds Default inbox and prints token once.
- Test suite on Pest 4.
- `AGENTS.md` for AI coding agents.
