# مراجعة نقدية للـTech Stack المقترح لـCODEX Revenue OS

- **تاريخ البحث والوصول لكل المصادر:** 2026-10-21
- **السؤال:** هل الـStack المقترح في `docs/00-master-prompt.md` (Next.js + Laravel + PostgreSQL + Redis + Python/FastAPI/LangGraph عند الحاجة + Docker/Cloud) مناسب لفريق صغير يقوده مؤسس خبرته Laravel/React/MySQL، بميزانية محدودة، لمنتج Multi-Tenant B2B SaaS في السعودية؟ وإيه اللي يتأجل؟
- **المنهجية:** WebSearch وWebFetch مع تفضيل التوثيق الرسمي (laravel.com، platform.claude.com، postgresql.org، GitHub pgvector، Meta for Developers، docs.salla.dev، stripe.com، pricing pages الرسمية) ومكاتب المحاماة/الاستشارات الكبرى للتنظيم (Clyde & Co، EY). الأرقام المهمة اتراجعت من مصدرين لو أمكن، والتناقضات متسجلة في قسم مستقل.
- **حدود البحث (Limitations):**
  - استنفدنا حد الـWebSearch في الجلسة قبل ما نخلص كل نقاط التحقق؛ بعض النقاط (علاقة Laravel AI SDK بـPrism، تفاصيل اشتراكات Tap الرسمية، قائمة دول Paddle الكاملة) اتحققت جزئيًا بس أو اتسابت "غير مؤكدة".
  - مقارنات الأداء (latency من السعودية لـFrankfurt/Bahrain، إلخ) **مااتقاسش**؛ أي رقم من النوع ده هنا افتراض.
  - معلومات التنظيم (PDPL) مأخوذة من تحليلات مكاتب محاماة، مش من النص الرسمي على منصة استطلاع؛ لازم مراجعة قانونية.
  - الأسعار بالدولار ووقت الوصول بس، وبتتغير.

**الرموز:** **[مؤكدة]** = موثقة بمصدر + رابط + (2026-10-21) · **[افتراض]** = محتاج اختبار · **[اقتراح]** = توصية مننا · **غير مؤكدة** = ملقيناش دليل كافي.

---

## 0. الخلاصة في صفحة واحدة (TL;DR)

| المكوّن | المقترح الأصلي | توصيتنا للـMVP | الحالة |
|---|---|---|---|
| Backend | Laravel (Modular Monolith) | **Laravel 13، Modular Monolith بـdomain folders عادية + Pest arch tests** (بدون nwidart) | [اقتراح] |
| Frontend | Next.js منفصل | **Inertia 3 + React 19 + TypeScript + Tailwind 4 جوه نفس الـrepo** (React starter kit بالـTeams). Next.js يتأجل | [اقتراح] |
| Multi-Tenancy | غير محدد | **Single DB + `tenant_id` + Global Scope + tests إجبارية**؛ Postgres RLS كطبقة دفاع ثانية بعد الـMVP | [اقتراح] |
| Database | PostgreSQL | **PostgreSQL** (مع pgvector). مبرر قوي رغم إن خبرة المؤسس MySQL | [اقتراح] |
| Cache/Queues | Redis | **Redis/Valkey + Horizon** لو VPS/Forge؛ **جدول `scheduled_actions` + Scheduler** للتأخيرات الطويلة (60 دقيقة) في كل الأحوال | [اقتراح] |
| AI | LLM APIs + RAG + Tool Calling، Python/LangGraph عند الحاجة | **Laravel AI SDK v1 ورا Interface خاص بينا**؛ **لا Python ولا LangGraph في الـMVP** | [اقتراح] |
| Vector DB | غير محدد | **pgvector** جوه نفس Postgres؛ Vector DB مخصصة تتأجل | [اقتراح] |
| Billing | غير محدد | **مش Laravel Cashier (Stripe)** لكيان سعودي؛ فواتير يدوية/روابط دفع للـpilots، بعدها Moyasar/Tap tokenization + subscription engine بسيط | [اقتراح] |
| Feature Flags | مطلوب | **Laravel Pennant** بـscope على الـTeam/Tenant | [اقتراح] |
| Audit Logs | مطلوب | جدول `audit_events` append-only خاص بينا لأفعال الـAI والأدمن | [اقتراح] |
| Observability | Monitoring | **Sentry (Free/Team) + Nightwatch (Free/Pro)**؛ OpenTelemetry/Grafana تتأجل | [اقتراح] |
| Hosting | Docker + Cloud | قرار مفتوح مربوط بـPDPL: **Forge + VPS** أو **Laravel Cloud** في EU مع تصميم portable؛ مراجعة بعد اعتماد تعديلات PDPL وإطلاق AWS/Azure في السعودية | [اقتراح] |

**أهم 5 نتايج بتغيّر القرار:**
1. Laravel بقى عنده **AI SDK رسمي v1.0 (23 سبتمبر 2026)** فيه Agents وTool Calling و**Tool Approvals (human-in-the-loop)** وQueueing وEmbeddings وVector search على pgvector — ده بيضعف جدًا مبرر Python/FastAPI في الـMVP. [مؤكدة] https://laravel.com/blog/introducing-laravel-ai-sdk-v1 (2026-10-21)
2. **Stripe مش متاح لكيانات سعودية** (الإمارات بس في المنطقة)، فـLaravel Cashier (Stripe) مش حل مباشر. [مؤكدة] https://stripe.com/global (2026-10-21)
3. **Laravel Cloud مفيهوش Region في الشرق الأوسط**، و**Managed Queues عليه أقصى delay 15 دقيقة** ومش بتدعم Horizon. [مؤكدة] https://laravel.com/cloud/pricing ، https://laravel.com/cloud/docs/queues (2026-10-21)
4. **SDAIA نشرت (أكتوبر 2026) مسودة تعديلات للائحة PDPL فيها شرط localisation: تخزين البيانات الشخصية داخل المملكة**، والاستطلاع مفتوح لحد 5 نوفمبر 2026. ده ممكن يقلب قرار الاستضافة. [مؤكدة] https://www.clydeco.com/en/insights/2026/10/sdaia-publishes-draft-amendments-to-the-pdpl (2026-10-21)
5. Inertia 3 + React starter kit الرسمي فيه **Teams** جاهزة (user ينتمي لأكثر من team، routes متعلقة بالـteam، دعوات) — أساس جاهز للـTenant model بدل بناء auth/API منفصل لـNext.js. [مؤكدة] https://laravel.com/docs/13.x/starter-kits (2026-10-21)

---

## 1. السياق والقيود اللي بتحكم القرار

| القيد | التفاصيل | التصنيف |
|---|---|---|
| خبرة المؤسس | PHP، Laravel، JS/TS، React، Next.js، **MySQL** (مش PostgreSQL)، Docker، VPS، n8n | [مؤكدة] `docs/00-master-prompt.md` §6 |
| تفضيل معماري | Modular Monolith ما لم يوجد مبرر لـMicroservices | [مؤكدة] `docs/00-master-prompt.md` §6 |
| متطلبات SaaS | Multi-Tenancy، Tenant Isolation، RBAC، OAuth، Webhook Security، Queues & Retries، Idempotency، AI Tool Permissions، Human-in-the-loop، Usage Metering، Billing، Feature Flags، Audit Logging، Observability، Encryption، Backups، PDPL، Consent | [مؤكدة] `docs/00-master-prompt.md` §7 |
| الميزانية والفريق | محدودة، والمؤسس بيبرمج بنفسه | [مؤكدة] `CLAUDE.md` Rule 5 |
| [افتراض] | كل لغة/runtime إضافية (Python، Node server لـNext.js) بتزود تكلفة التشغيل والـdeploy والـmonitoring والـsecurity patching بشكل ملموس على فريق 1–3 أشخاص. | [افتراض] |

