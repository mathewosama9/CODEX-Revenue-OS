# Session 4 prompt: operations hardening + cleanup (the last step before P3)

> Copy everything below the line and paste it into a new session opened on the `CODEX-Revenue-OS` folder.

---

You're working with me on **CODEX Revenue OS**. Reply to me in **Egyptian Arabic**, short and plain: what you did, and what I need to do. When there's a decision, give me options with your recommendation first. If I say "شوف انت الافضل", take your recommendation and go.

## Read these first, in this order
1. `CLAUDE.md`: rules, stack, how to run things. Read `web/AGENTS.md` before touching Next.js code.
2. `docs/reviews/2026-10-25-p1-p2-issues.md`: **the issue list**. Sessions 1–3 fixed every 🔴 item (marked ✅). This session takes what is left.
3. `STATUS.md`.

## Where to start (git)
- Sessions 1–3 live on stacked branches: `feat/P2-backend-gaps` → `fix/honest-measurement` → `fix/consent-compliance` → `fix/whatsapp-session`.
- If they are already merged into `main` (`git log main --oneline | head`), branch from `main`. If not, branch from `fix/whatsapp-session`.
- New branch: `fix/operations-cleanup`. Small commits per item, then push.

## Scope (from the issue list)
**Operations (do these first, in this order):**
- **H1** stale claimed actions re-run forever → count reclaims, fail after 3, show in health.
- **H2** Meta app secret in the token-exchange URL → send it in a POST body (check Meta's docs that POST works for `oauth/access_token`, and mark it [افتراض] if you can't confirm), or redact URLs in exceptions.
- **H3** `BackfillStore` can't finish for medium stores → one job per resource with page checkpoints, `$timeout` below `retry_after`, and sync categories in reconcile too.
- **H4** `ReconcileStore` fails on any Salla 429 → `$tries`/`backoff` or `RateLimited` middleware.
- **H6** profit report dates in UTC, "to" excluded, single day errors → Asia/Riyadh days, inclusive "to", `after_or_equal`; Riyadh dates on the client too.
- **H12** every tenant API GET switches `current_team` → only switch on an explicit workspace choice.
- **H14** admin TenantHealth N+1 → memoize per row or grouped queries.

**Frontend (H10, H15, H16, L1–L8):** cost-card note, keep query string on language switch, show 422s on unrendered fields + allow clearing a category margin, inbox pagination + batch phones, CSV download errors + Safari-safe `saveBlob`, Riyadh month on usage + invalid month state, onboarding store status for non-managers, `RangeEstimate` labels from the dictionaries (simplified MSA, not colloquial) + Arabic sr-only "Close", `aria-describedby` on fields + a real dialog for the mobile menu, no "-0 ر.س" + smaller attributed revenue, live approval countdown + a cart reference the merchant can find.

**Cleanup:** remove the old Inertia/React scaffold (`resources/js`, Inertia middleware, web routes and controllers that only served it) now that Next.js covers sign-in and settings. Keep: Fortify JSON routes, Sanctum, the Filament admin, webhooks. Before deleting, list what still depends on it (email verification page, passkeys, `welcome`, the `/dashboard` redirect) and decide each one; ask me only if a choice changes product behavior.

**Flaky test watch:** `ProfitReportTest` "recovers a known effect inside its interval" failed once in a combined run (seeded, couldn't reproduce). If it fails again, find the cause.

## How to work
- **Don't change product behavior beyond the issue list without asking me.**
- Every fix gets a regression test, and **prove the test fails on the old code** (stash the fix, run, restore), like Sessions 1–3 did.
- Keep `composer ci:check` green and `cd web && npm run e2e` passing (E2E uses its own DB `codex_e2e` and ports 8010/3010; my dev app runs on 3001).
- Verify any claim about Salla or Meta against their docs, or label it [افتراض].

## When you're done
- Mark each fixed item ✅ in `docs/reviews/2026-10-25-p1-p2-issues.md` and add a "Session 4" section like the others.
- Update `STATUS.md` and `CLAUDE.md` (Inertia gone → remove the scaffold notes).
- Give me the PR link and tell me in short Egyptian Arabic: what's fixed, what's left, and whether we're ready for P3 (the AI Sales Agent: E09/E10/E11).
