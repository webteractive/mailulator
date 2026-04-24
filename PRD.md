# Product Requirements Document

## Mailulator — Self-Hosted Email Testing Service for Laravel

---

## 1. Overview

A self-hosted, Laravel-based alternative to Mailtrap. The product consists of two components:

1. **Receiver App** — A standalone Laravel application that captures, stores, and displays emails sent to it via HTTP.
2. **Driver Package** — A Composer package installed in any Laravel application that intercepts outgoing mail and forwards it to the Receiver App instead of a real mail server.

Teams deploy the Receiver App once per environment (or once shared across environments) and install the Driver Package in any application whose emails they want to intercept for inspection.

---

## 2. Problem Statement

Development teams currently rely on third-party SaaS tools like Mailtrap for staging email interception. These tools work well but have drawbacks:

- Ongoing subscription cost
- Emails pass through external infrastructure
- Rate limits on lower tiers
- Limited control over retention, access, and data residency

Local tools like Mailpit solve the local-dev case but are not built for shared team/staging use and require SMTP configuration. Teams need a self-hosted, HTTP-based, Laravel-native solution they can own end-to-end.

---

## 3. Goals

- Provide a drop-in replacement for Mailtrap in Laravel projects.
- Keep setup minimal — install the driver package, set two environment variables, done.
- Deliver a polished inspection UI comparable to Mailtrap and Mailpit.
- Enable team/staging use via API-key-based inbox routing.
- Stay Laravel-native (Livewire, Eloquent, standard conventions).

---

## 4. Non-Goals (v1)

- SMTP protocol support — HTTP-only by design.
- Resending captured emails to real recipients (planned for v2).
- Spam scoring or deliverability analysis.
- Multi-region replication or HA deployment patterns.
- Non-Laravel sender support (though the HTTP API is open enough to make this trivial later).

---

## 5. Architecture

```
┌────────────────────────────┐
│ Laravel App (Sender)       │
│                            │
│  Driver Package installed  │
│  MAILULATOR_URL             │
│  MAILULATOR_TOKEN           │
└─────────────┬──────────────┘
              │
              │ POST /api/emails
              │ Authorization: Bearer {token}
              │
              ▼
┌────────────────────────────┐
│ Receiver App (Service)     │
│                            │
│  • API endpoint            │
│  • Database storage        │
│  • Livewire UI             │
│  • Inbox management        │
└────────────────────────────┘
```

The API key sent by the driver determines which inbox the email lands in on the receiver side. One receiver instance can serve many apps, each routed to its own isolated inbox.

---

## 6. Components

### 6.1 Driver Package

A lightweight Composer package that registers a custom Symfony Mailer transport in Laravel.

**Responsibilities:**
- Extend `Symfony\Component\Mailer\Transport\AbstractTransport`.
- Serialize outbound `SentMessage` objects (headers, to/from/cc/bcc, subject, HTML body, text body, attachments as base64) into a JSON payload.
- `POST` the payload to the configured receiver URL with the bearer token.
- Register via `Mail::extend('mailulator', ...)` in a service provider.
- Configuration file publishable via `artisan vendor:publish`.

**Configuration (`.env`):**
```
MAIL_MAILER=mailulator
MAILULATOR_URL=https://mail.your-staging.com
MAILULATOR_TOKEN=your-api-key
```

**Out of scope:**
- No UI, no storage, no routes. Pure send-only transport.

---

### 6.2 Receiver App

A standalone Laravel application (similar in spirit to Horizon or Telescope — can be installed into an existing app or deployed standalone) that accepts and displays emails.

**Responsibilities:**
- Expose `POST /api/emails` endpoint with bearer-token authentication.
- Resolve the token to an inbox and store the email accordingly.
- Persist emails with attachments, headers, and metadata.
- Serve a Livewire-powered UI for browsing and inspecting emails.
- Manage inboxes, API keys, and retention policies.

---

## 7. Data Model

