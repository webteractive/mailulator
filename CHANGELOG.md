# Changelog

All notable changes to `webteractive/mailulator` will be documented in this file.

## 1.0.0 - 2026-04-25

Initial release.

- Bearer-token ingest API at `POST /api/emails` (JSON or multipart). Per-inbox rate limiting.
- Isolated `mailulator` DB connection with auto-touch SQLite default; any Laravel driver supported. `MAILULATOR_DB_CONNECTION` accepts any pre-defined connection name to share an existing host-app DB instead.
- Symfony Mailer transport registered as `mailulator`. Failure modes: `log` (default), `silent`, `throw`.
- Vue 3 SPA at `/mailulator` (shadcn-vue + Tailwind). Three-pane layout, HTML/text/headers/attachments tabs, sandboxed iframe preview, device preview toggle, dark mode.
- Admin inbox management — create / rename / delete / regenerate key. Plaintext key shown once.
- Per-inbox color (UI accent) stored in JSON `settings` column with allowlisted keys.
- Protected `Default` inbox — cannot be renamed or deleted. Last-remaining-inbox guard regardless of name.
- Realtime toggle: polling (default, dep-free), broadcast (Reverb/Pusher), or static.
- `EmailReceived` event broadcasts to private `mailulator.inbox.{id}` channels (when enabled).
- `PruneEmails` daily job — per-inbox retention, cleans attachment files.
- Search across subject/from/to, bulk mark-read, bulk delete.
- Horizon-style published `MailulatorServiceProvider` for gate / `canViewInbox` / `manage` customization.
- `mailulator:install` — idempotent, headless-friendly, seeds Default inbox and prints token once.
