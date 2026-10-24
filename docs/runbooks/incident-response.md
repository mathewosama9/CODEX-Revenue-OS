# Runbook: security incident or data breach

> Contractual target: notify the affected merchant **within 24 hours** of discovery, so they can meet SDAIA's 72 hours and the Ministry of Commerce's 3 days (spine §7.6).

## In the first hour
1. **Stop the damage**: from the admin (`/admin` → Store health), pause sending for every affected merchant. The pause is recorded in `audit_events`.
2. If a token leaked: revoke it on the platform's side (Salla or Meta) and flag the connection `needs_reauth`.
3. Preserve the evidence: export the relevant `audit_events` and `webhook_events` **before** the retention purge runs.

## Within 24 hours
4. Identify the affected merchants and customers (counts only, no data in the notification).
5. Notify the merchant in writing: what happened, what data was involved, what we did, and what they need to do.
6. Record a `Sev1` with a timeline in `docs/runbooks/incidents/`.

## Afterwards
7. Blameless postmortem within 5 working days, plus a test that prevents the same failure again.
