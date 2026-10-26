# WooCommerce ↔ CODEX: the connector protocol (v1.1)

> For: whoever changes the Laravel WooCommerce adapter (`app/Modules/Commerce/WooCommerce/`) or the WordPress plugin (`integrations/wordpress/codex-connector/`). Both sides must follow this file; change it first, then both sides.
> Status: trials only (founder decision 2026-10-25). Salla stays the ICP platform.
> v1.1 (2026-10-26, [ADR-012](../adr/ADR-012-multi-currency.md)): any supported currency, money strings with the store's price decimals, the site timezone. A v1.0 plugin still works for 2-decimal stores.

WooCommerce has no abandoned-cart API and does not keep the customer's phone before the order. The **CODEX Connector** plugin captures the cart at checkout (with the customer's WhatsApp consent), keeps it in its own table, pushes events to us, and answers our questions about carts. Products, categories and orders come from the standard **WooCommerce REST API v3** with read-only keys.

## 1. Connecting a store (in the app)
1. The owner opens Onboarding → Connect store → WooCommerce and enters: **site URL** (https only), **consumer key** and **consumer secret** (WooCommerce → Settings → Advanced → REST API, permission *Read*).
2. We call `GET {site}/wp-json/wc/v3/system_status` (fallback `GET {site}/wp-json/wc/v3/products?per_page=1` + `GET wc/v3/data/currencies/current`) to check the keys, and read the store currency. **The currency must be one of `config/currencies.php`** (Arab currencies and USD), else we refuse with reason `currency` and a message listing the supported codes.
3. We read the site timezone from the public index `GET {site}/wp-json`: `timezone_string` (e.g. `Africa/Cairo`) when it is a valid PHP timezone, else a whole-hour `gmt_offset` as `Etc/GMT∓N` (`0` → `UTC`), else we keep the workspace's. Failing to read it never fails the connect.
4. **Workspace currency (ADR-012).** A workspace with no products, carts or orders takes the store's currency and timezone (`teams.currency`, `teams.timezone`), before the backfill reads any amount. A workspace that already has data keeps both: a store in another currency is refused with reason `currency_mismatch` (HTTP 409; "connect it to a new workspace"); the same currency reconnects.
5. We create the `store_connections` row for the current tenant: `provider = woocommerce`, `external_store_id = woo_<ulid lowercase>` (the **Store ID**), `site_url`, `credentials = {consumer_key, consumer_secret, connector_secret}` (encrypted). `connector_secret` = 32 random bytes, hex (64 chars). `TenantRoute::bind(woocommerce, store_id, tenant)`.
6. We show the **Store ID**, the **connector secret** (once; can be rotated) and the **API base URL** (`config('app.url')`). The owner pastes them into the plugin settings.

## 2. Signing (both directions)
Shared secret: `connector_secret`. Clock window: **±300 seconds**. Compare with constant-time equality (`hash_equals`).

Headers on every signed request:
- `X-Codex-Timestamp`: Unix seconds.
- `X-Codex-Signature`: `sha256=` + lowercase hex of HMAC-SHA256.
- `X-Codex-Event-Id`: UUID v4 (push events only; our de-duplication key).

Signed string:
- **Plugin → us (POST):** `"{timestamp}.{raw_body}"`.
- **Us → plugin (GET):** `"{timestamp}.GET.{route}?{query}"` where `route` is the REST route without the `/wp-json` prefix (e.g. `/codex/v1/carts`) and `query` is the query parameters sorted by key and encoded with RFC 3986 (PHP: `ksort($q); http_build_query($q, '', '&', PHP_QUERY_RFC3986)`); empty query → nothing after `?`. The plugin rebuilds it from `WP_REST_Request::get_route()` and `get_query_params()`.

## 3. Plugin → us: events
`POST {api_base}/webhooks/woocommerce/{store_id}`, `Content-Type: application/json`, body max 1 MB:

```json
{ "event": "cart.updated", "occurred_at": "2026-10-28T10:15:00Z", "plugin_version": "1.1.0", "data": { } }
```

Responses: `200` accepted (also for a duplicate `X-Codex-Event-Id`), `401` bad/missing signature or timestamp out of window, `404` unknown store id, `413` too large, `422` malformed. `429` slow down (our per-IP webhook limit). The plugin retries on network errors, `429` and 5xx (Action Scheduler, backoff 1m, 5m, 30m, 2h, 6h), never on any other 4xx.

| `event` | `data` | What we do |
|---|---|---|
| `cart.updated` | a **Cart** (§5). Reconciliation also reads carts from `GET /carts`; both paths record the consent box before the cart (`WooCheckoutConsent`) | `recordAbandonedCart`; if `consent` is true and `phone` is set, grant WhatsApp marketing consent (source `checkout_opt_in`, evidence: `consent_text`, `consent_text_version`) |
| `cart.purchased` | `{ "id": "c_…", "order_id": "789" }`, sent when the linked order is **paid** (`woocommerce_payment_complete`, or status processing/completed, e.g. cash on delivery), not when it is placed: a customer who leaves at the payment page can still be reminded | `markCartPurchased` |
| `order.created`, `order.updated` | `{ "id": "789" }` | fetch `GET wc/v3/orders/789`, then `recordOrder` |
| `product.updated` | `{ "id": "123" }` | fetch `GET wc/v3/products/123` (+ variations), then `recordProduct` |
| `product.deleted` | `{ "id": "123" }` | `removeProduct` |
| `connector.ping` | `{ "woocommerce_version": "9.x", "checkout": "classic" \| "blocks", "currency": "EGP", "price_decimals": 2, "timezone": "Africa/Cairo" }` (v1.1 adds `price_decimals` and `timezone` = `wp_timezone_string()`, which can be an offset like `+02:00`) | update `last_webhook_at`; the plugin's "Test connection" button |
| `plugin.deactivated` | `{}` | log it and record it for store health; nothing is deleted |

