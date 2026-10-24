# سلة (Salla) كأول Integration وكـPlatform Risk

> **تاريخ الاطلاع:** 2026-10-21. المعلومات دي بتتقادم بسرعة (الـchangelog بتاع سلة بيتحدث كل كام أسبوع)، ولازم تتراجع قبل ما نبدأ أي كود، وبعدين كل ربع سنة.
> **مرتبط بـ:** [competitive-feature-matrix.md](competitive-feature-matrix.md) · [karam-ai.md](karam-ai.md) · [mersal-ai.md](mersal-ai.md) · [thikaa.md](thikaa.md)

## المنهجية
- قرينا الـdocs الرسمية بتاعة سلة للمطورين (`docs.salla.dev`)، وبوابة الشركاء (`partners.salla.com`)، وسياسة متجر التطبيقات، ومركز المساعدة (`help.salla.sa`)، وصفحات سلة الرسمية (`salla.com`).
- عملنا نفس الحاجة لزد (`docs.zid.sa`، `zid.sa`، `help.zid.sa`).
- استخدمنا مصادر خارجية (StoreLeads وShoprank وأخبار) للأرقام بس، وعلّمناها إنها أقل موثوقية.
- الأرقام المهمة حاولنا نقارنها بمصدرين. لما لقينا تناقض كتبناه صراحة في قسم 9.

## مفتاح التصنيف
- **[مؤكدة]**: معاها مصدر (رابط + تاريخ الاطلاع 2026-10-21). أغلبها من سلة أو زد نفسهم، يعني بتأكد إن الشركة **بتقول** كده، مش إننا جربناه.
- **[افتراض]**: استنتاج مننا أو حاجة محتاجة تتختبر.
- **[اقتراح]**: توصية لـCODEX.
- **غير مؤكدة**: دورنا وملقيناش دليل. ده **مش** معناه إن الحاجة مش موجودة.

## حدود البحث
- **ميزانية البحث خلصت قبل ما نخلّص كل الأسئلة.** في 3 حاجات مقدرناش نتحقق منها: هل سلة أطلقت AI assistant للتجار، وتفاصيل منتج "WhatsApp by Salla" وسعره، ونسبة عمولة زد على التطبيقات. التلاتة متعلّمين **غير مؤكدة**.
- **نسبة عمولة سلة على التطبيقات مش منشورة.** الرقم اللي تحت محسوب من مثال في صفحة الشركاء، مش رقم معلن.
- **مفيش SLA رسمي لمراجعة التطبيقات.** اللي عندنا شكوى واحدة من partner.
- صفحات كتير في `docs.salla.dev` بتعرض الـschema عن طريق components مبتتقراش، فاعتمدنا على أمثلة الـJSON. ممكن يكون في fields ناقصة.
- أرقام عدد المتاجر من المصادر الخارجية متناقضة جدًا (قسم 9)، ومنهجيتها غالبًا بتقلل عدد متاجر سلة اللي على subdomain.
- مجربناش أي API بإيدينا، ومفيش عندنا demo store.

---

## 1. الخلاصة التنفيذية

| السؤال | الإجابة المختصرة | التصنيف |
|---|---|---|
| هل الـAPI بتاع سلة كفاية للـMVP؟ | أيوه، في الغالب. فيه webhook للسلات المتروكة بيبعت رقم الموبايل ورابط الـcheckout، وفيه webhooks للطلبات والمنتجات والعملاء، وBilling جاهز. | [مؤكدة] (قسم 3 و4) |
| هل نقدر ننشئ cart أو payment link من الـAPI؟ | مفيش endpoint لإنشاء cart. فيه `POST /orders` بحالة `pending_payment` ومعاه `checkout_url` لدفع الباقي، بس الـdocs مش واضحة. | جزئيًا [مؤكدة] و**غير مؤكدة** إنه بيشتغل كـpayment link |
| هل في consent جوه بيانات السلة المتروكة؟ | لأ. مفيش أي field للـmarketing opt-in في السلة. | [مؤكدة] (من الـschema المعروض) |
| هل سلة نفسها بتنافس؟ | أيوه. عندها تذكير سلات متروكة built-in (SMS وEmail وPush وWhatsApp)، لحد 5 تذكيرات، ومعاه خصم. | [مؤكدة] |
| العمولة على التطبيق؟ | مش معلنة. المثال اللي في صفحة الشركاء بيطلّع حوالي **30%**. | [افتراض] محسوب |
| أكبر خطر؟ | إن سلة تحدد التوزيع والفلوس والـAPI، وتقدر توقف التطبيق أو تغيّر الشروط في أي وقت. وكمان بتتوسع رأسيًا (Ads وPayments). | [مؤكدة] + [افتراض] |
| الترتيب المقترح | سلة الأول، وزد تاني، وبعدهم Shopify وWooCommerce. لازم يكون في Adapter layer من أول يوم. | [اقتراح] |

---

## 2. Salla Partners وOAuth

### 2.1 بوابة الشركاء وأنواع التطبيقات
| البند | التفاصيل | التصنيف |
|---|---|---|
| أنواع التطبيقات | **Public** (بيظهر في متجر التطبيقات)، و**Private** (لتاجر معيّن ومش بيظهر في البحث)، و**Shipping** (ممكن يكون Public أو Private) | [مؤكدة] https://docs.salla.dev/421410m0 (2026-10-21) |
| بيئة التطوير | Demo store بيحاكي متجر حقيقي، والـOTP الافتراضي `1111` | [مؤكدة] نفس المصدر |
| تكلفة الانضمام كـpartner | **غير مؤكدة**. السؤال موجود في الـFAQ بتاع صفحة الشركاء، بس الإجابة مش ظاهرة | **غير مؤكدة** — https://partners.salla.com/en/apps (2026-10-21) |
| عدد المطورين | "Join over 500 App Developers" | [مؤكدة] https://partners.salla.com/en/apps (2026-10-21) |
| App Events | سلة بتبعت events للتطبيق نفسه: `app.store.authorize` و`app.updated` و trial وsubscription (started/ended/renewed). و`app.subscription.started` بقى فيه object اسمه `promotion` من 2025-08-31 | [مؤكدة] https://docs.salla.dev/421410m0 + https://docs.salla.dev/421127m0 (2026-10-21) |