**ملاحظة تشغيلية من نفس يوم البحث:** يوم 2026-10-21 اتعلن عن **ثغرتين critical في PHP** بتأثر على كل تطبيقات PHP. Laravel Cloud رقّع التطبيقات الشغالة تلقائيًا، بينما مستخدمي Forge كانوا مستنيين الـUbuntu PPAs اللي "لم تستلم الـpatch بعد" وقت النشر، والنصيحة كانت إطفاء السيرفر لو مش هتقدر تحدّث. [مؤكدة] https://laravel.com/blog/php-critical-vulnerability-response-updates (2026-10-21)
→ [اقتراح] ده مثال حي على تكلفة الـself-managed VPS لفريق صغير: لازم يكون فيه runbook للـsecurity patching ومسؤول واضح.

---

## 2. Laravel كـModular Monolith

### 2.1 حالة Laravel الحالية
| المعلومة | التصنيف |
|---|---|
| Laravel 13 هو الإصدار الحالي؛ أقل PHP مطلوب 8.3، ويدعم PHP 8.3–8.5؛ bug fixes لحد Q3 2027 وsecurity fixes لحد Q1 2028 | [مؤكدة] (عبر نتائج البحث لصفحة الـrelease notes) https://laravel.com/docs/13.x/releases (2026-10-21) |
| تاريخ إطلاق Laravel 13: سياسة الدعم بتقول "Q1 2026"، ومدونة طرف ثالث بتقول 17 مارس 2026 | [مؤكدة] للـQ1؛ اليوم بالظبط غير مؤكد رسميًا |

### 2.2 خيارات تنظيم الـModules

| الخيار | الوصف | مزايا | عيوب | التصنيف |
|---|---|---|---|---|
| `nwidart/laravel-modules` | كل module فيه routes/controllers/models/views/migrations/tests؛ v13.0.0 (2026-03-19) يدعم Laravel 13 وPHP ≥8.3 | ناضج، من 2016، commands جاهزة | structure مخصص بعيد عن conventions لارافيل؛ مصمم أصلًا لتفعيل/تعطيل modules ديناميكيًا (حالة CMS) | [مؤكدة] https://packagist.org/packages/nwidart/laravel-modules ، https://laravelmodules.com/docs/13 (2026-10-21) |
| `internachi/modular` | modules في `app-modules/` كـLaravel packages عبر Composer path repositories + auto-discovery؛ README بيقول إن laravel-modules أنسب لـCMS بـmodules بتتفعل وتتقفل | أخف، قريب من conventions | دعم Laravel 13 **غير مؤكد** من صفحة الـrepo | [مؤكدة] https://github.com/InterNACHI/modular (2026-10-21) |
| Domain folders عادية (`app/Modules/Billing`, …) + PSR-4 | بدون package | صفر dependency، أبسط للمؤسس | محتاج انضباط يدوي | [اقتراح] |

**النقطة الحاسمة:** ولا واحد من الخيارات دي **بيمنع** module من إنه يوصل لـmodels بتاعة module تاني؛ كلها تنظيم مش enforcement. [مؤكدة] (وصف package `chrisabner/cordon-modulith` على Packagist، مصدر طرف ثالث) https://packagist.org/packages/chrisabner/cordon-modulith (2026-10-21)
الـenforcement ممكن يتعمل بـ**Pest Architecture Testing** (مثلًا `expect('App\Models')->toOnlyBeUsedIn('App\Repositories')` و`not->toBeUsedIn`). [مؤكدة] https://pestphp.com/docs/arch-testing (عبر نتائج البحث، 2026-10-21)

**[اقتراح]:**
- Domain folders عادية + Pest arch tests في CI تمنع الوصول للـinternals بين الـmodules.
- Modules مبدئية للـMVP (مش أكتر): `Tenancy/Accounts`، `Integrations/Salla`، `Messaging/WhatsApp`، `Inbox`، `AIAgent`، `Recovery` (Abandoned Cart)، `Analytics`، `Billing/Usage`، `Consent`، `Admin/Audit`.
- التواصل بين الـmodules: reads عبر interface/service عام للـmodule، وwrites عبر Laravel Events. ممنوع join مباشر على جداول module تاني إلا في Analytics (read-only).

---

## 3. Multi-Tenancy على Laravel + PostgreSQL

### 3.1 مقارنة النماذج

| النموذج | العزل | التكلفة التشغيلية | Migrations | تقارير عبر الـtenants | مناسب لـCODEX MVP؟ |
|---|---|---|---|---|---|
| **Single DB + `tenant_id` + Global Scopes** | على مستوى التطبيق؛ bug واحد في query = تسريب | الأقل | مرة واحدة | سهل | **نعم** [اقتراح] |
| **Single DB + Postgres RLS** | على مستوى الـDB (defense-in-depth) | متوسطة | لازم إعادة توليد الـpolicies بعد كل migrate | سهل عبر central connection | **لاحقًا** كطبقة ثانية [اقتراح] |
| Schema-per-tenant | أعلى | عالية (migrations × عدد الـschemas) | معقد | أصعب | لا [اقتراح] |
| DB-per-tenant | الأعلى | الأعلى (connections، backups، monitoring لكل DB) | معقد جدًا | صعب | لا، إلا لعميل Enterprise بشرط تعاقدي [اقتراح] |

[افتراض] عملاء CODEX الأوائل متاجر صغيرة/متوسطة على Salla؛ حجم بيانات كل tenant صغير، فمفيش مبرر أداء لعزل فيزيائي.

### 3.2 الـPackages

| Package | المعلومة | التصنيف |
|---|---|---|
| `stancl/tenancy` v4 | أهم ميزة جديدة: **PostgreSQL RLS** لـsingle-database tenancy؛ صفحة v4 بتقول إن الـdocs "WIP" والـRLS "still an experimental feature"؛ **تاريخ إصدار v4 وترخيصه (مجاني/sponsorware) غير مؤكدين** من الصفحة | [مؤكدة] https://v4.tenancyforlaravel.com/version-4/ (2026-10-21) |
| `stancl/tenancy` v4 — قيود الـRLS | الصفحة بتقول إن RLS **"ليس security boundary"** ضد كود شغال في سياق الـtenant؛ الـFK cascades بتتخطى RLS؛ الجداول اللي مفيهاش policy مفتوحة بالكامل؛ لازم تشغيل `tenants:rls` بعد كل migrate؛ الـpolicies المستنتجة من الـFKs ممكن تعمل nested subqueries بطيئة؛ **Forced RLS محتاج `BYPASSRLS` للـcentral user و Laravel Cloud مش بيسمح بيه** | [مؤكدة] https://v4.tenancyforlaravel.com/postgres-rls/ (2026-10-21) |
| `spatie/laravel-multitenancy` v4 | آخر إصدار 4.1.0 (2026-02-22) يدعم Laravel 11–13 حسب aggregator طرف ثالث؛ فيه صفحة "Using a single database" بس محتواها ماوصلناش | [مؤكدة] (طرف ثالث) https://laraplugins.io/plugins/spatie/laravel-multitenancy ؛ تفاصيل الـsingle-DB **غير مؤكدة** |
| Laravel React starter kit — Teams | user ينتمي لأكتر من team وله current team، routes بـ`/{current_team}/...` مع تحقق تلقائي من العضوية، شاشات دعوة وإدارة | [مؤكدة] https://laravel.com/docs/13.x/starter-kits (2026-10-21) |