Unknown events: `200` and ignored (forward compatible).

## 4. Us → plugin: questions
All under `{site}/wp-json/codex/v1/`, signed (§2), JSON:

| Request | Response |
|---|---|
| `GET /ping` | `{ "ok": true, "plugin_version": "1.1.0", "woocommerce_version": "…", "currency": "EGP", "price_decimals": 2, "timezone": "Africa/Cairo", "checkout": "classic" \| "blocks" }` |
| `GET /carts?since={ISO8601 UTC}&page={n}&per_page={≤100}` | `{ "carts": [Cart…], "next_page": 2 \| null }`, carts updated at or after `since`, oldest first |
| `GET /carts/{id}` | a Cart, or `404 { "code": "not_found" }` |

`401` for a bad signature. In `abandonedCart()`: `status: purchased` returns the cart marked purchased (our cart is marked purchased and the sequence cancelled with reason `purchased`, like Salla); `expired` or a 404 returns null ("cart gone").

Event de-duplication key on our side: `"{store_id}:{X-Codex-Event-Id}"` (event id: `[A-Za-z0-9-]{8,64}`). Known limit of v1: the event id is not part of the signed string, so a captured body could be replayed with a new id inside the 5-minute window; every effect is an idempotent upsert. A later version should sign it (still open in v1.1).

## 5. The Cart object
```json
{
  "id": "c_01jbx8…",                 // plugin id, ≤ 64 chars, stable for the cart
  "status": "open",                  // open | purchased | expired
  "currency": "SAR",                 // the store currency; must be the workspace's (below)
  "total": "199.00",                 // decimal string, store currency, the store's price decimals (wc_get_price_decimals(): "12.345" KWD, "25000" IQD), taxes included as shown at checkout
  "items": [
    { "product_id": "123", "variation_id": "456", "name": "عود كمبودي ملكي - 50ml",
      "quantity": 1, "unit_price": "199.00", "category_id": "15" }
  ],
  "phone": "+966551234567",          // E.164; ONLY when consent is true, else null
  "consent": true,
  "consent_text": "أوافق على استلام رسائل واتساب من المتجر عن طلبي والعروض.",
  "consent_text_version": "a1b2c3d4", // first 8 hex of sha1(consent_text)
  "recovery_url": "https://shop.example/?codex_cart=<token>", // restores the cart and opens checkout
  "customer_id": "12",               // WordPress user id, or null for guests
  "updated_at": "2026-10-28T10:15:00Z",
  "abandoned_at": "2026-10-28T10:15:00Z"
}
```
Mapping to `CartData`: `externalId = id`, `customer = {externalId: customer_id, phone}`, `totalHalalas = Currency::toMinor(total)` in the workspace currency (exact string parsing, no floats: "12.345" KWD → 12345), `checkoutUrl = recovery_url`, `abandonedAt = abandoned_at`, `purchased = status == purchased`, items → `LineItemData(productExternalId = product_id, name, quantity, unitPriceHalalas, categoryExternalId)`.

**Never mix currencies:** a cart (plugin) or order (REST v3 `currency`) whose currency is not the workspace's (the merchant changed the store currency after connecting) is logged as a warning and skipped, on the webhook and on reconciliation. Products have no currency field and are read in the workspace currency. All REST amounts (`price`, `total`, `discount_total`, …) go through the same `toMinor`.

## 6. Privacy rules (plugin)
- The phone leaves the site **only** when the customer ticks the consent box; without consent the cart is not sent at all.
- The consent box is unticked by default; its text is set in the plugin settings (default above) and must be reviewed by the lawyer before any real customer.
- The plugin stores carts for at most 30 days, then deletes them (daily cleanup).
- [افتراض] Collecting a phone before the order is placed needs the lawyer's opinion before real customers (STATUS.md: no real merchant before Gate 0).

## 7. Known limits (v1.1)
- Unticking the box after a `cart.updated` is not sent to us: the plugin stops returning the cart (404, so no reminder for it), but the earlier consent grant stays in our records. v1.1: a `consent.withdrawn` event.
- On Blocks checkout the box is registered with `location: contact`, which WooCommerce may pre-fill for returning signed-in customers; check on the live site, else move it to the order location.
- Revoked REST keys make the sync jobs fail every 10 minutes; there is no "needs re-connect" state for WooCommerce yet.
- The plugin keeps carts (with phone, for consented ones) on the merchant's site for 30 days; our data-deletion request does not reach them. For the lawyer.
- **Phones in local format are read as Saudi** (plugin `normalize_phone` and our `PhoneNumber`): an Egyptian customer typing `010…` without `+20` is dropped (no phone, so no reminder). Until the country comes from the store (WooCommerce base country), Egyptian and other non-Saudi stores need the international format at checkout.
