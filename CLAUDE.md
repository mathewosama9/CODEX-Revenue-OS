# CODEX Revenue OS

## ملخص المشروع
- **CODEX Revenue OS** منصة Multi-Tenant B2B SaaS من **CODEX AI Systems** (https://codexai.systems)، هدفها تبقى AI Revenue Operating System للشركات.
- مش AI Chatbot. النظام بيفهم نشاط الشركة، ويحلل بياناتها، ويكتشف المشاكل والفرص، ويقترح وينفّذ إجراءات في حدود قواعد وصلاحيات محددة.
- السوق الأول السعودية، وبعدها الخليج ومصر. القطاع الأول E-commerce: عطور، Beauty، Fashion، Personal Care، ومتاجر D2C.
- أول منتج (MVP) اسمه **CODEX Commerce**: تكامل مع Salla، وWhatsApp Business Platform الرسمية، وAI Sales Agent، وShared Inbox، وAbandoned Cart Recovery، وBasic Revenue Analytics، وBilling/Usage، وAdmin، وConsent.
- الميزة التنافسية المقترحة **Profit-Aware Autonomous Growth**، ولسه **فرضية محتاجة تتختبر** مقابل المنافسين: Thikaa وKasbly وKaram AI وMersal AI.

المرجع الكامل هو `docs/00-master-prompt.md`. شغل الـBlueprint في `docs/blueprint/`، والبحث الخام في `docs/research/`، والقرارات المعمارية في `docs/adr/`.

## المرحلة الحالية
المؤسس بدأ التنفيذ يوم 2026-10-25 (خطة البناء في `docs/blueprint/16-build-plan-and-brand.md`):
- **P0a** شغالة: المقابلات والمنصات في `docs/phases/P0/`، والحالة في `STATUS.md`.
- **Sprint S0** (الأساس): Laravel 13، PostgreSQL، طبقة العزل بين التجار، وثيم البراند.
- **P1 Backend** و**P2 الواجهة (Next.js في `web/`)** خلصوا ومدموجين في `main`. وبعدها 4 sessions صلّحت كل مشاكل المراجعة ([قائمة المشاكل](docs/reviews/2026-10-25-p1-p2-issues.md)).
- **P3 الـAI Sales Agent** (E09/E10/E11/E12b): Claude Haiku 5.5 للإنتاج ورا `AiGateway` ([ADR-007](docs/adr/ADR-007-ai-orchestration.md))، والـPII في [ADR-008](docs/adr/ADR-008-pii-and-llm.md). Assisted بس. اتجرّب live على DeepSeek بس (تجربة)، وHaiku لسه متجربش.
- **بعد P3 (branches متراكبة، آخرهم `feat/multi-currency`):** الموقع التسويقي + «نظرة عامة» (`docs/website/site-plan.md`)، وربط **WooCommerce** للتجربة + plugin «CODEX Connector» (`docs/integrations/woocommerce-connector.md`)، و**تعدد العملات** (كل عملات الوطن العربي + الدولار، عملة ومنطقة زمنية لكل مساحة، [ADR-012](docs/adr/ADR-012-multi-currency.md)). متجر المؤسس rahaf.shop (جنيه) مربوط محليًا للتجربة.
- **Gate 0** لسه قايمة: مفيش تاجر حقيقي يتربط قبل ما ≥5 يدفعوا Proof Pilot.

## Critical Rules (بالنص من البرومبت الأصلي)

**Rule 1:** لا تبدأ بكتابة الكود الآن.

**Rule 2:** لا تعتبر جميع المميزات المذكورة ضرورية للـMVP.

**Rule 3:** لا تفترض أن ميزتنا التنافسية جديدة دون مقارنة المنافسين.

**Rule 4:** فرّق بين معلومات السوق المؤكدة، والافتراضات، والمقترحات.

**Rule 5:** راعِ أن الميزانية محدودة وأن المؤسس يشارك بنفسه في البرمجة.

**Rule 6:** لا تستخدم Microservices أو Infrastructure معقدة بدون مبرر.

**Rule 7:** اهتم بالـSecurity والـMulti-Tenancy من البداية.

**Rule 8:** لا تعتمد على واجهات WhatsApp غير الرسمية أو طرق تتجاوز قيود المنصات.

**Rule 9:** لا تجعل الـAI ينفذ معاملات مالية أو خصومات أو حملات عالية التكلفة دون سياسات وضوابط واضحة.

**Rule 10:** لا نريد بناء منتج ضخم قبل اختبار استعداد العملاء للدفع.

**Rule 11:** كل Phase يجب أن تكون لها Deliverables وExit Criteria واضحة.

**Rule 12:** فكر كصاحب شركة SaaS يسعى لبناء شركة مربحة، وليس كمبرمج يريد إضافة أكبر عدد من المميزات.

## تصنيف المعلومات (إلزامي)
أي معلومة عن السوق أو المنافسين أو الأسعار أو الأرقام لازم تكون متصنّفة:
- **مؤكدة**: معاها مصدر (رابط + تاريخ الاطلاع).
- **افتراض**: محتاجة تتختبر مع العملاء أو بالبيانات.
- **اقتراح**: توصية مننا.

لو ميزة ملقيتهاش عند منافس، تتكتب **"غير مؤكدة"**، مش "غير موجودة".

## الـStack (حسب الـspine §8، والـADRs بتتكتب في `docs/adr/`)
- Backend: **Laravel 13 Modular Monolith** كـ**API** (`/api/v1`، Sanctum SPA auth).
- Frontend: **Next.js + TS + Tailwind** في تطبيق منفصل، **قرار المؤسس 2026-10-25** ([ADR-002](docs/adr/ADR-002-frontend-nextjs.md)). التطبيق في `web/` (Next.js 16 App Router + Cache Components، Tailwind 4، shadcn/ui، TanStack Query). اقرا `web/AGENTS.md` قبل ما تكتب كود Next.js: الإصدار فيه تغييرات عن اللي تعرفه، والـdocs في `web/node_modules/next/dist/docs/`. Laravel مبيعرضش أي صفحات للتاجر خالص (الـInertia scaffold اتمسح): بيعرض API وFortify JSON وwebhooks والأدمن (Filament على `/admin`) بس، وأي حد بيفتح Laravel في المتصفح بيتحوّل للتطبيق.
- Database: **PostgreSQL + pgvector**. Queues: Redis + Horizon (من S1)، و`scheduled_actions` مصدر الحقيقة لأي تأخير.
- AI: من P2 بس، عن طريق Laravel AI SDK ورا `AiGateway`. مفيش Python ولا LangGraph في الـMVP.
- Admin: Filament داخلي. Hosting: GCP Dammam (Frankfurt مؤقت).

## الكود: قواعد لازم تتّبع
- **Tenant = Team.** أي model فيه بيانات تاجر لازم يستخدم `App\Shared\Tenancy\BelongsToTenant` ويبقى فيه `tenant_id`. أي job بيلمس بيانات تاجر يستخدم `TenantAware` ومعاه `public int $tenantId`. الـtenant بييجي من السيرفر بس، ولو مش موجود الـquery بتفشل.
- الـModules في `app/Modules/*` وقواعدها في `app/Modules/README.md`، ومفروضة بـ`tests/Arch`.
- الفلوس integer بأصغر وحدة لعملة المساحة (الأعمدة اسمها `*_halalas` تاريخيًا، [ADR-012](docs/adr/ADR-012-multi-currency.md))، ومن string لـinteger عن طريق `Currency::toMinor` بس، ممنوع float. الوقت UTC، والـquiet hours والتقارير بتوقيت المساحة (`WorkspaceLocale`).
- البراند: ألوان الثيم في `web/src/app/globals.css`، ومكوّنات البراند في `web/src/components/brand/`. **الأصفر للفلوس بس** (`bg-revenue` / `text-revenue-ink`)، ورقم أصفر واحد في الشاشة. Navy هو الثيم الافتراضي. أرقام لاتيني، والعملة «ر.س».

## التشغيل محليًا
- `docker compose up -d pgsql` (PostgreSQL + pgvector على منفذ **54320**، لأن 5432 مستخدم على جهاز المؤسس).
- `composer dev` بيشغّل كل حاجة: الـAPI (localhost:8000)، والـqueue، والـlogs، والتطبيق (localhost:3001). افتحهم على `localhost` مش `127.0.0.1` عشان الـsession cookie.
- `php artisan db:seed --class=DemoStoreSeeder` بيعمل متجر تجريبي (`owner@demo.test` / `agent@demo.test`، الباسورد `password`). محلي بس.
- `composer ci:check` لكل الفحوصات: RTL check، وlint وtsc بتوع `web/`، وPint، وPHPStan، وPest.
- `cd web && npm run e2e`: Playwright على الـAPI الحقيقي، بداتابيز `codex_e2e` وبورتات 8010/3010.
- الـtests بتشتغل على PostgreSQL (`codex_testing`) مش SQLite، عشان isolation suite تبقى حقيقية.
- الـAI محليًا من غير key: في `.env` حط `CODEX_AI_PROVIDER=demo` و`CODEX_EMBEDDINGS_ENABLED=false` و`CODEX_PRICE_MAX_AGE_MINUTES` رقم كبير (محلي بس). بعدين `php artisan codex:demo-message "بكم العود الكمبودي؟"` بيبعت رسالة عميل على مسار الـwebhook الحقيقي ([دليل P3](docs/phases/P3-ai-agent/testing-guide.md)).
- `php artisan ai:eval`: الـgolden set offline (من غير key، وبيشتغل في CI). `php artisan ai:eval --live`: الموديل الحقيقي، **بفلوس، ومحتاج موافقة المؤسس** ([دليل الحالات](docs/phases/P3-ai-agent/golden-set-guide.md)).

## إمتى تستخدم أنهي Plugin
| الشغل | Plugin / Skill |
|---|---|
| PRDs، Roadmaps، تحليل المنافسين، Sprint Planning | `product-management` (write-spec، roadmap-update، competitive-brief، sprint-planning) |
| ADRs، Architecture، System Design، التوثيق التقني، Testing Strategy | `engineering` (architecture، system-design، documentation، testing-strategy) |
| Positioning، Messaging، اختيار الـGTM motion | `gtm-skills` (positioning-and-messaging، gtm-motion-picker) |
| أي شغل واجهات | `frontend-design` + `vercel-react-best-practices` (⏸️ disabled لحد ما نبدأ نكتب واجهات) |
| Animations في الـlanding page | `gsap-skills` (⏸️ disabled لحد ما نشتغل على الـlanding page) |
| حملات ومحتوى تسويقي | `marketing` |
| تطوير features، وreview، وتبسيط الكود (لما نبدأ نكتب كود) | `feature-dev`، `pr-review-toolkit`، `code-simplifier` |
| Security review | `security-guidance` (hooks شغالة لوحدها) |
| Code intelligence | `php-lsp`، `typescript-lsp` |

لتفعيل plugin معمول له disable: `claude plugin enable <plugin>@<marketplace>`، وبعدها restart.

## قواعد الـFrontend
- النصوص في `web/src/i18n/ar.ts` و`en.ts` (الواجهة بالفصحى المبسّطة). الروابط `/ar/…` و`/en/…`.
- البيانات بتتجاب من المتصفح عن طريق `web/src/lib/api.ts` (Sanctum SPA + XSRF). ممنوع تخزين بيانات تجار أو عملاء في Next.js، أو tokens في localStorage.
- كل صفحة في `t/[team]/` عبارة عن `page.tsx` (server، فيه `instant = false`) + `screen.tsx` (client).
- اخفي أي حاجة المستخدم معندوش صلاحيتها (`useTeam().can(...)`)، والـAPI بيرفض على أي حال.
- دعم كامل لـ**RTL** (العربي) و**LTR** (الإنجليزي)، مع `dir` ديناميكي حسب الـlocale.
- استخدم Tailwind **logical properties** بس: `ms-` و`me-` و`ps-` و`pe-` و`start-` و`end-` و`text-start` و`text-end`. ممنوع `ml-` و`mr-` و`pl-` و`pr-` و`left-` و`right-` و`text-left` و`text-right` للاتجاهات.
- الأيقونات اللي ليها اتجاه (أسهم وchevrons) لازم تنعكس في RTL.

## أسلوب الكتابة
- بالعربي الواضح، والمصطلحات التقنية بالإنجليزي.
- نقدي ومبني على أدلة. ممنوع مدح الفكرة من غير تحليل.
- استخدم جداول للمقارنات.
