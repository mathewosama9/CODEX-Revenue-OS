# Architecture Decision Records

The status of each ADR in the plan ([09](../blueprint/09-technical-architecture.md) §9.20) after building P1.
**Accepted** = signed off by the founder. **Implemented** = built as the Blueprint proposed and waiting for sign-off.

| ADR | Decision | Status | In the code |
|---|---|---|---|
| ADR-001 | Laravel Modular Monolith, module boundaries enforced by tests | Implemented | `app/Modules/*`, `tests/Arch` |
| [ADR-002](ADR-002-frontend-nextjs.md) | Separate Next.js frontend; Laravel = API | **Accepted** (2026-10-25) | `routes/api.php`, Sanctum, CORS |
| ADR-003 | PostgreSQL + pgvector | Implemented | `compose.yaml`, tests on Postgres |
| ADR-004 | Shared DB + `tenant_id` + Global Scope that fails closed; RLS later | Implemented | `app/Shared/Tenancy` |
| ADR-005 | Hosting: GCP Dammam, Frankfurt as a temporary fallback | **Open** | Waiting on the GCP request |
| ADR-006 | WhatsApp Tech Provider + data localization region | **Open (region)** | Registration refuses to run without `META_DATA_LOCALIZATION_REGION` |
| ADR-007 | AI via Laravel AI SDK behind `AiGateway` | P2 | — |
| ADR-008 | PII separation: encryption + hashes + log redaction | Implemented | `encrypted` casts, `SubjectHasher`, `Redactor` |
| ADR-009 | Pooled data from order outcomes only, never message text | Implemented | Arch test: Measurement never imports Messaging |
| ADR-010 | `scheduled_actions` + SKIP LOCKED + `webhook_events` + at-most-once | Implemented | `Recovery/ScheduledActionDispatcher`, `MessageSender` |
| ADR-011 | Commerce Adapter + internal model | Implemented | `Commerce/CommerceAdapter`, `SallaAdapter` |
| ADR-012 | Billing: manual → Moyasar/Tap → Salla | P3 | Metering only for now |
| ADR-013 | Measurement: 10% customer holdout, ITT, interval + win probability | Implemented | `Measurement/*` (metric per independent review #1) |
| ADR-014 | Discount policy engine in code + approvals | Implemented | `Recovery/DiscountPolicy` |
| ADR-015 | Observability + PII redaction | Partial | Logs redacted. Sentry/Nightwatch need accounts |
