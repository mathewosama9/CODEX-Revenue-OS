# Phase 3 session prompt: the AI Sales Agent

> Copy everything below the line and paste it into a new session opened on the `CODEX-Revenue-OS` folder.

---

You're working with me on **CODEX Revenue OS**. Reply to me in **Egyptian Arabic**, short and plain: what you did, and what I need to do. When there's a decision, give me options with your recommendation first. If I say "شوف انت الافضل", take your recommendation and go.

## Naming, so we don't mix things up
Our "Phase 3" is **"P2 — Conversational Selling MVP" in the Blueprint**. It covers epics **E09** (catalog and knowledge), **E10** (AiGateway and the golden set), **E11** (the AI Sales Agent), and **E12b** (the rest of the inbox: drafts, takeover). **E16 (COD) is out of scope:** it depends on P0 interview results.

## Read these first, in this order
1. `CLAUDE.md`: rules, stack, how to run things. Read `web/AGENTS.md` before any Next.js code.
2. `STATUS.md`, and `docs/reviews/2026-10-25-p1-p2-issues.md` (closed; it shows how we work: every fix has a test proven to fail on the old code).
3. The Blueprint for this phase:
   - `docs/blueprint/07-master-development-phases.md` §7.4: goal, deliverables, **exit criteria**.
   - `docs/blueprint/06-mvp-vs-future-matrix.md`: the **E09, E10, E11 and E12b** stories, with their acceptance criteria and technical tasks.
   - `docs/blueprint/09-technical-architecture.md`: AiGateway, the validator, PII, 9.11 (autonomy levels and night replies).
   - `docs/blueprint/14-key-decisions.md`: D-T09 (ADR-007, AI orchestration) and D-T10 (ADR-008, PII with the LLM).
   - `docs/blueprint/05-product-modules-map.md`: M05, M06 and M07.
4. The code you will build on:
   - `app/Modules/*`: Commerce (catalog, `checkout_url`), Messaging (MessageSender, templates, the 24h window), Inbox, Measurement (`CostKind::Llm` in the cost ledger), Billing (UsageMeter), Platform (admin, kill switch).
   - `app/Modules/README.md` and `tests/Arch`: the module boundaries.

## Git
Branch `feat/P3-ai-agent` from `main`. If `fix/operations-cleanup` isn't merged yet, branch from it. Small commits per epic, then push.

## Decisions to raise with me at the start (recommend, then wait only where marked)
1. **The LLM provider and models** (finalising ADR-007). My leaning is Claude through the official **Laravel AI SDK**, behind `AiGateway`: a cheap model by default, escalating to a stronger one by rule. Check the model IDs and prices from the official docs (use the `claude-api` skill), against the **≤ $0.03 per conversation** target. **Wait for my approval before using any paid key.**
2. **Embeddings for search:** which provider. Check whether the provider above offers them; if not, recommend one and say why.
3. **The daily AI spend cap per merchant** (US-E10-06): propose a number.
4. Write **ADR-007** (detailed) and **ADR-008** (PII with the LLM: pseudonymization, zero-retention) in `docs/adr/`. Mark anything legal as **[افتراض]** for the lawyer.

## Build order (each step with tests before the next)
1. **E10 AiGateway first**, with a **fake provider** in tests, so everything works without a key:
   - Every LLM call goes through `AiGateway`, and an **arch test forbids any other path**.
   - Metering per tenant, per conversation and per model (tokens and cost) → cost ledger (`CostKind::Llm`) + `usage_daily`.
   - Pseudonymization before the call, with a test showing **zero PII** across a sample of calls.
   - The daily cap per merchant. When it's hit: handoff to a person, never a reply without the validator.
   - Rules-based routing, timeouts, and a fallback to handoff if the provider is down.
