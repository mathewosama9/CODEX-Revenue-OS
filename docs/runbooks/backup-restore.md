# Runbook: backup and restore

**Targets** [proposal, needs founder approval]:

| Target | Value |
|---|---|
| RPO (acceptable data loss) | ≤15 minutes (PITR on managed Postgres) |
| RTO (outage inside the region) | ≤4 hours |
| RTO (loss of the whole region) | ≤48 hours. Where the copy lives depends on ADR-005 |

## Data we can't get back from anywhere else (it must be in the backup)
`consent_records`, `experiment_assignments`, `exposures`, `cost_ledger_entries`, `audit_events`, `discount_approvals`, `profit_report_snapshots`.

The rest (catalog, carts, orders) can be pulled from Salla again through reconciliation.

## Restore drill (before the first live merchant, then quarterly)
1. Restore the latest backup into a separate staging environment.
2. `php artisan migrate --pretend` must show no differences.
3. Run `./vendor/bin/pest --testsuite=Isolation` against the restored environment.
4. Compare row counts for the tables above with production.
5. Record the result and how long it took.

## After any outage longer than 15 minutes
Salla webhooks from that window may be lost. Reconciliation runs automatically every 10 minutes and picks them up. Check `reconciliation_gap_count` in the admin.
