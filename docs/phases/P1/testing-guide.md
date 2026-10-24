# P1 testing guide (backend)

> Written for: the founder and whoever reviews P1 before the frontend phase starts.
> Status: P1 backend is complete on branch `feat/S1-foundation`. **The frontend (Next.js) has not been built** ([ADR-002](../../adr/ADR-002-frontend-nextjs.md)).

## 1. Run it locally

```bash
docker compose up -d pgsql      # PostgreSQL + pgvector on port 54320
composer install && npm install
php artisan migrate
composer ci:check               # every check: formatting, types, RTL, PHPStan, all tests
```

To create an admin account (the password appears once only):

```bash
php artisan codex:admin you@codexai.systems "Your Name"
php artisan serve               # then open http://localhost:8000/admin
```

The admin asks you to set up 2FA (Google Authenticator) on first login.

## 2. What's tested automatically (no credentials needed)

| Area | Main test file | What it guarantees |
|---|---|---|
| Merchant isolation | `tests/Isolation/*`, `tests/Arch/*` | No query sees another merchant's data, and a query with no merchant set fails |
| Salla | `tests/Feature/Commerce/SallaIntegrationTest.php` | Install, carts, orders, uninstall, single token refresh, reconciliation |
| WhatsApp | `tests/Feature/Messaging/WhatsAppTest.php` | Sending via MM API, never sending twice, 131049, opt-out, quality |
| Recovery | `tests/Feature/Recovery/RecoveryFlowTest.php` | The full journey: 18 scenarios |
| Report | `tests/Feature/Measurement/ProfitReportTest.php` | A known effect lands inside its interval, SRM, no double counting |
| Consent and data | `tests/Isolation/ConsentGateTest.php`, `tests/Feature/Compliance/*` | No consent = no send, export/delete, retention, log redaction |
| Inbox, metering, admin | `tests/Feature/Inbox`, `Api/UsageTest.php`, `Platform/*` | |

## 3. What needs real credentials before the first merchant (P0b spikes)

All of the Salla and Meta code is written against their documentation and marked **[افتراض]** in the code. It needs a live check:

| # | Check | What you need | Where in the code |
|---|---|---|---|
| 1 | Field names in Salla payloads (`urls.checkout`, mobile, amounts, dates) | Salla demo store + private app | `Commerce/Salla/SallaPayloadMapper.php` |
| 2 | `app.store.authorize` and token refresh | `SALLA_CLIENT_ID` / `SECRET` / `WEBHOOK_SECRET` | `SallaWebhookHandler`, `RefreshStoreToken` |
| 3 | Endpoints `carts/abandoned`, `orders`, `oauth2/user/info` | Same | `SallaAdapter` |
| 4 | Embedded Signup v4 + MM API + `X-Hub-Signature-256` signature | Meta App + BV + test number | `Messaging/Meta/*` |
| 5 | Coexistence in Saudi Arabia | Saudi test number | — |
| 6 | Submitting the Arabic templates and getting them approved | WABA | `config/whatsapp_templates.php` (**needs review by a Saudi speaker**) |

**Variables to set in `.env`:** `SALLA_CLIENT_ID`, `SALLA_CLIENT_SECRET`, `SALLA_WEBHOOK_SECRET`, `META_APP_ID`, `META_APP_SECRET`, `META_WEBHOOK_VERIFY_TOKEN`, and `META_DATA_LOCALIZATION_REGION` (**irreversible**, decide it with the lawyer).

Webhook URLs to register with Salla and Meta: `https://<domain>/webhooks/salla` and `https://<domain>/webhooks/meta`.

## 4. The API for the frontend phase

Every route sits under `/api/v1/teams/{team}/…`. The list is in `routes/api/tenant.php`:

`me`, `margins`, `whatsapp` (+ embedded-signup, templates), `recovery` (+ enable, pause, approvals), `reports/profit` (+ snapshots), `inbox`, `billing/usage`, `data-requests`.

## 5. Open decisions affecting testing

1. Data localization region (ADR-006): AE or BH.
2. Hosting (ADR-005).
3. Retention periods: the defaults in `config/codex.php` are 30/90/365 days, pending the legal opinion.
4. Ramadan 1448 dates: estimated, need confirming.
5. The minimum detectable effect (`mde_halalas_per_subject` = 5 riyals per customer): set it after the interviews.
