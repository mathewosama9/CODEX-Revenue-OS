# P2 testing guide (the Next.js merchant app)

> Written for: the founder and whoever reviews the frontend before the first merchant.
> Status: built on `feat/P2-web` (2026-10-25), now on `main`. Updated 2026-10-27 for what came after: settings, consent import, the overview, the AI screens (P3, see the [P3 guide](../P3-ai-agent/testing-guide.md)) and the marketing site.

## 1. Run it locally

`composer dev` starts everything: Laravel (the API) on 8000, the queue, the logs, and the Next.js app on 3001. Open both on `localhost`, not `127.0.0.1`, or the session cookie won't be shared. The app uses port **3001** because 3000 is taken by another project on the founder's machine.

```bash
docker compose up -d pgsql
php artisan migrate
php artisan db:seed --class=DemoStoreSeeder   # demo store; see §2 (skip if you have your own data)
cp web/.env.example web/.env.local            # first time only
npm --prefix web install                      # first time only
composer dev                                  # API on :8000 + app on :3001
```

Open http://localhost:3001. Arabic (RTL) is the default; the language switch is at the top of every page.

> `DemoStoreSeeder` creates fake rows (a store, a WhatsApp number, a discount request, a conversation). It is for local demos only. **Never run it in production.**

## 2. Demo accounts

| Account | Password | Role | What they see |
|---|---|---|---|
| `owner@demo.test` | `password` | Owner | Every screen |
| `agent@demo.test` | `password` | Agent | Overview, Inbox, AI drafts and Settings (plus the AI stop button in the inbox) |

The demo store has everything on the go-live checklist green **except the margins**, so you can finish onboarding yourself.

## 3. What to try (about 10 minutes)

| # | Screen | What to do | What you should see |
|---|---|---|---|
| 1 | Sign in | Sign in as the owner | You go straight to the **Overview** (only one workspace), with setup progress as the first card |
| 2 | Setup | Open it from the menu and look at the checklist | 6 green, 1 grey (margins) |
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
| 13 | Sign out, then sign in as the agent | — | Only **Overview**, **Inbox**, **AI drafts** and **Settings** show in the menu. Typing `/report` in the URL shows "You don't have permission" |
| 14 | Settings | Change your name, set up 2FA, invite an agent | Saved; the QR code and recovery codes appear; the invitation shows as pending |
| 15 | Customer consent | Upload a CSV of phone numbers, write the consent text and source, tick the confirmation | An import summary; a number that opted out is never re-imported |
| 16 | Sign out → open `/ar` | — | The public marketing site (signed out). "Sign in" leads to the login page |

## 4. Automated tests

| Check | Command | What it covers |
|---|---|---|
| Everything | `composer ci:check` | Backend (Pest, PHPStan, Pint) + web lint + web TypeScript + the RTL check on `web/`. Current counts are in [STATUS.md](../../../STATUS.md) |
| E2E | `cd web && npm run e2e` | 9 spec files in `web/e2e/`, most of them run in both directions (Arabic RTL, English LTR), against the **real Laravel API** |

The E2E suite starts its own servers on ports **8010** and **3010** and uses its own database **`codex_e2e`** (it is wiped and re-seeded on every run, so it never touches your data). Create it once:

```bash
docker exec codex-revenue-os-pgsql-1 psql -U codex -d codex -c "CREATE DATABASE codex_e2e;"
docker exec codex-revenue-os-pgsql-1 psql -U codex -d codex_e2e -c "CREATE EXTENSION IF NOT EXISTS vector; CREATE EXTENSION IF NOT EXISTS pg_trgm;"
```

The spec files:

| File | Covers |
|---|---|
| `owner-journey.spec.ts` | login → onboarding (margins) → enable → settings bounds → report → approval → inbox (take, note, close) → usage → kill switch → sign out |
| `agent-and-auth.spec.ts` | wrong password, signed-out redirect, agent permissions, language switch, reset link survives a language switch |
| `overview.spec.ts` | the owner sees every card with setup first and one gold figure; the agent sees only the cards they may |
| `settings.spec.ts` | profile, password errors in the page language, 2FA setup, inviting an agent |
| `consent.spec.ts` | consent CSV upload and its summary |
| `session.spec.ts` | expired session → sign-in with a notice; API down → error screen with retry |
| `ai-agent.spec.ts` | P3: drafts, zero AI replies after takeover, discount request goes to a person, rejecting a draft |
| `multi-currency.spec.ts` | a workspace in Egyptian pounds shows pounds with one gold figure; the site stays in riyals |
| `site.spec.ts` | the marketing site opens signed out with the right `dir`, header links, sign-in path |

CI (`.github/workflows/tests.yml`) runs the same suite after `composer ci:check`.

## 5. How it is built (for the reviewer)

| Topic | Decision |
|---|---|
| Place | `web/` in the same repo; Laravel stays at the root |
| Stack | Next.js 16 (App Router, Cache Components), TypeScript, Tailwind 4, shadcn/ui, TanStack Query |
| Auth | Sanctum SPA: session cookie + XSRF token from `/sanctum/csrf-cookie`; Fortify's `/login`, `/register`, `/forgot-password`, `/reset-password`, `/two-factor-challenge`. **No tokens in localStorage** |
| Data | Fetched in the browser from `/api/v1/teams/{team}/…`. Nothing about merchants or customers is stored in Next.js; the phone number comes masked from the API; exports go straight to a file |
| Language | Routes are `/ar/…` and `/en/…`; `<html dir>` follows. Copy lives in `web/src/i18n/ar.ts` and `en.ts` |
| Brand | Tokens from the brand theme (now only in `web/src/app/globals.css`); `KpiCard`, `DeltaBadge`, `RangeEstimate` and the `revenue` button copied as they are. One gold element per screen: incremental profit on the report, "Approve discount" on approvals (only the most urgent request gets the gold button) |
| Permissions | The menu hides what `me.teams[].permissions` doesn't allow; the API refuses anyway |
| Workspace pages | Each is a thin server `page.tsx` + a client `screen.tsx`, with `instant = false` (data is per merchant, so nothing is prerendered) |

## 6. What is not done, and why

| Item | Why | What it needs |
|---|---|---|
| WhatsApp connect button works for real | Needs a Meta app and a Facebook Login for Business configuration | `NEXT_PUBLIC_META_APP_ID` and `NEXT_PUBLIC_META_CONFIG_ID` in `web/.env.local`. Until then the screen says the connection isn't enabled |
| Connect Salla from the app | By design: the merchant installs our app from the Salla app store, and the connection appears on its own (WooCommerce, for trials, is connected from Setup) | — |
| Contact details on the site | Not decided yet | `NEXT_PUBLIC_CONTACT_WHATSAPP` and `NEXT_PUBLIC_CONTACT_EMAIL`; until then the contact page says "coming soon" |
| Privacy, terms and the `LEGAL REVIEW` copy on the site | Drafts waiting for the lawyer | The legal opinion |

The backend changes that §7 of this guide used to list (reset link to Next.js, settings API, members list and "assign to", `lang/ar`) were all made on `feat/P2-backend-gaps` ([issue list](../../reviews/2026-10-25-p1-p2-issues.md)), and Inertia was removed in Session 4.