### 3.3 حقائق PostgreSQL RLS اللي لازم تتعرف قبل الاعتماد عليه
- الـsuperusers والـroles اللي عندها `BYPASSRLS` **دايمًا** بتتخطى RLS؛ وصاحب الجدول (owner) بيتخطاه افتراضيًا إلا مع `FORCE ROW LEVEL SECURITY`. [مؤكدة] https://www.postgresql.org/docs/current/ddl-rowsecurity.html (2026-10-21)
- فحوصات الـreferential integrity (unique/PK/FK) **دايمًا** بتتخطى RLS، وممكن تعمل "covert channel" لتسريب معلومات. [مؤكدة] نفس المصدر
- [افتراض] لو استخدمنا connection pooler (PgBouncer transaction mode أو serverless Postgres)، ضبط متغير الـtenant على مستوى الـsession ممكن يتسرب بين requests؛ لازم `SET LOCAL` جوه transaction. محتاج اختبار.

### 3.4 التوصية [اقتراح]
1. **Tenant = Team** من الـstarter kit (أو model `Workspace` مكافئ)، و`tenant_id` (UUID/ULID) على كل جدول tenant-owned، حتى الجداول الفرعية (تجنب الاعتماد على join paths — نفس نصيحة stancl لتقصير مسارات الـpolicies).
2. Trait واحد `BelongsToTenant` بـGlobal Scope + ملء تلقائي لـ`tenant_id` + منع `withoutGlobalScopes` إلا في `Admin`/`Analytics` عبر arch test.
3. **سياق الـtenant في الـJobs:** كل Job يحمل `tenant_id` صريح ويعيد تهيئة السياق (أشهر مصدر تسريب في single-DB). [افتراض]
4. **Test suite إجباري للعزل:** لكل endpoint/job — tenant A ميشوفش بيانات tenant B.
5. Unique indexes مركبة `(tenant_id, …)`، وكل الـindexes تبدأ بـ`tenant_id`.
6. RLS: نأجله لـPhase 2 كـdefense-in-depth بعد ما نختار الاستضافة (لأن Laravel Cloud بيمنع BYPASSRLS، وstancl بيقول الميزة experimental).

---

## 4. الـFrontend: Next.js منفصل ولا Inertia + React؟

### 4.1 الحقائق
| المعلومة | التصنيف |
|---|---|
| Inertia بيسمح ببناء SPA بـserver-side routing وcontrollers عادية، **بدون client-side routing وبدون API** | [مؤكدة] https://inertiajs.com/docs (2026-10-21) |
| Inertia v3 اتطلق 26 مارس 2026؛ React < 19 مش مدعوم، وNode ≥ 24 | [مؤكدة] https://inertiajs.com/docs ؛ تفاصيل الـbreaking changes من https://laravel-news.com/the-inertia-v3-beta-is-here (2026-10-21) |
| React starter kit الرسمي: **Inertia 3، React 19، TypeScript، Tailwind 4، shadcn/ui**؛ auth بـFortify (2FA، rate limiting)؛ يدعم Inertia SSR؛ فيه Teams؛ وفيه variant بـWorkOS AuthKit | [مؤكدة] https://laravel.com/docs/13.x/starter-kits (2026-10-21) |
| Sanctum SPA auth لـNext.js منفصل: لازم **نفس الـtop-level domain** (subdomains مسموحة)، و`stateful` domains، وCORS `supports_credentials=true`، وطلب `/sanctum/csrf-cookie` قبل الـlogin، وsession cookie domain بـ`.domain.com` | [مؤكدة] https://laravel.com/docs/13.x/sanctum (2026-10-21) |

### 4.2 المقارنة

| المعيار | Inertia + React (نفس الـrepo) | Next.js منفصل + Laravel API |
|---|---|---|
| Auth | Session عادي (Fortify) | Sanctum SPA + CORS + CSRF + إعداد domains، أو tokens |
| API layer | مش محتاج (props من الـcontroller) | لازم تصميم REST/JSON + versioning + types مشتركة |
| Deploy | تطبيق واحد | تطبيقين (Node runtime + PHP)، pipelines اتنين |
| Type safety بين الطرفين | Wayfinder (routes typed) في الـstarter kit | OpenAPI/codegen يدوي |
| SEO/marketing pages | ضعيف نسبيًا (SSR اختياري) | قوي |
| RTL/i18n | محتاج إعداد يدوي في الحالتين | محتاج إعداد يدوي في الحالتين |
| تكلفة على فريق صغير | أقل | أعلى | 

(كل التقييمات في الجدول [افتراض]/[اقتراح] مبنية على الحقائق في 4.1.)

### 4.3 التوصية [اقتراح]
- **Dashboard/App = Inertia 3 + React + TS + Tailwind** جوه Laravel. المؤسس بيعرف React، فمفيش خسارة مهارة.
- **Next.js يتأجل**. لو احتجناه، يبقى للـmarketing site بس (أو موقع static)، ومنفصل تمامًا عن الـauth.
- لو ظهر احتياج لـPublic API (لعملاء/شركاء)، نبني API منفصل بـSanctum tokens وقتها — مش قبل كده.
- قواعد RTL/logical properties في `CLAUDE.md` تنطبق بالكامل.

---

## 5. Queues، الإجراءات المؤجلة (Wait 60 min)، والـIdempotency

### 5.1 حقائق Laravel Queues وHorizon
| المعلومة | التصنيف |
|---|---|
| Horizon **بيتطلب Redis**، و**مش متوافق مع Redis Cluster**؛ فيه auto-balancing، tags، metrics، تنبيهات long-wait | [مؤكدة] https://laravel.com/docs/13.x/horizon (2026-10-21) |
| Horizon: الـ`timeout` لازم يكون أقل من `retry_after` بكام ثانية "وإلا الـjobs ممكن تتنفذ مرتين" | [مؤكدة] نفس المصدر |
| Delayed dispatch مدعوم (`->delay(...)`)، و**Amazon SQS أقصى delay له 15 دقيقة** | [مؤكدة] https://laravel.com/docs/13.x/queues (2026-10-21) |
| `ShouldBeUnique` و`ShouldBeUniqueUntilProcessing` و`WithoutOverlapping` موجودين ومعتمدين على atomic locks (redis/database/…)؛ unique constraints مش بتنطبق جوه batches | [مؤكدة] نفس المصدر |
| `after_commit` / `->afterCommit()`: الـjob ميتبعتش غير بعد commit الـtransaction، ويتلغي لو rollback | [مؤكدة] نفس المصدر |
| `#[DebounceFor(30)]`: آخر dispatch بس هو اللي يتنفذ خلال النافذة | [مؤكدة] نفس المصدر |
| **Laravel Cloud Managed Queues**: standard queues at-least-once وdelay أقصاه **15 دقيقة**؛ FIFO مفيهاش delays؛ **Horizon و`queue:failed/retry/clear` مش مدعومين**؛ SQS بيحتفظ بالرسالة 14 يوم بحد أقصى؛ Flex workers أقصى job runtime 90 ثانية؛ فيه SQS fair queues عشان tenant مزعج ميجوّعش الباقي | [مؤكدة] https://laravel.com/cloud/docs/queues (2026-10-21) |