### 2.2 OAuth: Easy Mode وCustom Mode
| البند | التفاصيل | التصنيف |
|---|---|---|
| Easy Mode | سلة بتطلّع الـaccess token وتبعته للـwebhook URL بتاعك في event اسمه `app.store.authorize`، فأنت مش بتتعامل مع الـcallback | [مؤكدة] https://docs.salla.dev/doc-421118 (2026-10-21) |
| **Easy Mode إجباري للتطبيقات المنشورة** | النص: "Easy Mode is the only way allowed for published apps on the Salla App Store" | [مؤكدة] نفس المصدر |
| Custom Mode | Callback URL ومعاه `code`، وبعدين POST على `https://accounts.salla.sa/oauth2/token`. الـdocs بتقدّمه كحاجة للتجربة | [مؤكدة] نفس المصدر |
| عمر الـAccess token | أسبوعين (14 يوم) | [مؤكدة] نفس المصدر |
| عمر الـRefresh token | شهر. وبيتبعت بس لو الـscope فيه `offline_access` | [مؤكدة] نفس المصدر |
| **الـRefresh token بيتستخدم مرة واحدة** | لو استخدمته تاني (حتى في requests متوازية)، سلة بتلغيه وبتلغي كل الـaccess tokens اللي طلعت منه، و**التاجر لازم ينزّل التطبيق من جديد**. الـdocs بتنصح بـmutex أو lock | [مؤكدة] نفس المصدر |
| تحديث التطبيق | `app.updated` وبعده authorize event فيه tokens جديدة لازم تتخزن | [مؤكدة] نفس المصدر |
| تناقض في الـdocs | الـauthorize response فيه `expires`، والـrefresh response فيه `expires_in`، والصيغة مرة timestamp ومرة ثواني | [مؤكدة] نفس المصدر |

**[افتراض]** إن Easy Mode يبقى إجباري معناه إن أي تاجر عايز يستخدم CODEX كتطبيق public لازم ينزّله من متجر سلة. يعني **قناة التوزيع نفسها بتعدي على سلة**.

**[اقتراح]** الـtoken refresh يبقى job واحدة لكل tenant وعليها distributed lock (Redis)، ومتتعملش أبدًا جوه request عادي. Bug واحد هنا هيخلّي التجار يعيدوا تنزيل التطبيق، وده churn مباشر.

---

## 3. الـAPIs والـWebhooks

### 3.1 Rate Limits
| خطة التاجر | Requests/minute لكل متجر | بعد ما تعدّي الحد | التصنيف |
|---|---|---|---|
| Plus | 120 | Leaky bucket بيسمح بـrequest واحد في الثانية لحد ما الدقيقة تخلص | [مؤكدة] https://docs.salla.dev/doc-421125 (2026-10-21) |
| Pro | 360 | نفس الكلام | [مؤكدة] نفس المصدر |
| Special | 720 | نفس الكلام | [مؤكدة] نفس المصدر |
| Basic (المجانية) | مش مذكورة في الجدول | — | **غير مؤكدة** |
| Customers endpoint | 500 request كل 10 دقايق، فوق حد الخطة | — | [مؤكدة] نفس المصدر |

- الـHeaders: `X-RateLimit-Limit` و`X-RateLimit-Remaining` و`Retry-After` و`X-RateLimit-Reset`. القيم اللي في المثال (60 و120 و180) مش متطابقة مع الجدول. [مؤكدة] نفس المصدر
- لو عديت الحد أو حصل "unusual behavior"، ممكن يحصل "temporary access restrictions". [مؤكدة] نفس المصدر
- **[افتراض]** الحد مربوط **بخطة التاجر** مش بالتطبيق. ده معناه إن تاجر Plus (وده الشريحة الأرجح لينا) بيدّينا 120 request في الدقيقة، **ومش واضح لو الرقم ده مشترك مع باقي التطبيقات المتنزّلة عنده ولا لأ** (غير مؤكدة). الـbackfill الأولاني لمتجر فيه آلاف الطلبات والعملاء هياخد وقت، وحد العملاء (500 كل 10 دقايق) هو اللي هيبطّأنا أكتر حاجة.

### 3.2 الـWebhooks (أسماء الـevents بالظبط)
المصدر: [مؤكدة] https://docs.salla.dev/doc-421119 + https://docs.salla.dev/doc-433812 + https://docs.salla.dev/1726838m0 (2026-10-21)

| الفئة | الـEvents |
|---|---|
| Order | `order.created`، `order.updated`، `order.status.updated`، `order.cancelled`، `order.refunded`، `order.deleted`، `order.products.updated`، `order.payment.updated`، `order.coupon.updated`، `order.total.price.updated`، `order.shipment.creating/created/cancelled`، `order.shipment.return.creating/created/cancelled`، `order.shipping.address.updated` |
| Product | `product.created`، `product.deleted`، `product.quantity.low`، `product.price.updated`، `product.status.updated`، `product.image.updated`، `product.category.updated`، `product.brand.updated`، `product.tags.updated`. أما `product.updated` و`product.available` فاتعملهم deprecate في 2025-12-15 |
| Customer | `customer.created`، `customer.updated`، `customer.login`، `customer.otp.request` |
| **Abandoned Cart** | `abandoned.cart` (اتعملت أو اتحدثت)، `abandoned.cart.status.changed`، `abandoned.cart.purchased` (الاتنين دول اتضافوا 2025-03-28). وفي App Functions بيظهر كمان `abandoned.cart.updated` |
| Cart/Coupon | `coupon.applied` |
| تانية | `invoice.created`، `review.added`، `specialoffer.created/updated`، `shipment.*`، `shipping.zone.*`، `shipping.company.*`، `category.*`، `brand.*`، `store.branch.*`، `storetax.created` |

