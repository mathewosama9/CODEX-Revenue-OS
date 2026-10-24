# Domain modules (ADR-001)

One Laravel monolith, one folder per module. The MVP modules (M01–M12) map to these folders:

| Folder | Module | First phase |
|---|---|---|
| `Identity` | M01 Identity, Access & Workspace | P1 |
| `Commerce` | M02 Commerce Integrations (`CommerceAdapter`, Salla) | P0b/P1 |
| `Messaging` | M03 Messaging Channels (WhatsApp Tech Provider, MM API) | P1 |
| `Inbox` | M04 Conversation Inbox & Handoff | P1 (lite) |
| `Recovery` | M08 Revenue Recovery Engine (`scheduled_actions`, discount policy) | P1 |
| `Measurement` | M09 Measurement & Attribution (holdout, cost ledger, profit report) | P1 |
| `Compliance` | M10 Consent & Compliance (`ConsentGate`, `audit_events`) | P1 |
| `Billing` | M11 Billing, Plans & Usage Metering | P1 (metering) |
| `Platform` | M12 Platform Administration & Ops | P1 (lite) |

`Knowledge` (M05), `SalesAgent` (M06) and `AiGateway` (M07) are created in P2. Modules M13–M21 get no folder before the P3 exit.

## Rules (enforced in `tests/Arch`)

1. A module's `Models` are used only inside that module. Others go through its public services or listen to its domain events.
2. Every model in `app/Modules/*/Models` uses `App\Shared\Tenancy\BelongsToTenant` and has a `tenant_id` column. The only exception is `Measurement` pooled tables (ADR-009), listed in the arch test.
3. `withoutGlobalScopes` is allowed only in `Platform` (admin) and `Measurement` (pooled jobs).
4. `Measurement` never imports `Messaging` or `Inbox` (no message content in pooled data — Meta terms, ADR-009).
5. Every queued job that touches tenant data uses `App\Shared\Tenancy\TenantAware` and carries a `public int $tenantId`.