### 5.2 حقائق الـWebhooks (مصدر الـduplicates)
| المصدر | السلوك | التصنيف |
|---|---|---|
| WhatsApp (Meta) | retry "with decreasing frequency" **لحد 7 أيام**، و"retries can result in duplicate webhook notifications"؛ payload لحد 3 MB | [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/webhooks/overview (2026-10-21) |
| Salla | Signature strategy افتراضي: HMAC-SHA256 للـbody في `X-Salla-Signature`؛ timeout ~30 ثانية؛ **حتى 3 محاولات بفاصل ~5 دقايق** | [مؤكدة] https://docs.salla.dev/doc-421119 (2026-10-21) |
| Salla — Token strategy | التوثيق متناقض في اسم الـheader (Authorization مقابل x-salla-signature) | [مؤكدة] كتناقض، نفس المصدر |

### 5.3 الإجراءات المؤجلة الطويلة (مثل: "استنى 60 دقيقة وابعت رسالة استرجاع السلة")
**المشكلة:** delay جوه الـqueue نفسه هش: على SQS/Laravel Cloud مستحيل فوق 15 دقيقة [مؤكدة، أعلاه]، وعلى Redis الـjob المؤجل مش ظاهر للـbusiness (مينفعش تلغيه بسهولة لو العميل اشترى، ومش بيظهر في audit). [افتراض]

**[اقتراح] Pattern موحد يشتغل على أي استضافة:**
1. جدول `scheduled_actions` (tenant_id، type، subject (cart_id)، `run_at`، status: pending/cancelled/running/done/failed، `idempotency_key` unique، attempts، last_error).
2. Scheduler كل دقيقة (`Schedule::...->everyMinute()->withoutOverlapping()`) يلتقط الـdue rows بـ`SELECT … FOR UPDATE SKIP LOCKED` ويعمل dispatch لـjob قصير.
3. **إعادة فحص الشروط وقت التنفيذ** (السلة لسه abandoned؟ العميل عنده consent؟ مش في quiet hours؟ مفيش رسالة اتبعتت في آخر X ساعة؟ policy الخصم تسمح؟).
4. إلغاء = تحديث status (لما ييجي webhook `order.created` من Salla).
5. كل ده بيدي audit trail وbusiness visibility مجانًا.

### 5.4 Idempotency [اقتراح]
- جدول `webhook_events` بـunique `(provider, external_event_id)`؛ نرجّع 200 بسرعة ونعالج في queue (Meta بتعيد لحد 7 أيام، Salla 3 مرات).
- كل side-effect خارجي (إرسال رسالة WhatsApp، إنشاء كوبون) ليه `idempotency_key` محفوظ قبل التنفيذ، ونتحقق منه قبل أي retry.
- `afterCommit` افتراضيًا على connection الـqueue.
- Unique jobs للعمليات اللي ممنوع تتكرر بالتوازي (sync متجر واحد).

### 5.5 Redis/Horizon ولا Managed Queues؟ [اقتراح]
- **لو VPS/Forge:** Redis + Horizon (dashboard وmetrics مجانًا)، مع supervisor.
- **لو Laravel Cloud:** Managed Queues (بدون Horizon) + pattern الـ`scheduled_actions` أعلاه إجباري.
- في الحالتين، الـpattern في 5.3 هو اللي يحمل منطق الـbusiness، مش delay الـqueue.

---

## 6. دمج الـLLM: داخل PHP ولا خدمة Python جانبية؟

### 6.1 الخيارات داخل PHP

| الخيار | الحالة (2026-10-21) | قدرات مهمة لـCODEX | المخاطر | التصنيف |
|---|---|---|---|---|
| **Laravel AI SDK (`laravel/ai`)** | رسمي؛ beta من فبراير 2026؛ **v1.0 في 23 سبتمبر 2026**؛ آخر إصدار **v1.3.0 (2026-10-19)**؛ ~11.2M install؛ PHP ^8.3 وIlluminate ^12/^13 | Agents، Tool Calling، **Approvable tools (pause لموافقة بشرية، approve/reject/edit args)**، Conversation storage، Streaming، Queueing، Middleware لكل خطوة في الـagent loop (تحكم في التكلفة)، Unified usage reporting، ToolSearch، Structured output، Failover بين providers، Embeddings وCaching ليها | v1.0 جه بـbreaking changes في schema الـconversations؛ عمره كمستقر شهر واحد | [مؤكدة] https://laravel.com/blog/introducing-laravel-ai-sdk-v1 ، https://packagist.org/packages/laravel/ai ، https://laravel.com/docs/13.x/ai-sdk (2026-10-21) |
| Laravel AI SDK — Providers | Text: OpenAI، Anthropic، Gemini، Azure، Bedrock، Groq، xAI، DeepSeek، Mistral، Ollama، OpenRouter، Cohere، OpenAI-compatible؛ Embeddings: OpenAI، Gemini، Azure، Bedrock، Cohere، Mistral، Jina، VoyageAI، Ollama، OpenRouter | — | — | [مؤكدة] https://laravel.com/docs/13.x/ai-sdk (2026-10-21) |
| **Prism (`prism-php/prism`)** | community؛ **v0.102.0 (2026-10-16)**؛ ~7.7M install؛ PHP ^8.2، Laravel 12/13؛ لسه 0.x | multi-provider، tools، structured output | 0.x = API ممكن تتغير | [مؤكدة] https://packagist.org/packages/prism-php/prism (2026-10-21) |
| علاقة Laravel AI SDK بـPrism | `laravel/ai` composer.json **مش بيتطلب** `prism-php/prism` | — | — | [مؤكدة] https://raw.githubusercontent.com/laravel/ai/1.x/composer.json (2026-10-21) |
| **Anthropic PHP SDK الرسمي (`anthropic-ai/sdk`)** | رسمي، **beta**، major version 0، PHP ≥ 8.1؛ streaming، retries تلقائية (مرتين افتراضيًا)، Bedrock/Vertex/Foundry clients | تحكم كامل بأحدث features | beta = API ممكن تتغير | [مؤكدة] https://platform.claude.com/docs/en/cli-sdks-libraries/sdks/php (2026-10-21) |
| OpenAI PHP SDK رسمي | **غير مؤكد وجوده**؛ الأشهر community `openai-php/client` | — | — | غير مؤكدة |

### 6.2 خدمة Python/FastAPI جانبية — امتى يبقى ليها مبرر؟
| مبرر محتمل | هل موجود في الـMVP؟ | التصنيف |
|---|---|---|
| Agent orchestration معقد (graphs، checkpoints، parallel branches) | لا — الـMVP فيه agent مبيعات + recovery flows خطية؛ Laravel AI SDK فيه agents وtool approvals وqueueing | [افتراض] |
| مكتبات ML/تقييم غير متاحة في PHP (evals، fine-tuning، forecasting) | لا في الـMVP؛ ممكن في "Profit-Aware" لاحقًا | [افتراض] |
| معالجة مستندات عربية معقدة (OCR/PDF) للـRAG | ممكن، بس الـMVP مصدره بيانات Salla structured | [افتراض] |
| خبرة الفريق | المؤسس خبرته PHP/JS مش Python | [مؤكدة] `00-master-prompt.md` |

### 6.3 LangGraph
- LangGraph framework نفسه MIT مجاني؛ الـPlatform المدفوعة جزء من billing الـLangSmith، والـself-hosted بالتواصل مع المبيعات. [مؤكدة] https://www.langchain.com/langgraph-platform-pricing (عبر نتائج البحث، 2026-10-21)
- البديل الـPHP الوحيد اللي لقيناه `cainydev/laragraph` early-stage جدًا (≈2 stars). [مؤكدة] https://laraplugins.io/plugins/cainydev/laragraph (2026-10-21) — **مانرشحوش**.
- **[اقتراح] LangGraph مش ضروري للـMVP.** الـ"graph" بتاعنا = state machine في الـDB (`scheduled_actions` + statuses) + agent بـtools محدودة + approvals. ده أسهل في الـaudit والـHITL من graph runtime خارجي.

### 6.4 التوصية [اقتراح]
1. **Laravel AI SDK v1** كأساس، **بس ورا interface داخلي** (`AiGateway`/`AgentRunner`) عشان لو الـSDK اتغير أو احتجنا ننزل للـSDK الرسمي لـAnthropic/OpenAI أو لـPrism، التغيير يبقى في مكان واحد.
2. **Tool permissions = Approvable tools + policy layer بتاعتنا**: أي tool فيه خصم/كوبون/حملة/إرسال جماعي = Approvable بشكل افتراضي، مع حدود per-tenant (Rule 9).
3. Usage metering من الـunified usage reporting (input/output/cached tokens) → جدول `ai_usage` per tenant لأغراض الـbilling والـmargin.
4. **Python service = Phase لاحقة** بـtrigger واضح: (أ) احتياج مكتبة ML محددة مش موجودة في PHP، أو (ب) workload batch/analytics تقيل، أو (ج) تعيين مهندس Python. لو حصل، تبقى worker داخلي بيقرا من queue/DB، مش microservice بـAPI عام.

### 6.5 Data residency للـLLM (مهم مع PDPL)
- Anthropic first-party API: `inference_geo` قيمه المتاحة **`"global"` و`"us"` بس**، و**Workspace geo (التخزين at rest) `"us"` بس حاليًا**؛ US-only inference بسعر 1.1x. [مؤكدة] https://platform.claude.com/docs/en/manage-claude/data-residency (2026-10-21)
- OpenAI أعلنت inference residency في الإمارات لعملاء Enterprise/Edu والـAPI Platform (حسب تقارير صحفية)، مع قيود على الموديلات والميزات. [مؤكدة كتقارير صحفية] https://www.zawya.com/en/press-release/openai-expands-inference-residency-to-the-united-arab-emirates-428932 (2026-10-21) — **تفاصيل الأهلية والتسعير غير مؤكدة**.
- **[افتراض] حرج:** حتى لو استضفنا التطبيق في الرياض، نصوص المحادثات (بيانات شخصية لعملاء المتاجر) هتتعالج خارج المملكة عند مزود الـLLM. ده لازم يدخل في تقييم PDPL (transfer safeguards) ومع المستشار القانوني.

---

## 7. RAG: pgvector ولا Vector DB مخصصة؟

| المعلومة | التصنيف |
|---|---|
| pgvector آخر إصدار **0.8.7**؛ HNSW (أسرع/recall أعلى، build أبطأ وذاكرة أكتر) وIVFFlat (أخف) | [مؤكدة] https://github.com/pgvector/pgvector (2026-10-21) |
| Indexing لحد **2,000 dimension لـ`vector`** و4,000 لـ`halfvec` | [مؤكدة] نفس المصدر |
| الـfilters في الـapproximate indexes بتتطبق **بعد** scan الـindex فممكن ترجع نتايج أقل؛ الحل iterative index scans؛ partial indexes لقيم قليلة؛ partitioning لقيم كتير | [مؤكدة] نفس المصدر |
| **Multi-tenant:** index مشترك يخلي vectors tenant تأثر على recall tenant تاني؛ للعزل: list partitioning بالـtenant أو جداول منفصلة | [مؤكدة] نفس المصدر |
| Laravel: `whereVectorSimilarTo` بيستخدم **PostgreSQL + pgvector**، و`$table->vector('embedding', dimensions: 1536)`، و`Schema::ensureVectorExtensionExists()`؛ Laravel Cloud Serverless Postgres فيه pgvector | [مؤكدة] https://laravel.com/docs/13.x/search (عبر نتائج البحث، 2026-10-21) |

**[اقتراح]:**
- **pgvector** جوه نفس الـDB: صفر خدمة إضافية، نفس الـbackups، نفس الـtenant scoping.
- كل query فيها `tenant_id` + `hnsw.iterative_scan`، ومحتوى الـRAG في الـMVP صغير (كتالوج منتجات + FAQ + سياسات المتجر) [افتراض] فالـrecall مش مشكلة في البداية.
- اختار embedding model بأبعاد ≤ 2,000 (أو halfvec) عشان الـindex يشتغل.
- **Vector DB مخصصة (Qdrant/Pinecone/…) تتأجل** لحد ما يظهر: ملايين chunks per tenant أو latency مش مقبولة بعد partitioning.
- [افتراض] ده كمان **مبرر قوي لـPostgreSQL بدل MySQL** رغم خبرة المؤسس: Laravel vector search الرسمي مبني على pgvector.

---

## 8. الـBilling والاشتراكات في السعودية

| المعلومة | التصنيف |
|---|---|
| Laravel Cashier (الصفحة الرئيسية) = **Stripe**؛ فيه usage-based billing (meters و`reportMeterEvent`) | [مؤكدة] https://laravel.com/docs/13.x/billing (2026-10-21) |
| **Stripe مش متاح لكيانات في السعودية**؛ الإمارات متاحة | [مؤكدة] https://stripe.com/global (2026-10-21) |
| Paddle (Merchant of Record) بيقول إنه بيخدم بائعين في +200 دولة، والسعودية **مش** في قائمة الدول المحظورة | [مؤكدة جزئيًا] https://developer.paddle.com/concepts/sell/supported-countries-locales ، https://learn.profitwell.com/help/legal/sanctions/which-countries-are-supported-by-paddle (2026-10-21) — **قبول بائع سعودي فعليًا غير مؤكد** |
| Moyasar: tokenization لـmada/Visa/Mastercard/Amex/UnionPay للمدفوعات المتكررة (لازم تفعيل من الحساب)؛ **مالقيناش subscription object** في الـAPI — الجدولة على مسؤوليتنا | [مؤكدة] https://docs.moyasar.com/guides/tokenization/overview (2026-10-21)؛ عدم وجود subscription API **غير مؤكد** |
| Tap Payments: saved-card tokens وrecurring webhooks حسب مصادر طرف ثالث؛ **وثائق الاشتراكات الرسمية مااتراجعتش** | غير مؤكدة |
| ZATCA Phase 2 (Fatoora) — Wave 25: أي مكلف دخله الخاضع للضريبة > **187,500 ريال** في أي سنة 2022–2025، الالتزام من **1 فبراير 2027** | [مؤكدة] https://globaltaxnews.ey.com/news/2026-1705-saudi-arabia-announces-25th-wave-of-phase-2-e-invoicing-integration (2026-10-21) |

**[اقتراح]:**
1. **Pilots (أول 5–20 عميل):** فواتير يدوية + payment links/تحويل بنكي. بناء billing engine قبل إثبات الاستعداد للدفع = مخالفة Rule 10.
2. **بعد الـpilots:** subscription engine صغير خاص بينا (plans، entitlements، usage counters، invoices) + Moyasar **أو** Tap tokenization للتجديد. Laravel Cashier مش هيوفر قيمة هنا.
3. **بديل يحتاج قرار business:** كيان في الإمارات + Stripe/Cashier، أو Paddle كـMoR — كل واحد له أثر ضريبي/قانوني. [افتراض]
4. الفواتير لازم تتوافق مع ZATCA لو CODEX كيان سعودي تعدى الحد — [افتراض] غالبًا عبر مزود e-invoicing جاهز بدل بناء التكامل.
5. **Usage Metering** (رسائل WhatsApp، AI tokens، محادثات) يتسجل من اليوم الأول حتى لو مش بنحاسب عليه، عشان نفهم الـunit economics.

---

## 9. Feature Flags وAudit Logs

### 9.1 Laravel Pennant
- خفيف، drivers: `array` و`database` (الافتراضي)، وscope مش لازم user — ممكن **Team** (`Feature::for($user->team)`)، فيه rich values، in-memory cache لكل request، custom drivers؛ وخارج HTTP (jobs) لازم scope صريح. [مؤكدة] https://laravel.com/docs/13.x/pennant (2026-10-21)
- **[اقتراح]:** Pennant بـscope = Tenant. **وفصل** "feature flags" (rollout تقني) عن "plan entitlements" (إيه اللي الباقة بتسمح بيه) — الـentitlements تبقى جدول في Billing module مش Pennant.

### 9.2 Audit Logs
| الخيار | المعلومة | التصنيف |
|---|---|---|
| `spatie/laravel-activitylog` | v5.0.0 (2026-03-25) حسب aggregator؛ activity عامة في `activity_log` | [مؤكدة] (طرف ثالث) https://dev.co/observability/open-source/laravel-activitylog ، https://spatie.be/docs/laravel-activitylog/v5/api/log-options (2026-10-21) |
| `owen-it/laravel-auditing` | تغييرات الـmodels (مين غيّر إيه وإمتى)؛ v14.x لـLaravel 11–13 (المصادر متضاربة في رقم الإصدار) | [مؤكدة] (طرف ثالث) https://dev.co/observability/open-source/laravel-auditing (2026-10-21) |

**[اقتراح]:** جدول `audit_events` خاص بينا (append-only: tenant_id، actor_type: user/ai_agent/system، actor_id، action، subject، before/after، policy_decision، approval_id، correlation_id، ip، created_at) لأن أهم حاجة نوثقها هي **قرارات وأفعال الـAI** (Rule 9) وده مش model-change audit عادي. ممكن نضيف activitylog لتغييرات الـmodels لو احتجنا، مش بديل.

---

## 10. Observability

| الأداة | المعلومة | التصنيف |
|---|---|---|
| **Sentry** | Developer مجاني (5k errors، 5M spans، 5GB logs، مستخدم واحد)؛ Team $26/شهر سنوي أو $29 شهري (50k errors)؛ Business $80/$89 | [مؤكدة] https://sentry.io/pricing/ (2026-10-21) |
| **Laravel Nightwatch** | Free (300k events، 14 يوم)؛ Pro $20 (7.5M events، 30 يوم)؛ Team $60؛ Business $300؛ spending caps؛ **data centers في US وEU بس**؛ redaction للـPII من الـagent؛ SOC 2 "قيد السعي" | [مؤكدة] https://laravel.com/nightwatch/pricing (2026-10-21) |
| Nightwatch أرقام قديمة (200k free / 5M Pro) في تغطية الإطلاق | [مؤكدة كتناقض] https://laravel-news.com/laravel-nightwatch-released (2026-10-21) — الصفحة الرسمية هي المعتمدة |
| **OpenTelemetry PHP** | Traces وMetrics وLogs كلها **Stable** | [مؤكدة] https://opentelemetry.io/docs/languages/php/ (2026-10-21) |
| Laravel Cloud logs/metrics retention | 1 يوم Starter، 7 Growth، 30 Business | [مؤكدة] https://laravel.com/cloud/pricing (2026-10-21) |

**[اقتراح]:**
- MVP: **Sentry** (errors + alerts) + **Nightwatch Free/Pro** (requests/jobs/queries، مبني للارافيل) + uptime check خارجي + Horizon/Cloud queue dashboard.
- **PII redaction إجباري** قبل الإرسال (محتوى رسائل WhatsApp وأرقام التليفونات) لأن البيانات هتروح US/EU.
- **OpenTelemetry + Grafana تتأجل** لحد ما يبقى فيه أكتر من service أو احتياج vendor-neutral. [اقتراح]
- Correlation ID واحد من الـwebhook → job → AI call → رسالة صادرة، يتسجل في الـaudit والـlogs.

---

## 11. الـDeployment والاستضافة (مع PDPL)

### 11.1 الخيارات المُدارة من Laravel
| المعلومة | التصنيف |
|---|---|
| **Laravel Cloud**: Starter $5/شهر + usage، Growth $20، Business $200؛ Regions: US East (Virginia، Ohio)، Canada، EU (Frankfurt، Ireland، London)، Asia Pacific (Singapore، Sydney، Tokyo) — **مفيش شرق أوسط**؛ Serverless Postgres (Neon، Postgres 17) وMySQL وValkey؛ سيناريو "SaaS MVP" تقديري ≈ **$34.29/شهر** | [مؤكدة] https://laravel.com/cloud/pricing (2026-10-21) |
| **Laravel Forge**: Hobby $12/شهر (سيرفر خارجي واحد)، Growth $19، Business $39؛ بيدير VPS من DigitalOcean/AWS/Hetzner/… أو "Laravel VPS"؛ zero-downtime deploys؛ يدعم Postgres وRedis | [مؤكدة] https://laravel.com/forge/pricing (2026-10-21) |

### 11.2 الاستضافة داخل السعودية
| المزود | الحالة | التصنيف |
|---|---|---|
| Oracle Cloud Jeddah (`me-jeddah-1`) وRiyadh (`me-riyadh-1`) | Live، بيع مباشر | [مؤكدة] (tracker طرف ثالث بتاريخ 31 يوليو 2026) https://vision2030.ai/sectors/technology/saudi-arabia-cloud-regions/ (2026-10-21) |
| Google Cloud Dammam (`me-central2`) | Live من نوفمبر 2023، **بيع عبر CNTXT بس**؛ Cloud SQL for PostgreSQL مدعوم مع قيد (مفيش instances ≥ 34 vCPU) | [مؤكدة] نفس الـtracker + https://cloud.google.com/sql/docs/postgres/region-availability-overview (2026-10-21) |
| Huawei Cloud Riyadh، Alibaba Cloud Riyadh (عبر SCCC/stc)، Tencent Cloud | Live | [مؤكدة] (tracker طرف ثالث) نفس المصدر |
| **AWS Saudi region** | مخطط **ديسمبر 2026**، 3 AZs، استثمار +5.3 مليار دولار | [مؤكدة كإعلان] https://www.spa.gov.sa/en/w2665748 (العنوان بس) + https://tbreak.com/aws-saudi-arabia-cloud-region-december-2026/ (2026-10-21) |
| **Azure Saudi Arabia East** | مخطط Q4 2026 (تقارير بتقول نوفمبر)، 3 AZs | [مؤكدة كتقارير صحفية] https://meatechwatch.com/?p=46224 (2026-10-21) — الموعد الدقيق غير مؤكد من Microsoft |
| AWS Bahrain (`me-south-1`) وUAE (`me-central-1`) | Live | [مؤكدة] https://aws.amazon.com/blogs/aws/now-open-aws-region-in-the-united-arab-emirates-uae/ (2026-10-21) |
| VPS محلي في الرياض (Exa Host، Dimofinf…) | أسعار listings طرف ثالث من ~$90 لـ~$500/شهر | [مؤكدة كـlistings طرف ثالث] https://www.whtop.com/plans/exahost.com/130864 (2026-10-21) — غالية نسبيًا وجودتها غير مؤكدة |

### 11.3 PDPL — العامل الحاسم
| المعلومة | التصنيف |
|---|---|
| تعديل لائحة النقل (سبتمبر 2024): adequacy decisions تنشرها SDAIA؛ بدون adequacy: SCCs أو BCRs أو Certification؛ SCCs منشورة 1 سبتمبر 2024 | [مؤكدة] (تحليلات مكاتب محاماة) https://www.dentons.com/en/insights/alerts/2025/may/15/saudi-arabias-framework-for-cross-border-data-transfers (2026-10-21) |
| حتى سبتمبر 2026، SDAIA **مانشرتش قائمة adequacy** | [مؤكدة] (مصدر ثانوي) https://www.recordinglaw.com/world-laws/world-data-privacy-laws/saudi-arabia-data-privacy-laws/ (2026-10-21) — يحتاج تأكيد رسمي |
| **مسودة تعديلات (نُشرت ~6 أكتوبر 2026):** المادة 23 المقترحة تُلزم الـcontrollers **بتخزين البيانات الشخصية داخل المملكة**، مع استمرار السماح بالنقل للخارج وفق PDPL؛ التسجيل في السجل الوطني يبقى إلزامي لو فيه نقل للخارج؛ **الاستطلاع لحد 5 نوفمبر 2026**؛ النفاذ بعد 60 يوم من النشر في الجريدة الرسمية **لو اتعتمدت** | [مؤكدة] https://www.clydeco.com/en/insights/2026/10/sdaia-publishes-draft-amendments-to-the-pdpl (2026-10-21) |

[افتراض] CODEX غالبًا هيبقى **Processor** لبيانات عملاء المتاجر (المتجر هو الـController)، بس ده محتاج تكييف قانوني؛ وحتى لو processor، المتاجر نفسها ممكن تطلب تخزين داخل المملكة تعاقديًا.

### 11.4 مقارنة مسارات الـDeploy

| المعيار | A: Laravel Cloud (EU) | B: Forge + VPS (EU أو Bahrain/UAE) | C: Docker على VPS بإدارة ذاتية | D: سحابة داخل السعودية (Oracle/GCP-CNTXT/AWS بعد الإطلاق) |
|---|---|---|---|---|
| جهد التشغيل | الأقل (patching تلقائي، شفناه النهارده) | متوسط (patching على المؤسس) | الأعلى | متوسط–عالي |
| التكلفة البدائية | ~$34/شهر سيناريو MVP [مؤكدة كتقدير رسمي] | $12–19 + VPS | VPS بس | غير مؤكدة (محتاج عروض أسعار) |
| Horizon/Redis | لا Horizon على managed queues | نعم | نعم | نعم |
| Delays > 15 دقيقة في الـqueue | لا | نعم | نعم | حسب الـdriver |
| Postgres RLS forced (BYPASSRLS) | غير مسموح | نعم | نعم | غالبًا نعم [افتراض] |
| بيانات داخل المملكة | لا | لا (إلا VPS سعودي) | لو VPS سعودي | **نعم** |
| مخاطرة PDPL لو المسودة اتعتمدت | عالية | عالية | منخفضة لو سعودي | منخفضة |

### 11.5 التوصية [اقتراح]
- **صمم للـportability من اليوم الأول:** Postgres standard (بدون features خاصة بـNeon)، S3-compatible object storage، Docker image للـapp حتى لو مش هنستخدمه في الـprod، config بالـenv، ومفيش اعتماد على خدمات proprietary للـqueue logic (عشان كده `scheduled_actions` في الـDB).
- **المرحلة الحالية (تطوير + pilots بموافقة مكتوبة):** **Forge + VPS واحد** (app + Postgres + Redis + Horizon) في أقرب region معقول، أو Laravel Cloud لو السرعة أهم — مع backups يومية خارج السيرفر.
- **قبل الإطلاق التجاري:** قرار نهائي بعد (1) صدور النسخة النهائية من تعديلات PDPL، (2) مراجعة قانونية لدور CODEX (controller/processor)، (3) إطلاق AWS/Azure في السعودية فعليًا. لو الـlocalisation اتعتمد → نقل لسحابة داخل المملكة (AWS Riyadh بعد الإطلاق أو Oracle Riyadh) مع Forge.
- **ممنوع** Kubernetes أو multi-region في الـMVP (Rule 6).

---

## 12. الـStack الموصى به + اللي يتأجل + الـTrade-offs

### 12.1 الـStack للـMVP [اقتراح]
| الطبقة | الاختيار |
|---|---|
| Language/Framework | PHP 8.4/8.5 (مدعومين في Laravel 13) + Laravel 13 |
| Architecture | Modular Monolith، domain folders، Pest arch tests |
| Frontend | Inertia 3 + React 19 + TS + Tailwind 4 + shadcn/ui (React starter kit مع Teams)، RTL أول |
| Auth/RBAC | Fortify (من الـstarter kit) + Policies/Gates + roles per tenant (جدول بسيط أو `spatie/laravel-permission` بـteams) |
| DB | PostgreSQL 17 + pgvector |
| Tenancy | Single DB + `tenant_id` + Global Scope + tests |
| Queues | Redis + Horizon (أو Cloud managed queues) + `scheduled_actions` |
| AI | Laravel AI SDK v1 ورا `AiGateway` + Approvable tools + `ai_usage` |
| RAG | pgvector، tenant filter + iterative scan |
| Flags | Pennant (scope = tenant) |
| Audit | `audit_events` custom append-only |
| Billing | يدوي للـpilots → Moyasar/Tap tokenization + engine بسيط |
| Observability | Sentry + Nightwatch + uptime |
| Hosting | Forge + VPS (أو Laravel Cloud)، portable، قرار نهائي بعد PDPL |

### 12.2 اللي يتأجل (ومتى يرجع)
| المؤجَّل | Trigger للرجوع | التصنيف |
|---|---|---|
| Next.js | marketing site بـSEO جاد، أو public frontend منفصل | [اقتراح] |
| Python/FastAPI | مكتبة ML محددة أو workload analytics تقيل أو مهندس Python | [اقتراح] |
| LangGraph | agent graphs متفرعة/متوازية مايقدرش الـstate machine يغطيها | [اقتراح] |
| Vector DB مخصصة | ملايين chunks per tenant أو latency غير مقبولة بعد partitioning | [اقتراح] |
| Postgres RLS | بعد اختيار الاستضافة النهائية واستقرار الـschema | [اقتراح] |
| Schema/DB-per-tenant | عقد Enterprise يشترط عزل فيزيائي | [اقتراح] |
| OpenTelemetry/Grafana | أكتر من service أو تغيير vendor | [اقتراح] |
| Laravel Cashier | لو اتعمل كيان في الإمارات واستُخدم Stripe | [اقتراح] |
| Microservices/Kubernetes | فريق > ~8 مهندسين أو bottleneck مثبت بالقياس | [اقتراح] |
| Public API | طلب حقيقي من شركاء/عملاء | [اقتراح] |

### 12.3 الـTrade-offs الصريحة
| القرار | بنكسب | بنخسر/بنخاطر |
|---|---|---|
| Inertia بدل Next.js | تطبيق واحد، auth أبسط، سرعة تطوير | SEO أضعف للـapp (مش مهم لـdashboard)، ربط أقوى بـLaravel، صعوبة أكبر لو جه mobile app (هنحتاج API ساعتها) |
| Single DB + tenant_id | أبسط وأرخص | خطأ query واحد = تسريب؛ بنعوضه بـtests وarch rules ثم RLS |
| PostgreSQL بدل MySQL | pgvector + Laravel vector search الرسمي + RLS | منحنى تعلم للمؤسس (خبرته MySQL) |
| Laravel AI SDK v1 | رسمي، tool approvals، queue، multi-provider، testing | مستقر من شهر واحد، breaking changes حصلت قبل كده؛ نخففها بالـinterface |
| لا Python | runtime واحد، فريق واحد | بعض مكتبات الـAI/ML المتقدمة أبطأ في الوصول |
| Forge+VPS | تكلفة أقل، تحكم، Horizon، أي region | patching ومسؤولية تشغيل على المؤسس (ثغرة PHP النهارده مثال) |
| Laravel Cloud | أقل جهد تشغيل | لا region في المنطقة، queue delay ≤15 دقيقة، لا BYPASSRLS، تكلفة usage-based |

---

## 13. التناقضات المسجلة
| الموضوع | التناقض | الحسم |
|---|---|---|
| تاريخ Laravel 13 | سياسة الدعم "Q1 2026" vs مدونة "17 مارس 2026" | نعتمد Q1 2026 |
| Laravel AI SDK والإصدار | بعض المقالات تقول "مدمج في Laravel 13"، بينما الـpackage منفصل ويدعم Illuminate ^12 و^13 | Packagist هو المرجع: package منفصل |
| آخر إصدار laravel/ai | GitHub releases ظهرت v1.2.0، Packagist v1.3.0 (2026-10-19) | Packagist |
| stancl/tenancy RLS | صفحة "Version 4" تقول experimental، صفحة RLS نفسها مابتقولش | نعامله كـexperimental |
| Nightwatch pricing | تغطية الإطلاق 200k/5M vs الصفحة الرسمية 300k/7.5M | الصفحة الرسمية |
| Sentry Team | $26 (سنوي) vs $29 (شهري) | الاتنين صح حسب دورة الدفع |
| Salla Token strategy | اسم الـheader مختلف بين الشرح والأمثلة | نعتمد Signature strategy |
| owen-it/laravel-auditing | v13.7.4 vs v14.0.6 | غير محسوم (نتحقق من Packagist وقت الاستخدام) |
| Laravel Cloud plans | جدول Managed queues per environment: Business "Unlimited" في صفحة الأسعار vs "50" في docs الـqueues | غير محسوم |
| Moyasar subscriptions | صفحة تسويقية بتتكلم عن تجديد/إيقاف/إلغاء، والـAPI docs فيها tokenization بس | نفترض إن الجدولة علينا |

---

## انعكاسات على CODEX

1. **الـStack الأصلي سليم في جوهره (Laravel + Postgres + Redis + Modular Monolith)، لكن فيه طبقتين زيادة عن حاجة الـMVP: Next.js المنفصل وPython/LangGraph.** شيلهم بيوفر runtime كامل وpipeline وauth layer. [اقتراح]
2. **مبرر Python ضعف كتير في 2026** بسبب Laravel AI SDK v1 (tool approvals = human-in-the-loop اللي Rule 9 بتطلبه). [اقتراح مبني على مؤكدة]
3. **"Abandoned Cart Recovery بعد 60 دقيقة" لازم يتصمم كـstate machine في الـDB، مش delay في الـqueue** — ده بيحل قيود Laravel Cloud، وبيدّي إلغاء وaudit، وبيتعامل مع webhooks مكررة من Meta (لحد 7 أيام) وSalla (3 محاولات). [اقتراح]
4. **Billing:** Laravel Cashier (Stripe) مش متاح مباشرة لكيان سعودي؛ ده بيدعم قرار البدء بفواتير يدوية مع الـpilots (Rule 10) وتأجيل الـbilling engine. [اقتراح مبني على مؤكدة]
5. **PDPL ممكن يقلب قرار الاستضافة خلال أسابيع** (استطلاع لحد 5 نوفمبر 2026). أي قرار hosting دلوقتي لازم يبقى قابل للعكس. وحتى مع استضافة سعودية، الـLLM APIs بتعالج البيانات بره المملكة (Anthropic: us/global بس). [اقتراح مبني على مؤكدة]
6. **PostgreSQL مبرر رغم إن خبرة المؤسس MySQL** (pgvector، Laravel vector search الرسمي، RLS)؛ لازم وقت تعلم محسوب في الـtimeline. [اقتراح]
7. **Multi-tenancy:** الـstarter kit Teams + `tenant_id` + tests يغطوا Rule 7 من البداية بأقل تعقيد؛ RLS بعدين. [اقتراح]
8. **ده بحث، مش ADR.** القرارات دي محتاجة ADRs منفصلة في `docs/adr/` (Frontend، Tenancy، AI integration، Hosting، Billing) بعد موافقة المؤسس. [اقتراح]
9. [افتراض] المنافسين (Thikaa مثلًا بيظهر عليه نمط PHP حسب `docs/research/thikaa.md`) مش متقدمين تقنيًا بشكل يفرض stack أعقد؛ الميزة هتيجي من المنتج مش من الـinfrastructure.

---

## أسئلة مفتوحة

1. هل CODEX هيبقى **Controller ولا Processor** لبيانات عملاء المتاجر تحت PDPL؟ وهل مسودة الـlocalisation (المادة 23) هتتعتمد بنصها؟ (محتاج مستشار قانوني سعودي + متابعة منصة استطلاع بعد 5 نوفمبر 2026)
2. هل نقل نصوص المحادثات لمزود LLM خارج المملكة (US/global) مقبول تحت SCCs، وهل محتاجين مزود LLM بـinference داخل المملكة/الخليج (مثلًا عبر HUMAIN أو OpenAI UAE residency)؟ — غير مؤكدة.
3. الكيان القانوني لـCODEX: سعودي ولا إماراتي؟ (بيحدد Stripe/Cashier vs Moyasar/Tap، وZATCA).
4. هل Paddle بيقبل بائعين سعوديين فعليًا؟ وإيه رسومه مقارنة بـMoyasar/Tap؟ — غير مؤكدة.
5. هل Tap عنده Subscriptions API رسمي يغنينا عن بناء الجدولة؟ — غير مؤكدة.
6. موعد إطلاق AWS Riyadh (ديسمبر 2026) وAzure Saudi East فعليًا، وهل Laravel Cloud/Forge هيدعموا الـregions دي؟ — غير مؤكدة.
7. هل المؤسس مرتاح يتعلم PostgreSQL، ولا محتاج مراجع/استشارة في البداية؟ (أثر على الـtimeline)
8. Latency فعلية من السعودية لـFrankfurt/London vs Bahrain/UAE vs Riyadh للـdashboard والـwebhooks — محتاجة قياس.
9. هل stancl/tenancy v4 مجاني ومستقر للإنتاج؟ ولا نبني الـtenant scoping بنفسنا (حوالي trait + middleware)؟ — غير مؤكدة.
10. حجم بيانات RAG المتوقع per tenant (كتالوج + FAQ) — لتأكيد إن pgvector بدون partitioning كفاية في السنة الأولى.
11. هل Laravel AI SDK v1 هيستقر بدون breaking changes تانية خلال 2027؟ (نراقب الـUPGRADE.md والـreleases)
12. علاقة Laravel AI SDK بـPrism داخليًا (هل بيستخدمه أو بديل له) — مااتأكدتش بالكامل بسبب حد البحث.