| سلوك الـWebhook | التفاصيل | التصنيف |
|---|---|---|
| Timeout | حوالي 30 ثانية | [مؤكدة] https://docs.salla.dev/doc-421119 (2026-10-21) |
| Retries | لحد 3 مرات، بين كل مرة والتانية حوالي 5 دقايق | [مؤكدة] نفس المصدر |
| Security | `signature` (HMAC-SHA256 على الـraw body، وده الافتراضي) أو `token` أو `none`. والـheaders هما `X-Salla-Security-Strategy` و`X-Salla-Signature` | [مؤكدة] نفس المصدر |
| Version | الـwebhooks الجديدة بتطلع على v2 by default | [مؤكدة] نفس المصدر |

**[افتراض]** 3 محاولات بس على مدى 15 دقيقة تقريبًا معناها إن أي downtime عندنا أطول من كده هيضيّع events. **[اقتراح]** نرجع للـpolling كـreconciliation: نسحب `GET /carts/abandoned` و`/orders` بشكل دوري، ونعمل dedupe بالـ`id`.

### 3.3 Abandoned Carts API (بيانات السلة المتروكة)
| البند | التفاصيل | التصنيف |
|---|---|---|
| Endpoint | `GET /carts/abandoned` على `https://api.salla.dev/admin/v2`، والتفاصيل على `GET /carts/abandoned/{id}` | [مؤكدة] https://docs.salla.dev/5394138e0 (2026-10-21) |
| Scope | `carts.read` | [مؤكدة] نفس المصدر |
| Params | `page`، و`offset` (بالدقايق)، و`per_page` (أقصاه 60)، و`keyword` (customer_id أو رقم الموبايل). الأخير اتضاف في أبريل 2025 | [مؤكدة] نفس المصدر + https://docs.salla.dev/421127m0 |
| الـFields | `id`، `total`، `subtotal`، `total_discount`، **`checkout_url`**، `age_in_minutes`، `created_at`، **`customer` {id, name, mobile, email, avatar, country, city}**، `coupon`، و`items` {product_id, quantity, amounts...} | [مؤكدة] نفس المصدر |
| رقم الموبايل في الـwebhook | `abandoned.cart` فيه `customer.mobile` و`customer.email`. أما `status.changed` و`purchased` فمفيهمش بيانات عميل | [مؤكدة] https://docs.salla.dev/doc-433812 (2026-10-21) |
| **Consent / opt-in** | **مفيش** أي field للموافقة التسويقية، لا في الـAPI ولا في الـwebhook | [مؤكدة] (مش موجود في الـschema المعروض). ممكن يكون في fields مبتظهرش |
| إشارة لإعدادات الإشعارات | من 2025-08-03 فيه param اسمه `fields` على customer endpoints والـwebhooks بيرجّع attributes زيادة، منها `is_notifications_enabled`. **معناه بالظبط (موافقة تسويق ولا إشعارات طلبات؟) غير مؤكد** | [مؤكدة] https://docs.salla.dev/421127m0 (2026-10-21)، والمعنى غير مؤكد |
| سلات مش بتظهر | سلات "Buy now" مش بتظهر كسلات متروكة. والسلة ممكن تاخد لحد ساعة عشان تظهر. وفي جملة عن Mahally صياغتها غامضة: "Abandoned carts appear only from Mahally (for enabled stores)" | [مؤكدة] https://help.salla.sa/en/article/managing-the-abandoned-carts-page/dm3tfjxk5thcslttb2uxepvx (2026-10-21) |

**[افتراض]** عشان مفيش consent في البيانات، CODEX **مينفعش يفترض** إن العميل موافق على رسائل WhatsApp تسويقية. ومش هنعرف إذا كانت رسالة "تذكير السلة" هتتصنف عند Meta كـMarketing ولا Utility غير لما نجرب. ده محتاج مراجعة قانونية (PDPL) ومراجعة لسياسة Meta مع بعض.

### 3.4 هل نقدر ننشئ Cart أو Checkout link أو Payment link؟
| القدرة | الحالة | التصنيف |
|---|---|---|
| إنشاء cart من الـMerchant API | ملقيناش endpoint ليه | **غير مؤكدة** |
| رابط استكمال سلة متروكة | موجود: `checkout_url` جوه كل abandoned cart | [مؤكدة] https://docs.salla.dev/5394138e0 (2026-10-21) |
| إنشاء طلب غير مدفوع | `POST /orders` بـ`payment.status = pending_payment` و`accepted_methods` (credit_card / mada / bank / cod) | [مؤكدة] https://docs.salla.dev/5394145e0 (2026-10-21) |
| رابط دفع للطلب | `payment_actions.remaining_action.checkout_url` ("Checkout URL used to pay the remaining order amount")، بس قيمته فاضية في المثال. وفي field اسمه `draft` في الـresponse، بس مفيش طريقة documented إنك تنشئ draft | جزئيًا [مؤكدة]. **غير مؤكد** إنه بيشتغل كـpayment link للعميل |
| Create Drafted Order | موجود في الـnavigation بتاع الـdocs، بس مقدرناش نقرا صفحته | **غير مؤكدة** — https://docs.salla.dev/421127m0 |
| الـNational Address | بقى إجباري في `ship_to` لـCreate Order من 2026-01-05 | [مؤكدة] https://docs.salla.dev/421127m0 (2026-10-21) |
| Special Offers / Coupons | فيه endpoints لإنشاء special offer، وwebhooks للكوبونات | [مؤكدة] https://docs.salla.dev/5394217e0 (2026-10-21) |

**[افتراض]** الـAI Sales Agent يقدر يبعت `checkout_url` بتاع السلة المتروكة بسهولة. لكن لو عايزينه "يقفل البيعة" من محادثة جديدة مفيهاش سلة، فده محتاج `POST /orders` بحالة pending_payment، والعميل لازم يكون عنده عنوان وطني كامل. ده احتكاك كبير. **[اقتراح]** نختبر الـflow ده على demo store قبل ما نوعد بيه في الـMVP.

