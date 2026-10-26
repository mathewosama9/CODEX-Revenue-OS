# ADR-012: One currency and one timezone per workspace

- **Status:** Accepted (founder, 2026-10-26)
- **Context:** The founder's own WooCommerce store sells in Egyptian pounds, and "we have many sites in every Arab currency and the dollar". Until now every amount was Saudi riyals ×100 (`*_halalas`), costs were converted at a fixed 3.75, the agent said «ريال», and times were Riyadh time.

## Decision
1. **A workspace has one currency and one timezone** (`teams.currency`, ISO 4217, and `teams.timezone`). They are set from the store when it is connected and cannot change once the workspace has commerce data. Read them through `App\Shared\Tenancy\WorkspaceLocale`.
2. **Money stays integer minor units of the workspace currency.** The columns keep their historic `*_halalas` names: read "halalas" as "minor units". The exponent comes from `config/currencies.php` (2 for SAR/EGP/AED/USD…, 3 for KWD/BHD/OMR/JOD/TND/LYD, 0 for IQD/LBP/SYP/YER/SOS/DJF/KMF). Columns are `bigint`.
3. **Prices are never converted.** Store amounts enter through `Currency::toMinor()` (string parsing, no floats) and leave through `toMajor()`.
4. **Only costs priced in dollars are converted** (Meta messages, AI): `usd_rate` in `config/currencies.php`, dated, approximate for floating currencies [افتراض], refreshed by hand. The cost ledger keeps micros of the workspace currency (`Currency::microsPerMinor()` links it to minor units).
5. **Thresholds are set in dollars in config** and converted per workspace (`Currency::minorFromUsd`), so one default works in every currency; merchants can still set their own in their currency.
6. **The sales agent speaks the workspace currency**; the validator treats a number next to **any** supported currency's word as money, so a reply in the wrong currency is checked like any price.
7. **Local time** (quiet hours, report days, budget months, usage days, the AI daily cap) uses the workspace timezone.

## Consequences
- One store per workspace stays the rule; a merchant with stores in two currencies uses two workspaces.
- Reports never mix currencies. Comparing workspaces in different currencies (e.g. pooled priors) needs a conversion step later.
- Meta per-country rates without a source are marked estimated (CLAUDE.md: no invented numbers).