### `inboxes`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| name | string | Human-readable (e.g. "Project A Staging") |
| api_key | string | Hashed, à la Sanctum |
| retention_days | int nullable | Null = keep forever |
| last_used_at | timestamp nullable | For UI "last active" display |
| created_at | timestamp | |
| updated_at | timestamp | |

### `emails`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| inbox_id | FK → inboxes.id | Determined by the API key used |
| from | string | |
| to | json | Array of addresses |
| cc | json nullable | |
| bcc | json nullable | |
| subject | string | |
| html_body | longtext nullable | |
| text_body | longtext nullable | |
| headers | json | Full raw headers |
| read_at | timestamp nullable | |
| created_at | timestamp | |

### `attachments`
| Column | Type | Notes |
|---|---|---|
| id | bigint PK | |
| email_id | FK → emails.id | |
| filename | string | |
| mime_type | string | |
| size | int | Bytes |
| disk | string | Storage disk name |
| path | string | Path on disk |
| created_at | timestamp | |

---

## 8. API Specification

### `POST /api/emails`

**Auth:** `Authorization: Bearer {api_key}`

**Request Body (JSON):**
```json
{
  "from": "noreply@example.com",
  "to": ["user@example.com"],
  "cc": [],
  "bcc": [],
  "subject": "Welcome",
  "html_body": "<h1>Hi</h1>",
  "text_body": "Hi",
  "headers": { "X-Mailer": "Laravel" },
  "attachments": [
    {
      "filename": "invoice.pdf",
      "mime_type": "application/pdf",
      "content": "base64-encoded-bytes"
    }
  ]
}
```

**Responses:**
- `201 Created` — email stored, returns `{ "id": 123 }`.
- `401 Unauthorized` — missing or invalid token.
- `422 Unprocessable Entity` — validation failure.
- `429 Too Many Requests` — rate limit hit (optional, per-token).

---

## 9. UI Requirements (Livewire)

### 9.1 Layout
A three-panel layout familiar from email clients:
- **Left:** Inbox list (admin users see all; regular users see assigned inboxes).
- **Middle:** Email list for the selected inbox, paginated, with search.
- **Right:** Detail view for the selected email.

### 9.2 Email Detail
Tabs within the detail panel:
- **HTML Preview** — Rendered inside a sandboxed iframe.
- **Plain Text** — Raw text body.
- **Raw Headers** — Full header dump.
- **Attachments** — List with filename, size, and download action.

### 9.3 Real-Time Updates
Configurable via a config file:
- `polling` — default; `wire:poll.3s` on the email list.
- `broadcast` — uses Laravel Echo + Reverb (or Pusher) for push updates.

Teams can opt into Reverb if they want instant updates; polling keeps the default install dependency-free.

### 9.4 Inbox Actions
- Mark all as read
- Delete all emails in inbox
- Search by subject, from, or to
- Filter by date range

### 9.5 Inbox Management (Admin)
- Create new inbox → generates a new API key (shown once)
- Revoke / regenerate API key
- Rename inbox
- Set retention policy (days)
- View last-used timestamp

---

## 10. Authentication & Access

### 10.1 API Authentication
- Bearer token on every `POST /api/emails` request.
- Tokens are hashed at rest (Sanctum-style).
- Each token maps to exactly one inbox.

### 10.2 UI Authentication
- Standard Laravel auth (sessions).
- Roles:
  - **Admin** — can manage inboxes, keys, users, retention.
  - **User** — can view emails in assigned inboxes.
- First user registered becomes admin.

---

## 11. Database

The Receiver App uses its own database connection, independent of the host application's primary database. This keeps email data isolated — especially important when the package is installed into an existing Laravel app alongside its primary database.

### 11.1 Default: SQLite
- Ships with SQLite as the default connection.
- The package registers its own `mailulator` connection in Laravel's database config.
- Database file lives at `database/mailulator.sqlite` (path configurable).
- Auto-created on first migration — no manual setup.
- Migrations run against the `mailulator` connection only, never touching the host app's primary DB.