### 3.5 الـChangelog (مؤشر على سرعة التغيير)
- من فبراير 2025 لسبتمبر 2026 فيه أكتر من 25 إصدار (v2.10.40 لحد v2.10.70)، وفيهم deprecations ليها مُهَل قصيرة، زي "خلال أسبوعين" لـ`groups` في 2026-02-04، وتغيير `checkout_url` إلى `urls.checkout` في مايو 2025 بمهلة حوالي 17 يوم. [مؤكدة] https://docs.salla.dev/421127m0 (2026-10-21)
- **[افتراض]** المُهَل القصيرة دي معناها إن المؤسس (اللي بيبرمج بنفسه) لازم يتابع الـchangelog أسبوعيًا، ويكون عندنا contract tests على الـpayloads.

---

## 4. متجر التطبيقات: النشر والمراجعة والتسعير والعمولة

### 4.1 النشر والمراجعة
| البند | التفاصيل | التصنيف |
|---|---|---|
| خطوات النشر | 6 أقسام: Basic Information، App Configurations، App Features، Pricing، Contact Information، Service Trial | [مؤكدة] https://docs.salla.dev/421410m0 (2026-10-21) |
| مراجعة أشد لتطبيقاتنا | الـdocs بتقول إن التطبيقات اللي "touch customer communication channels" أو "pass merchant/customer data to third-party AI providers" بتاخد data-handling review أشد. **وCODEX الاتنين** | [مؤكدة] https://docs.salla.dev/playbook/foundations/intro-salla-partners (2026-10-21) |
| التصنيف | التطبيق لازم يكون في category واحدة، و"Others" مش طريقة تهرب بيها من المراجعة. ومن الـcategories: AI-Driven Solutions وChat وCommunication Apps | [مؤكدة] نفس المصدر |
| حق الرفض | سلة ليها حق تقبل أو ترفض أي تطبيق، ومن غير أي مدة محددة | [مؤكدة] https://partners.salla.com/legal/apps-policy (2026-10-21) |
| مدة المراجعة | **مفيش SLA رسمي.** فيه partner اشتكى إن تطبيقه فضل "pending" حوالي 15 يوم من غير أي تحديث (يونيو 2026، ومفيش رد رسمي) | **غير مؤكدة** رسميًا. الشكوى [مؤكدة] https://ideas.salla.partners/ideas/SC-I-2328 (2026-10-21) |
| صفحة "Review Process" | فيها 4 مراحل (Technical، UI/UX، Metadata، Pre-Launch)، بس روابطها رايحة لـ`twilight-engine`، يعني غالبًا دي مراجعة **الثيمات** مش التطبيقات | [مؤكدة] https://docs.salla.dev/845943f0 (2026-10-21)، والربط بالتطبيقات [افتراض] |

**[اقتراح]** نحسب في الـplan من 3 لـ6 أسابيع للمراجعة وإعادة التقديم. ونجهّز privacy policy ونوضّح فيها مين الـAI providers اللي البيانات بتروحلهم (data processing disclosure) قبل ما نقدّم.

### 4.2 التسعير والـBilling والعمولة
| البند | التفاصيل | التصنيف |
|---|---|---|
| نماذج التسعير | Free، One-Time Charge، Recurring. وفيه "Custom Plans" وAdd-ons | [مؤكدة] https://apps.salla.sa/en/faq + https://docs.salla.dev/421410m0 (2026-10-21)؛ والـAdd-ons من عنوان https://salla.dev/blog/apps-add-ons-pricing/ (المحتوى ماتقراش) |
| Usage-based billing (لكل رسالة أو محادثة) | ملقيناش إنه مدعوم بشكل رسمي | **غير مؤكدة** |
| الدفع من خلال سلة | "في حال كان التطبيق مدفوعًا، فإن عملية الدفع تتم عبر منصة سلة وتخضع للعمولات والنسب المعتمدة" | [مؤكدة] https://partners.salla.com/legal/apps-policy (2026-10-21) |
| حق الاقتطاع | "تحتفظ منصة سلة بحقها في اقتطاع نسبة من قيمة الاشتراك مقابل خدمات الاستضافة والتكامل"، **من غير ما تقول النسبة** | [مؤكدة] نفس المصدر |
| **النسبة المحسوبة** | حاسبة الأرباح في صفحة الشركاء بتقول: 100 اشتراك × 59 ريال = **4,130 ريال**، بدل 5,900. يعني المطور بياخد **70%**، والعمولة حوالي **30%** | الأرقام [مؤكدة] https://partners.salla.com/en/apps (2026-10-21). والـ30% **[افتراض]** محسوب ومش معلن (ممكن يكون فيه VAT أو رسوم دفع داخلة في الحسبة) |
| صرف المستحقات | "عادة 15 يومًا" بعد فترة ضمان الجودة والاسترداد | [مؤكدة] https://partners.salla.com/legal/apps-policy (2026-10-21) |
| الاسترداد للتاجر | فيه نافذة 7 أيام بعد أول تنزيل، وبعد فترة التجربة إلغاء التنزيل مش بيرجّع الفلوس | [مؤكدة] https://apps.salla.sa/en/faq (2026-10-21) |
| Billing برا سلة | هل ينفع لتطبيق public يحاسب التاجر مباشرة (Stripe/Moyasar) على الاستهلاك؟ | **غير مؤكدة**. الصياغة بتلمّح إن الدفع "يتم عبر منصة سلة" |

