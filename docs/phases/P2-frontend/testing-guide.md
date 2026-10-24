# P2 testing guide (the Next.js merchant app)

> Written for: the founder and whoever reviews the frontend before the first merchant.
> Status: done on branch `feat/P2-web` (from `feat/S1-foundation`), 2026-10-25. The backend was not changed, apart from a demo-data seeder.

## 1. Run it locally

You need two servers: Laravel (the API) and Next.js (the app). Open both on `localhost`, not `127.0.0.1`, or the session cookie won't be shared.

```bash
docker compose up -d pgsql
php artisan migrate
php artisan db:seed --class=DemoStoreSeeder   # demo store; see §2 (skip if you have your own data)
composer dev                                  # Laravel on http://localhost:8000

cd web
cp .env.example .env.local                    # first time only
npm install                                   # first time only
npm run dev                                   # the app on http://localhost:3000
```

Open http://localhost:3000. Arabic (RTL) is the default; the language switch is at the top of every page.

> `DemoStoreSeeder` creates fake rows (a store, a WhatsApp number, a discount request, a conversation). It is for local demos only. **Never run it in production.**

## 2. Demo accounts

| Account | Password | Role | What they see |
|---|---|---|---|
| `owner@demo.test` | `password` | Owner | Every screen |
| `agent@demo.test` | `password` | Agent | The inbox only |

The demo store has everything on the go-live checklist green **except the margins**, so you can finish onboarding yourself.

## 3. What to try (about 10 minutes)

| # | Screen | What to do | What you should see |
|---|---|---|---|
| 1 | Sign in | Sign in as the owner | You go straight to **Setup** (only one workspace) |
| 2 | Setup | Look at the checklist | 6 green, 1 grey (margins) |
| 3 | Setup → Profit margin | Enter 40 as the default and a value for one category, then save | "Saved", and the checklist turns fully green |
| 4 | Cart recovery | Press **Turn on recovery** | The status turns to "On", and the **Pause now** button appears |
| 5 | Cart recovery | Put 30 in the first delay and save | The browser refuses: the minimum is 60 minutes |
| 6 | Profit report | Open it | A verdict ("not conclusive yet"), and "not enough data" instead of a number. **No single number appears without a range** |
| 7 | Discount approvals | Press **Approve discount** (the gold button) | The request disappears |
| 8 | Inbox | Open the conversation, take it, add a note, close it | The phone number is masked; the note appears |
| 9 | Usage | Look at this month, download the CSV | 37 abandoned carts, 21 eligible, 34 messages |
| 10 | Data requests | Enter `0551234567` and export | A JSON file downloads; nothing stays on screen |
| 11 | Cart recovery | **Pause now** → confirm | The status turns back to "Off" |
| 12 | Top bar | Switch to English, and the theme to Light | The layout flips to LTR; the theme survives a refresh |
| 13 | Sign out, then sign in as the agent | — | Only **Inbox** shows in the menu. Typing `/report` in the URL shows "You don't have permission" |

## 4. Automated tests

| Check | Command | What it covers |
|---|---|---|
| Everything | `composer ci:check` | Backend (269 tests, PHPStan, Pint) + web lint + web TypeScript + the RTL check on `web/` |
| E2E | `cd web && npm run e2e` | 5 journeys × 2 directions (Arabic RTL, English LTR) = **10 tests**, against the **real Laravel API** |

The E2E suite starts its own servers on ports **8010** and **3010** and uses its own database **`codex_e2e`** (it is wiped and re-seeded on every run, so it never touches your data). Create it once:

```bash
docker exec codex-revenue-os-pgsql-1 psql -U codex -d codex -c "CREATE DATABASE codex_e2e;"
docker exec codex-revenue-os-pgsql-1 psql -U codex -d codex_e2e -c "CREATE EXTENSION IF NOT EXISTS vector; CREATE EXTENSION IF NOT EXISTS pg_trgm;"
```

The journeys: login → onboarding (margins) → enable → settings bounds → report → approval → inbox (take, note, close) → usage → kill switch → sign out; wrong password; signed-out redirect; agent permissions; language switch. CI (`.github/workflows/tests.yml`) runs the same suite after `composer ci:check`.

## 5. How it is built (for the reviewer)

| Topic | Decision |
|---|---|
| Place | `web/` in the same repo; Laravel stays at the root |
| Stack | Next.js 16 (App Router, Cache Components), TypeScript, Tailwind 4, shadcn/ui, TanStack Query |
| Auth | Sanctum SPA: session cookie + XSRF token from `/sanctum/csrf-cookie`; Fortify's `/login`, `/register`, `/forgot-password`, `/reset-password`, `/two-factor-challenge`. **No tokens in localStorage** |
| Data | Fetched in the browser from `/api/v1/teams/{team}/…`. Nothing about merchants or customers is stored in Next.js; the phone number comes masked from the API; exports go straight to a file |
| Language | Routes are `/ar/…` and `/en/…`; `<html dir>` follows. Copy lives in `web/src/i18n/ar.ts` and `en.ts` |
| Brand | Tokens copied from `resources/css/app.css`; `KpiCard`, `DeltaBadge`, `RangeEstimate` and the `revenue` button copied as they are. One gold element per screen: incremental profit on the report, "Approve discount" on approvals (only the most urgent request gets the gold button) |
| Permissions | The menu hides what `me.teams[].permissions` doesn't allow; the API refuses anyway |
| Workspace pages | Each is a thin server `page.tsx` + a client `screen.tsx`, with `instant = false` (data is per merchant, so nothing is prerendered) |

## 6. What is not done, and why

| Item | Why | What it needs |
|---|---|---|
| WhatsApp connect button works for real | Needs a Meta app and a Facebook Login for Business configuration | `NEXT_PUBLIC_META_APP_ID` and `NEXT_PUBLIC_META_CONFIG_ID` in `web/.env.local`. Until then the screen says the connection isn't enabled |
| Connect Salla from the app | By design: the merchant installs our app from the Salla app store, and the connection appears on its own | — |
| Settings (profile, password, 2FA setup, inviting an agent) in Next.js | The backend has no JSON API for these yet, and CORS only allows the login routes | Backend change (decision §7) |
| Removing the old Inertia scaffold | It still serves the settings pages and the password-reset link | After the settings move to Next.js |
| Assigning a conversation to a named teammate | The API has no member list; today you can "take" a conversation or release it | `GET /teams/{team}/members` |
| Arabic validation messages | Laravel has no `lang/ar`, so server errors come back in English | Arabic translation files |

## 7. Backend changes waiting for your approval

None of these were made. Each is small and comes with a test.

1. **Password-reset email link** → points to the Next.js page (`/ar/reset-password?token=…&email=…`) instead of the old Laravel page. One line in `AppServiceProvider` (`ResetPassword::createUrlUsing`).
2. **Settings API** → open CORS for `user/*` (Fortify profile, password, 2FA), and add JSON endpoints for team members and invitations. After that: settings screens in Next.js, then delete Inertia.
3. **`GET /teams/{team}/members`** → names in the inbox, and "assign to" a teammate.
4. **`lang/ar`** → Arabic validation messages.
