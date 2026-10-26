# Architecture Decision Records

The status of each ADR in the plan ([09](../blueprint/09-technical-architecture.md) §9.20) after building P1, P3, the site, WooCommerce and multi-currency (2026-10-27).
**Accepted** = signed off by the founder. **Implemented** = built as the Blueprint proposed and waiting for sign-off.

| ADR | Decision | Status | In the code |
|---|---|---|---|
| ADR-001 | Laravel Modular Monolith, module boundaries enforced by tests | Implemented | `app/Modules/*`, `tests/Arch` |
| [ADR-002](ADR-002-frontend-nextjs.md) | Separate Next.js frontend; Laravel = API | **Accepted** (2026-10-25) | `routes/api.php`, Sanctum, CORS |
| ADR-003 | PostgreSQL + pgvector | Implemented | `compose.yaml`, tests on Postgres |
| ADR-004 | Shared DB + `tenant_id` + Global Scope that fails closed; RLS later | Implemented | `app/Shared/Tenancy` |
| ADR-005 | Hosting: GCP Dammam, Frankfurt as a temporary fallback | **Open** | Waiting on the GCP request |
| ADR-006 | WhatsApp Tech Provider + data localization region | **Open (region)** | Registration refuses to run without `META_DATA_LOCALIZATION_REGION` |
| [ADR-007](ADR-007-ai-orchestration.md) | AI: one agent + 5 tools behind `AiGateway` (Laravel AI SDK), Claude Haiku 5.5 only, Voyage embeddings, Assisted only | **Accepted** (2026-10-25) | `app/Modules/AiGateway`, `app/Modules/SalesAgent`, `tests/Arch` |
| [ADR-008](ADR-008-pii-and-llm.md) | PII: encryption + hashes + log redaction, and pseudonymization before the LLM | Implemented (tech) / **Open (legal)** | `encrypted` casts, `SubjectHasher`, `Redactor`, `AiGateway/Pseudonymizer` |
| ADR-009 | Pooled data from order outcomes only, never message text | Implemented | Arch test: Measurement never imports Messaging |
| ADR-010 | `scheduled_actions` + SKIP LOCKED + `webhook_events` + at-most-once | Implemented | `Recovery/ScheduledActionDispatcher`, `MessageSender` |
| ADR-011 | Commerce Adapter + internal model | Implemented | `Commerce/CommerceAdapter`, `SallaAdapter` |
| [ADR-012](ADR-012-multi-currency.md) | One currency and one timezone per workspace | **Accepted** (2026-10-26) | `teams.currency`/`timezone`, `Shared/Money/Currency`, `WorkspaceLocale`, `config/currencies.php` |
| ADR-013 | Measurement: 10% customer holdout, ITT, interval + win probability | Implemented | `Measurement/*` (metric per independent review #1) |
| ADR-014 | Discount policy engine in code + approvals | Implemented | `Recovery/DiscountPolicy` |
| ADR-015 | Observability + PII redaction | Partial | Logs redacted. Sentry/Nightwatch need accounts |
| ADR-016 | Billing: manual → Moyasar/Tap → Salla | P3 | Metering only for now |

**Numbering note:** the Blueprint (00, 05–10, and 14 D-T11) calls the billing decision **ADR-012**. That number went to multi-currency on 2026-10-26, so billing is **ADR-016** from now on. Read "ADR-012" in the Blueprint as ADR-016.