**[افتراض]** لو العمولة فعلًا 30%، فدي بتضرب الـgross margin بتاعنا جامد، خصوصًا إن تكلفة الـLLM ورسوم Meta متغيرة. **[اقتراح]** نسعّر: اشتراك أساسي من خلال سلة + رسوم المحادثات بتاعة Meta يدفعها التاجر لـMeta مباشرة من حسابه (الموديل ده بتستخدمه تطبيقات WhatsApp موجودة على سلة فعلًا: "additional messaging fees ... billed directly by Meta/WhatsApp"، **[مؤكدة]** من listing تطبيق BusinessChat https://apps.salla.sa/en/app/338190499 عن طريق نتيجة بحث، 2026-10-21). ولازم نسأل سلة رسميًا إذا كان مسموح بـBilling منفصل للاستهلاك.

### 4.3 شروط السياسة اللي بتمثل مخاطرة
| الشرط | النص أو المعنى | التصنيف |
|---|---|---|
| الإيقاف | "تحتفظ منصة سلة بحقها في إيقاف أي تطبيق مؤقتًا أو دائمًا" لأي مخالفة تقنية أو قانونية | [مؤكدة] https://partners.salla.com/legal/apps-policy (2026-10-21) |
| تعديل الاتفاقية | سلة تقدر تعدّل الاتفاقية في أي وقت، وتبلّغ بالإيميل أو في النظام | [مؤكدة] نفس المصدر |
| انسحاب المطور | لازم يبلّغ التجار المشتركين قبلها بـ15 يوم | [مؤكدة] نفس المصدر |
| البيانات | ممنوع تجمع بيانات حساسة من غير تصريح صريح، وممنوع تشارك أو تبيع البيانات، ولازم تلتزم بـPDPL وبسياسة الخصوصية بتاعة سلة | [مؤكدة] نفس المصدر |
| منع المنافسة مع سلة | مش مذكور | **غير مؤكدة** |
| تاريخ السياسة | الصفحة منشورة 2026-10-20، يعني **قبل اطلاعنا بيوم واحد**، فالشروط بتتغير | [مؤكدة] نفس المصدر |

---

## 5. مميزات سلة الـnative اللي بتنافسنا

| الميزة | التفاصيل | التصنيف |
|---|---|---|
| **تذكير السلات المتروكة** | تذكير بخصم أو من غير خصم. بتحدد مدة الترك (بالساعات)، وحد أدنى لقيمة السلة، ونوع الخصم وقيمته وصلاحيته، والقناة، ونص الرسالة. **لحد 5 تذكيرات لكل market** | [مؤكدة] https://help.salla.sa/en/article/abandoned-cart-reminders-with-a-discount/vzit7tzwz9e5pcv37nnenyqv (آخر تحديث 2026-09-09، اطلعنا 2026-10-21) |
| القنوات | "SMS"، و"Email"، و"Mobile push notifications (for App Maker subscribers)"، و"**WhatsApp notifications (for WhatsApp by Salla subscribers)**" | [مؤكدة] نفس المصدر |
| WhatsApp by Salla | منتج مدفوع من سلة. **سعره وقدراته (رسمي ولا لأ، فيه AI ولا لأ) غير مؤكدة** | الوجود [مؤكدة] (نفس المصدر)، والتفاصيل **غير مؤكدة** |
| تذكير يدوي بالجملة | تختار سلات وتبعتلهم تذكير بخصم، وتتابع الحالة (Sent / Failed / Scheduled) | [مؤكدة] https://help.salla.sa/en/article/managing-the-abandoned-carts-page/dm3tfjxk5thcslttb2uxepvx (2026-10-21) |
| حملات SMS وPush | فيه مقالات عن الحملات التسويقية عن طريق SMS وإشعارات التطبيق | [مؤكدة] (عناوين مقالات) help.salla.sa FAQ السلات المتروكة (2026-10-21) |
| Salla Ads | منصة إعلانات جوه سلة، جت من الاستحواذ على Sweply في 2025 | [مؤكدة] https://entarabi.com/en/2026/09/salla-acquires-paylink-to-expand-payment-solutions-for-merchants/ (2026-10-21) |
| Payments | استحواذ على Paylink (مرخصة من ساما) في سبتمبر 2026 | [مؤكدة] نفس المصدر |
| أدوات "AI" معلنة | AI-powered fraud detection، وPrompt Library، وStore Analyst (SEO/GEO checker) | [مؤكدة] https://salla.com/en-ae/ai-info + https://salla.com/ (2026-10-21) |
| AI Sales Agent أو Assistant native للتجار | ملقيناش أي إعلان رسمي | **غير مؤكدة** (ده مش معناه إنه مش موجود أو مش جاي) |
| مسرّع "سبّاق" (Sabbaq) | مسرّع لمؤسسي منتجات AI في التجارة، اتعلن في LEAP 2026، وآخر ميعاد للتقديم 2026-08-13 | [مؤكدة] (من نتيجة بحث للصفحة الرسمية، الصفحة نفسها ماتقريتش) https://salla.com/en-ae/sabbaq-2 (2026-10-21) |
| خطط سلة | Basic مجاني، وPlus بـ990 ريال في السنة، وPro بـ2,990 ريال في السنة، وSpecial بسعر مخصص | [مؤكدة] https://salla.com/plans/ (2026-10-21) |

**[افتراض] قراءة نقدية:**
1. **"استرجاع السلات المتروكة" لوحده مش هيكفي إننا نبيع.** سلة بتديه built-in بـ4 قنوات وخصومات و5 تذكيرات، وفيه عشرات تطبيقات WhatsApp على متجر سلة بأسعار من 20 لـ49 ريال في الشهر (WhatsApp Line من 20، وSocial Lab من 29، وWhatsApp Direct من 35، وBusinessChat بـ49). **[مؤكدة]** أسعار التطبيقات من listings متجر سلة عن طريق نتائج بحث (https://apps.salla.sa/en/app/1662840947 · https://apps.salla.sa/en/app/412425472 · https://apps.salla.sa/en/app/602421879 · https://apps.salla.sa/en/app/338190499)، 2026-10-21.
2. اللي ممكن يفرّق: محادثة AI بتجاوب على اعتراضات العميل، وقياس incrementality (control group)، وقرار الخصم المبني على الربح. وده متوافق مع الفرضية في `competitive-feature-matrix.md`.
3. **سلة بتعمل vertical integration بشكل واضح** (Ads في 2025، وPayments في 2026، ومسرّع AI، وWhatsApp by Salla). احتمال إنها تبني أو تشتري AI agent للمحادثة **[افتراض] متوسط لعالي** على مدى 12 لـ24 شهر. ومسرّع "سبّاق" ممكن يبقى طريق تشتري منه.

---

## 6. عدد التجار على سلة (الادعاءات المعلنة)

| المصدر | الرقم | التاريخ | التصنيف |
|---|---|---|---|
| salla.com (الصفحة الرئيسية) | "+68,000" متجر نشط في السعودية والإمارات. وفيه 550+ تطبيق في المتجر | الحالي | [مؤكدة] https://salla.com/ (2026-10-21) |
| salla.com/en-ae/ai-info | أكتر من 68,000 تاجر نشط (2025)، وGMV أكتر من 13 أو 13.3 مليار دولار (الصفحة نفسها متناقضة)، وأكتر من 900 integration | آخر تحديث نوفمبر 2025 | [مؤكدة] https://salla.com/en-ae/ai-info (2026-10-21) |
| partners.salla.com/en/apps | "over 60,000 Merchants" | الحالي | [مؤكدة] https://partners.salla.com/en/apps (2026-10-21) |
| خبر جولة pre-IPO (2024) | أكتر من 80,000 تاجر نشط، و7 مليار دولار GMV من 2020 | مارس 2024 | [مؤكدة] (من نتيجة بحث، والمقال ماتقراش) https://entarabi.com/en/2024/03/saudi-ecommerce-enablement-platform-salla-raises-130-million-in-pre-ipo-round/ (2026-10-21) |
| StoreLeads (خارجي) | 26,138 متجر live، منهم 8,563 في السعودية (32.8%) | 2026-10-16 | [مؤكدة] https://storeleads.app/reports/salla (2026-10-21)، **موثوقيته ضعيفة** |
| Shoprank (خارجي) | 3,825 متجر سلة بشحن فعلي في السعودية (9.22% من 41,471) | يونيو 2026 | [مؤكدة] https://shoprank.com/countries/saudi-arabia (2026-10-21)، **موثوقيته ضعيفة** |

**[افتراض]** الرقم اللي نشتغل بيه في التخطيط هو "عشرات الآلاف من المتاجر النشطة"، وفيها **ذيل طويل كبير** من متاجر صغيرة أو مش نشطة فعلًا. الـSAM بتاعنا (متاجر عطور وBeauty وFashion بمبيعات كفاية وحجم سلات متروكة يستاهل) **أصغر بكتير**، ولازم نقدّره من المقابلات مش من الرقم المعلن.

---

## 7. زد (Zid) قصاد سلة

| البعد | سلة | زد | التصنيف |
|---|---|---|---|
| عدد المتاجر المعلن | 68,000+ | "12,000+ متجر فعال" (صفحة من نحن)، و"13,500+ active stores" (صفحة المطورين) | [مؤكدة] https://salla.com/ · https://zid.sa/ar/about-zid/ · https://zid.sa/en/partners/developers/ (2026-10-21) |
| StoreLeads (خارجي) | 26,138 live (8,563 في السعودية) | 14,439 live (12,990 في السعودية) | [مؤكدة] https://storeleads.app/reports/salla · https://storeleads.app/reports/zid (2026-10-21)، **موثوقيته ضعيفة** |
| Switching في آخر 90 يوم (StoreLeads) | 63 تاجر راحوا من سلة لزد، و27 جم من زد لسلة | صافي +36 لزد | [مؤكدة] https://storeleads.app/reports/zid (2026-10-21)، الأرقام صغيرة ومش ممثلة |
| عدد التطبيقات | 550+ (أو 900+ integration) | "أكثر من 400 تطبيق" | [مؤكدة] https://salla.com/ · https://zid.sa/ar/about-zid/ (2026-10-21) |
| Rate limit | من 120 لـ720 في الدقيقة لكل متجر حسب الخطة | **60 في الدقيقة لكل تطبيق لكل متجر** | [مؤكدة] https://docs.salla.dev/doc-421125 · https://docs.zid.sa/rate-limiting-644369m0 (2026-10-21) |
| Webhooks للسلات المتروكة | `abandoned.cart`، `.status.changed`، `.purchased` | `abandoned_cart.created`، `abandoned_cart.completed` | [مؤكدة] https://docs.salla.dev/doc-433812 · https://docs.zid.sa/webhooks (2026-10-21) |
| باقي الـwebhooks | أكتر من 60 event | حوالي 16 event (order.create، order.status.update، order.payment_status.update، product.*، customer.*، category.*) | [مؤكدة] نفس المصادر |
| Webhook health | 3 retries | فيه tracking لحالة الـwebhook (healthy / degraded / broken) وAPIs لاسترجاع الـwebhooks المعطلة | [مؤكدة] (من نتيجة بحث لـdocs.zid.sa) https://docs.zid.sa/webhooks (2026-10-21) |
| تعريف السلة المتروكة | — | 10 دقايق من غير نشاط | [مؤكدة] (من نتيجة بحث) https://docs.zid.sa/list-abandoned-carts (2026-10-21) |
| تذكير native بالـWhatsApp | عن طريق WhatsApp by Salla (اشتراك منفصل) | **built-in**: SMS وEmail وWhatsApp بقوالب بتراجعها Meta، ورصيد بالنقاط، وخصم أو شحن مجاني، ومعاه وضع تلقائي ووضع يدوي | [مؤكدة] https://help.salla.sa/en/article/abandoned-cart-reminders-with-a-discount/vzit7tzwz9e5pcv37nnenyqv · https://help.zid.sa/auto-cart-reminders/ (2026-10-21) |
| عمولة التطبيقات | مش معلنة (محسوبة حوالي 30%) | مش معلنة. "حصة من الإيرادات" بس لما تبدأ تبيع، والتفاصيل في اتفاقية الشركاء. والانضمام مجاني | [افتراض] / [مؤكدة] https://zid.sa/en/partners/developers/ (2026-10-21) |
| أدوات المطورين | Docs وDemo store وPartners portal | Partner MCP (Claude وCursor وVS Code)، وAgent Skill مفتوح المصدر، ومراجعة تطبيقات "AI-assisted"، وSLA للدعم من 4 ساعات عمل للحالات الحرجة | [مؤكدة] https://zid.sa/en/partners/developers/ · https://docs.zid.sa/zid-agent-skill (2026-10-21) |

**[افتراض] الخلاصة:**
- **سلة أكبر حسب كلام الشركتين نفسهم** (68K قصاد 12K إلى 13.5K)، والـAPI بتاعها أغنى (events أكتر، وrate limit أعلى، والـabandoned cart webhook فيه الموبايل).
- **لكن المصادر الخارجية بتقول إن الفرق أصغر بكتير**، ويمكن زد أكبر في عدد المتاجر **النشطة اللي على دومين خاص** في السعودية. وده محتاج تحقق.
- زد عنده تذكير WhatsApp native **أقوى** من سلة، فالضغط التنافسي على "استرجاع السلات" هناك أعلى.
- زد مستثمر بوضوح في تجربة المطور (MCP، وwebhook health، وSLA). يعني تكلفة التكامل التاني غالبًا هتبقى معقولة.

---

## 8. Shopify وWooCommerce في السعودية

| المصدر | WooCommerce | Shopify | Zid | Salla | YouCan | التصنيف |
|---|---|---|---|---|---|---|
| Shoprank (متاجر بشحن فعلي، الأساس 41,471، يونيو 2026) | 14.56% | 6.81% | 14.55% | 9.22% | 37.26% | [مؤكدة] https://shoprank.com/countries/saudi-arabia (2026-10-21) |
| Shoprank (كل الإشارات، 54,723 domain) | 29.2% إلى 30.5% | 5.8% | 12.1% | 9.3% | — | [مؤكدة] (من نتيجة بحث) نفس المصدر، **وماينفعش يتجمع مع الجدول اللي فوقه** |
| StoreLeads (متاجر عندها Tabby في السعودية) | — | 9.4% | 71.3% | — | — | [مؤكدة] (من نتيجة بحث) https://storeleads.app/reports/technology/Tabby/country/SA (2026-10-21)، ودي عينة متحيزة |

**[افتراض] تحفظات منهجية:** الأدوات دي بتكتشف المنصة من الدومين والـsignals العامة. متاجر سلة اللي على subdomain (`salla.sa/...`) أو من غير دومين خاص غالبًا مش بتتعد كويس. ورقم YouCan (37%) غريب على السوق السعودي وممكن يكون فيه dropshipping أو تصنيف غلط. **مفيش مصدر موثوق لحصة السوق.** اللي نقدر نقوله: WooCommerce موجود بحجم مش قليل (غالبًا متاجر أكبر أو قديمة)، وShopify أقلية (من 6 لـ9%) لكنها غالبًا براندات D2C أكبر حجمًا.

---

## 9. التناقضات اللي سجلناها

| الموضوع | التناقض | تعاملنا معاه إزاي |
|---|---|---|
| عدد تجار سلة | 80,000+ (2024) ← 68,000+ (2025 والصفحة الحالية) ← 60,000+ (صفحة الشركاء) ← 26,138 live (StoreLeads) | بنستخدم "68K معلن" كسقف، ومحتاجين تقدير مستقل |
| نسبة متاجر سلة في السعودية (StoreLeads) | الصفحة اللي قريناها بتقول 32.8% (8,563)، ونتيجة بحث لنفس الصفحة بتقول 83.5% | الرقم مش موثوق |
| GMV سلة | 13 مليار دولار و13.3 مليار دولار في نفس الصفحة | فرق بسيط، مش مهم |
| شركاء الشحن في سلة | 200+ و1,000+ في نفس الصفحة | مش مهم لينا |
| تطبيقات سلة | 550+ تطبيق (الرئيسية) و900+ integration (ai-info) | غالبًا تعريفات مختلفة |
| أرقام زد | 12,000+ و13,500+ (صفحتين رسميتين)، و14,439 (StoreLeads) | الرقم الحقيقي حوالي 12K إلى 14K |
| Rate limit headers في سلة | الأمثلة بتقول 60 و120 و180، والجدول بيقول 120 و360 و720 | لازم نتحقق من live responses |
| `expires` و`expires_in` في OAuth سلة | صيغ مختلفة في الـdocs | نتعامل مع الاتنين |
| Scope بتاع abandoned carts | `carts.read` و`offline_access` في الـsecurity block | نطلب الاتنين |
| حاسبة الأرباح | 100 × 59 = 5,900، والمعروض 4,130 | ده اللي خلانا نستنتج إن العمولة حوالي 30% |

---

## انعكاسات على CODEX

1. **[اقتراح] الترتيب: سلة ← زد ← Shopify ← WooCommerce.** سلة الأول عشان أكبر قاعدة معلنة، وwebhook السلات المتروكة فيه الموبايل والـcheckout_url، والـBilling جاهز، وكل المنافسين الأربعة شغالين عليها (يعني فيه سوق). زد في Phase 2، **مش أكتر من 3 لـ6 شهور بعد سلة**، عشان نقلل الاعتماد على منصة واحدة. Shopify وWooCommerce لاحقًا، لو المقابلات أكدت إن براندات D2C الأكبر عليهم.
2. **[اقتراح] Commerce Adapter layer من أول يوم** (interface داخلي جوه الـModular Monolith، **مش microservice**). كل الـdomain logic (السلة، والعميل، والطلب، والـconsent) يتبني على نموذج بيانات بتاعنا، ومنتعاملش مع الـpayloads بتاعة سلة مباشرة. ده بيخلي زد تكلفته قليلة، وبيحمينا من الـdeprecations.
3. **[اقتراح] Consent ledger بتاع CODEX نفسه.** سلة مش بتدي أي consent للتسويق. لازم نجمع الموافقة ونسجّلها بنفسنا (عن طريق checkbox في الـcheckout لو ينفع، أو أول رسالة utility، أو من التاجر)، ونحدد مع مستشار قانوني إذا كانت رسالة تذكير السلة تتعد marketing تحت PDPL وتحت سياسة Meta. ده متوافق مع Rule 7 وRule 8.
4. **[اقتراح] Token handling متين:** refresh في job واحد لكل tenant وعليه distributed lock، وmonitoring على فشل الـrefresh، وتنبيه للتاجر قبل ما يحتاج يعيد التنزيل.
5. **[اقتراح] Sync بيحترم الـrate limit:** طابور لكل متجر بالحد بتاع خطته (120 في الدقيقة لو Plus)، والـCustomers endpoint ليه حد منفصل (500 كل 10 دقايق)، وreconciliation polling بيكمّل الـwebhooks اللي بتضيع (3 retries بس).
6. **[افتراض] التمايز مينفعش يكون "تذكير سلة بالواتساب".** سلة وزد بيدّوه native، وفيه عشرات التطبيقات بأقل من 50 ريال في الشهر. لازم الـpitch يكون: محادثة بتقفل البيعة + إثبات incrementality + قرار خصم مبني على الربح. ولازم نختبر في المقابلات إذا كان التاجر هيدفع على ده.
7. **[افتراض] أثر العمولة على الـunit economics:** لو العمولة حوالي 30%، فاشتراك بـ299 ريال يطلّع حوالي 209 ريال صافي قبل الـLLM والـinfrastructure. **[اقتراح]** نفصل تكلفة رسائل Meta (يدفعها التاجر مباشرة) عن اشتراك التطبيق، ونطلب من سلة رسميًا: النسبة، وهل usage billing مسموح، وهل الـbilling برا سلة مسموح لعملاء private app أو enterprise.
8. **[اقتراح] Distribution plan B:** Private app لعدد قليل من العملاء الأوائل (design partners) عشان ما نستناش مراجعة المتجر، وده بيوافق Rule 10. لكن لازم نتأكد إن الـprivate app بيشتغل بـCustom Mode، وإيه حدوده (**غير مؤكدة**).
9. **[افتراض] مخاطر الاعتماد على المنصة (ملخص):**

| الخطر | الاحتمال | الأثر | التخفيف [اقتراح] |
|---|---|---|---|
| سلة تطلق AI sales agent أو WhatsApp AI native | متوسط لعالي | عالي | التمايز بالـincrementality والربحية، ودعم أكتر من منصة، ووجود data وmodels عندنا |
| تغيير العمولة أو الشروط من طرف واحد (الاتفاقية "تُعدَّل في أي وقت") | متوسط | متوسط لعالي | تسعير فيه هامش، ونفصل رسوم Meta |
| إيقاف التطبيق أو رفضه في المراجعة (تطبيقات AI والتواصل مراجعتها أشد) | منخفض لمتوسط | عالي جدًا | Privacy وdata disclosure واضحين، وعلاقة مع فريق الشركاء، وprivate app كبديل |
| Deprecations ليها مُهَل قصيرة | عالي | متوسط | Adapter، وcontract tests، ومتابعة الـchangelog أسبوعيًا |
| سلة تقلل البيانات أو تغيّر شكلها (مثلًا تشيل الموبايل من الـwebhook عشان الخصوصية) | منخفض لمتوسط | عالي | Consent وجمع أرقام من القناة بتاعتنا، ومنعتمدش على حقل واحد |
| Rate limit بيبطّأ الـonboarding | عالي | منخفض لمتوسط | Backfill تدريجي، ونبدأ بالـwebhooks |
| سلة بتتحكم في التوزيع (Easy Mode إجباري للمنشور) | مؤكد | متوسط | قنوات بيع مباشرة (agencies وmeetups)، وإعلانات Partner Ads لو موجودة |

## أسئلة مفتوحة
1. **العمولة الفعلية** على التطبيقات المدفوعة في سلة وزد. هل الـ30% صح؟ وهل فيه VAT أو رسوم دفع داخلة في الحسبة؟ (نسأل فريق الشركاء رسميًا)
2. **هل مسموح لتطبيق public يحاسب على الاستهلاك** (رسائل أو محادثات) برا سلة، أو بـusage-based billing من خلالها؟
3. **"WhatsApp by Salla"**: إيه هو بالظبط؟ رسمي على WhatsApp Business Platform؟ سعره؟ فيه AI؟ وهل سلة بتخطط لـAI agent native؟
4. هل الـrate limit بتاع المتجر **مشترك** بين كل التطبيقات المتنزّلة ولا لكل تطبيق لوحده؟ وإيه الحد في خطة Basic المجانية؟
5. معنى `is_notifications_enabled` اللي في الـcustomer fields: هل ينفع يتعامل كمؤشر consent؟ (الأرجح لأ، بس لازم نتحقق)
6. هل `payment_actions.remaining_action.checkout_url` في Create Order بيطلّع **payment link** حقيقي للعميل؟ وهل "Create Drafted Order" بيدّي رابط دفع؟ (نختبر على demo store)
7. مدة مراجعة التطبيق الفعلية، والمعايير الإضافية للتطبيقات اللي بتبعت بيانات لـAI providers (هل فيه شرط استضافة البيانات في السعودية؟)
8. هل الـprivate apps بتعدي على مراجعة؟ وليها حد في عدد التجار؟ وبتشتغل بـCustom Mode؟
9. الحجم الحقيقي لمتاجر سلة **النشطة** في قطاعاتنا (عطور، Beauty، Fashion) بمبيعات تخلّي الـincrementality قابلة للقياس.
10. نسبة السلات المتروكة اللي فيها رقم موبايل صالح (هل الـguest checkout بيجمع الموبايل دايمًا؟)، وأثر استبعاد سلات "Buy now".
11. هل اتفاقية زد للشركاء فيها نسبة أقل أو شروط أحسن تخلّي زد يبقى أولوية أعلى؟
12. هل فيه شرط في اتفاقية سلة يمنع التطبيق إنه يشتغل على منصات منافسة أو يصدّر بيانات التاجر لبرا (portability)؟ (السياسة اللي قريناها مفيهاش كده، بس اتفاقية الشركاء الكاملة ماتقريتش)