2. **`ResponseValidator` (deterministic):** every number, price, quantity and percentage in a reply must match a value a tool returned in the same conversation, after normalising Arabic-Indic digits and currency. A mismatch blocks the reply → one regeneration → then handoff. Plus an automatic switch back to Assisted if the block rate goes over 2% (US-E10-07).
3. **E09 catalog and knowledge:**
   - Product sync from Salla, using the existing webhooks and reconcile. Prices are at most 15 minutes old.
   - Hidden or deleted products leave the index.
   - **A single Arabic normalisation layer** (أ/إ/ا، ة/ه، ى/ي، diacritics، basic Arabizi), shared with the opt-out word detection.
   - Hybrid search: Postgres FTS/trigram + pgvector.
   - FAQ, shipping and returns entered by the merchant (`knowledge_documents`/`chunks`).
   - The `CatalogSearch` and `KnowledgeRetriever` interfaces.
4. **E11 the agent:**
   - One agent with tools limited by name: `search_products`, `get_product`, `get_policy`, `get_cart_link`, `handoff`.
   - **No discount tool, no custom payment link, no order changes.** A config/arch test enforces this, not the prompt (Rule 9).
   - **Deterministic handoff rules before the LLM**: asking for a person, a complaint, a discount request, a high cart value.
   - Out-of-scope questions get a polite refusal.
   - Respect the 24-hour window: free-form text inside it only, a template outside it.
   - **Assisted mode is the only mode switched on:** every reply is a draft, sent only after a member approves it.
   - Scoped Autonomous is built but off: behind an admin feature flag, plus the owner's written approval, plus ≥4 weeks of Assisted.
   - The agent never replies to someone who opted out or has no consent.
5. **E12b in the inbox (Next.js):**
   - A drafts screen (approve, edit, reject), and the edit rate is measured.
   - An "AI paused" state per conversation.
   - Takeover and "hand back to the AI", both recorded in `audit_events`.
   - Handoff reasons shown, and a read-only log of what the agent did.
   - **An E2E test:** after takeover, zero AI replies, even if the customer sends 10 messages in a row.
6. **Merchant screens:**
   - A knowledge editor (FAQ, shipping, returns) in Arabic.
   - AI settings: the mode (read-only until the conditions are met) and the daily cap.
   - Everything RTL/LTR, following the existing frontend rules.
7. **The golden set and the eval runner:**
   - Build the runner (`eval_runs`) in CI, blocking any price regression.
   - Write the case format, plus a **starter set (~50 Saudi cases + 10 Egyptian)** covering prices, stock, discount requests, out-of-scope and handoff.
   - Reaching ≥300 needs labelling by a person: say so clearly, and add `docs/phases/P3-ai-agent/golden-set-guide.md` explaining how to add cases.

## Rules for this phase
- **Rule 9:** the AI never decides a discount or anything that moves money. Discounts come only from `DiscountPolicy` in code.
- **Rule 8:** the official WhatsApp API only. The agent stays inside the merchant's catalog (Meta's policy on AI chatbots: we are a commerce platform, not a general chatbot).
- **No live merchant before Gate 0.** Everything is tested with fakes and the demo store (`DemoStoreSeeder`; extend it with products and a FAQ).
- **No Python or LangGraph** (Rule 6). One agent, not multi-agent.
- Any claim about Laravel AI SDK, Meta or Salla: verify it against their docs, or label it **[افتراض]**.
- Every fix has a test, and prove the test fails on the old code. Keep `composer ci:check` green and `cd web && npm run e2e` passing.
- Don't change Phase 1/2 behaviour (recovery, report, consent) without asking me.

## When you're done
- `docs/phases/P3-ai-agent/testing-guide.md` (like the P2 one): how to try it with the demo store, and what needs a real key.
- Update `STATUS.md` and `CLAUDE.md`.
- Give me the PR link, and tell me in short Egyptian Arabic:
  - what works;
  - where we stand against the phase exit criteria (100% price accuracy on ≥300 cases, ≤ $0.03 per conversation, handoff ≤40%, ≥4 weeks Assisted);
  - what you need from me (keys, labelling, the lawyer's opinion).
