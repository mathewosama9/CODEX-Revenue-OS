=== CODEX Connector ===
Contributors: codex
Tags: woocommerce, abandoned cart, whatsapp, recovery
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
WC requires at least: 8.0
WC tested up to: 9.9
Stable tag: 1.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connects your WooCommerce store to CODEX. يربط متجر WooCommerce الخاص بك بمنصة CODEX.

== Description ==

**English:** CODEX Connector captures abandoned carts at checkout — only when the customer ticks the WhatsApp consent box — and keeps CODEX in sync with your orders and products, so CODEX can recover lost sales over the official WhatsApp Business Platform.

**العربية:** يلتقط CODEX Connector السلات المتروكة في صفحة الدفع — فقط عندما يحدد العميل خانة الموافقة على واتساب — ويُبقي CODEX على اطلاع بطلباتك ومنتجاتك، ليتمكن من استرجاع المبيعات عبر منصة واتساب للأعمال الرسمية.

Features:

* An unticked WhatsApp consent checkbox at checkout (classic checkout and, on WooCommerce 8.9+, the Checkout block).
* Captured carts with a recovery link (`?codex_cart=…`) that restores the cart and opens checkout.
* Signed (HMAC-SHA256) events to CODEX for carts, orders and products, with automatic retries (Action Scheduler).
* A signed read-only REST API (`/wp-json/codex/v1/…`) that CODEX uses to check cart status.
* HPOS (custom order tables) compatible.

== Installation ==

1. In CODEX, open Onboarding → Connect store → WooCommerce and connect the store. Copy the **API base URL**, **Store ID** and **Connector secret** shown there.
2. In WordPress, go to Plugins → Add New → Upload Plugin, choose `codex-connector.zip`, then Install and **Activate**.
3. Go to WooCommerce → CODEX Connector, paste the API base URL, Store ID and secret, and click **Save Changes**.
4. Click **Test connection**. You should see "Connected to CODEX. HTTP 200".
5. Open your checkout page and check that the WhatsApp consent checkbox appears (unticked).

التثبيت بالعربية:

1. في CODEX افتح الإعداد ← ربط المتجر ← WooCommerce، وانسخ رابط الـAPI ومعرّف المتجر والمفتاح السري.
2. في ووردبريس: الإضافات ← أضف جديد ← رفع إضافة، اختر `codex-connector.zip` ثم ثبّت و**فعّل**.
3. من WooCommerce ← CODEX Connector الصق القيم الثلاث واحفظ.
4. اضغط **اختبر الاتصال** وتأكد من ظهور "HTTP 200".

== Privacy ==

* The customer's phone number and cart leave your site **only** when the customer ticks the consent checkbox. Without consent, nothing is sent and any stored phone is cleared.
* The checkbox is unticked by default. Its text can be changed in the settings and **must be reviewed by your lawyer** before real customers use it.
* Captured carts are stored in the `{prefix}codex_carts` table for at most 30 days, then deleted automatically (daily cleanup).
* The connector secret is stored in your WordPress options and is never shown in pages or sent to the browser. Logs (WooCommerce → Status → Logs, source `codex-connector`) never contain full phone numbers.
* Deactivating the plugin notifies CODEX; uninstalling it deletes the table and the settings.

الخصوصية: لا يغادر رقم الجوال ولا السلة موقعك إلا إذا حدد العميل خانة الموافقة. تُحذف السلات المحفوظة تلقائيًا بعد 30 يومًا.

== Changelog ==

= 1.1.1 =
* Refunds, partial ones included, now send an order update to CODEX, so its profit report subtracts them.

= 1.1.0 =
* Connector protocol v1.1: amounts use the store's price decimals (3 for KWD, 0 for IQD), any currency CODEX supports, and the ping reports the site timezone.
* Removed the "store currency is not SAR" warning.

= 1.0.0 =
* First release (connector protocol v1).