### 11.2 Configurable to Any Laravel-Supported Driver
Teams running this at scale or as a standalone service can swap the connection to MySQL, PostgreSQL, or any other Laravel-supported driver via environment variables:

```
MAILULATOR_DB_CONNECTION=mysql
MAILULATOR_DB_HOST=127.0.0.1
MAILULATOR_DB_DATABASE=mailulator
MAILULATOR_DB_USERNAME=...
MAILULATOR_DB_PASSWORD=...
```

### 11.3 Why SQLite by Default
- Zero configuration — works immediately after install.
- Perfect fit for dev/staging workloads (low write volume, single-node).
- Doesn't muddy up the host app's primary DB when installed as a package.
- Portable — the entire email history is a single file, easy to back up or wipe.

---

## 12. Configuration

Receiver-side configuration (`config/mailulator.php`):

```php
return [
    'database' => [
        'connection' => env('MAILULATOR_DB_CONNECTION', 'mailulator'),
        'sqlite_path' => env('MAILULATOR_SQLITE_PATH', database_path('mailulator.sqlite')),
    ],
    'realtime' => env('MAILULATOR_REALTIME', 'polling'), // 'polling' | 'broadcast'
    'poll_interval' => env('MAILULATOR_POLL_INTERVAL', 3), // seconds
    'retention' => [
        'default_days' => env('MAILULATOR_RETENTION_DAYS', 30),
        'prune_schedule' => 'daily',
    ],
    'storage' => [
        'attachments_disk' => env('MAILULATOR_ATTACHMENTS_DISK', 'local'),
    ],
    'ui' => [
        'path' => 'mailulator',
    ],
];
```

---

## 13. Retention & Housekeeping

- Daily scheduled job prunes emails older than `retention_days` per inbox.
- Attachments cleaned up alongside parent email.
- "Delete all in inbox" action available in UI.
- Optional per-inbox max email count as a secondary cap.

---

## 14. Quality-of-Life Features (v1)

- Mark all as read / unread
- Delete all in inbox
- Full-text search on subject / from / to
- Date-range filter
- Unread count badge per inbox
- Copy-to-clipboard for email addresses and headers

---

## 15. Out-of-Scope / v2 Candidates

- **Resend to real address** — forward a captured email through an actual mail driver (Resend, Mailgun, SMTP) to a real inbox for checking render quality in Gmail, Outlook, etc.
- **Webhooks** — notify external services when emails arrive.
- **Tagging / labeling** — organize within an inbox.
- **Team/workspace concepts** — group inboxes by team.
- **SMTP transport** — bridge SMTP to the HTTP API for non-Laravel senders.
- **Export** — download an inbox as .eml or .mbox.

---

## 16. Success Metrics

- Time from `composer install` to first captured email < 5 minutes.
- Zero external services required for basic installation.
- Can fully replace Mailtrap for typical Laravel staging workflows.
- Community adoption: GitHub stars, Packagist installs.

---

## 17. Build Order

Recommended implementation sequence:

1. **Data model + API endpoint** — `POST /api/emails`, token auth, storage.
2. **Driver package** — enables end-to-end testing.
3. **Basic UI** — list + detail view.
4. **Inbox management** — create/revoke keys.
5. **Real-time + polling toggle.**
6. **QoL features** — search, bulk actions, retention pruning.
7. **Polish + documentation.**

---

## 18. Resolved Decisions

- **License:** MIT.
- **Driver failure mode:** Configurable via `MAILULATOR_ON_FAILURE` with values `log` (default), `throw`, `silent`. Log-and-continue is the default so a receiver outage never breaks the sender app's request path.
- **API payload formats:** Both JSON (base64 attachments) and `multipart/form-data` are accepted. Driver defaults to JSON; multipart is available for senders pushing large attachments.
- **Admin seeding:** Artisan command only — `php artisan mailulator:install`. Idempotent, works on headless deploys, no first-run web wizard.