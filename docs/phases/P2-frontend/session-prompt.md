# Phase 2 session prompt: the Next.js frontend

> Copy everything below the line and paste it into a new session opened on the `CODEX-Revenue-OS` folder.

---

You're working with me on **CODEX Revenue OS**. Reply to me in **Egyptian Arabic**, short and plain: what you did, and what I need to do. When there's a decision, give me options with your recommendation first.

## Read these first, in this order
1. `CLAUDE.md`: the rules, the stack, and how to run things locally.
2. `STATUS.md`: where we are. P1 backend is complete, 269 tests passing.
3. `docs/adr/ADR-002-frontend-nextjs.md`: the decision to use **Next.js** for the frontend, with Laravel as API only.
4. `docs/phases/P1/testing-guide.md`: what was built and the API list.
5. `docs/blueprint/16-build-plan-and-brand.md`, §4: the brand and how it maps to screens. Section 6.0 is the decision log.
6. `routes/api.php` and `routes/api/tenant.php`, plus the controllers under `app/Modules/*/Http`: the API contract the frontend will call.

## The goal of this phase
Build **the merchant app in Next.js** on top of the existing API, so a merchant can do every P1 step themselves (with our support), then hand it over for testing.

## Technical decisions (already made, don't reopen them)
- **Next.js (App Router) + TypeScript + Tailwind 4 + shadcn/ui**, in a new `web/` folder inside the same repo. Laravel stays the backend at the root.
- **Auth:** Laravel Sanctum SPA (session cookie + CSRF from `/sanctum/csrf-cookie`), with the Fortify login/register/2FA routes already in place. `FRONTEND_URL` and `SANCTUM_STATEFUL_DOMAINS` are in `.env`. No tokens in localStorage.
- **Arabic and RTL by default**, English LTR as a second language. Tailwind **logical properties only** (`ms-/me-/ps-/pe-/start-/end-/text-start/text-end`). Directional icons get mirrored in RTL. Wire `scripts/check-rtl.mjs` up to `web/` as well, and add it to CI.
- **Brand:** move the tokens from `resources/css/app.css` and the components from `resources/js/components/brand/` (KpiCard, DeltaBadge, RangeEstimate, and the `revenue` button) into `web/` as they are. **Navy** is the default theme, Light is the alternative.
  - **Gold is for money only**, and only one gold figure per screen: incremental profit, or the "approve discount" button.
  - The Cypher corner: `rounded-ss-none` on cards.
  - Latin digits, and the currency is «ر.س».
  - The product name in the app is **CODEX Commerce**.
- **The Inertia/React scaffold** under `resources/js` is temporary. Don't build anything new on it. Remove it at the end of the phase, once Next.js covers login and settings.
- **No customer data** stored in Next.js, and nothing logged with phone numbers.

## Screens (by priority)
1. **Sign-in:** login, password reset, 2FA, and picking a workspace (team) from `GET /api/v1/me`.
2. **Onboarding (Owner):** store connection status, WhatsApp connection (Embedded Signup v4 → `POST whatsapp/embedded-signup`), template status, entering margins (`margins`), and the **go-live checklist** (`GET recovery`).
3. **Recovery settings:** delays, number of messages, discount policy, the **Enable** button (refused until the checklist is complete), and the **Pause** kill switch.
4. **Discount approvals:** the list, and approve/reject with the gold (revenue) button.
5. **Incremental Profit Report:** the 4 numbers, `RangeEstimate` with the range and the probability, the "helpful / harmful / inconclusive" verdict, the "we need ~X more customers" sentence, a warning when margins are assumed, the "report frozen" state on SRM, and past snapshots. **Never show one exact number without its range.**
6. **Inbox (Owner + Agent):** the conversation queue, the thread, assign/status/notes.
7. **Usage:** monthly usage + CSV download (Owner).
8. **Data requests:** export/delete a customer (Owner).

Respect the permissions coming from `me.teams[].permissions`: hide anything the user isn't allowed to do. The API refuses anyway.

## Quality and testing
- **Playwright E2E** on the main journeys in RTL and LTR: login → onboarding → enable → report → approval → inbox.
- tsc and lint in CI, plus the RTL check on `web/`.
- Don't change any backend behavior without asking me first. If an endpoint is missing, propose it with a test, and keep `composer ci:check` green.
- Small commits per screen on a new branch from `feat/S1-foundation` (for example `feat/P2-web`), then push.

## When you're done
Update `STATUS.md` and `CLAUDE.md`, write `docs/phases/P2-frontend/testing-guide.md`, and tell me in short Egyptian Arabic: what I can test, and what you need from me.

**Next (not in this session):** the AI Sales Agent (P2 in the Blueprint: E09/E10/E11) gets its own session after this one.
