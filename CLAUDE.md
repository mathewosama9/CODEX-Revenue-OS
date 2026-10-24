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
- **Sprint S0** (الأساس) على branch `feat/S0-setup`: Laravel 13 + Inertia 3 + React 19، PostgreSQL، طبقة العزل بين التجار، وثيم البراند.
- **P1 Backend** خلص على `feat/S1-foundation`، و**P2 الواجهة (Next.js)** خلصت على `feat/P2-web` في `web/` (الحالة في `STATUS.md`، والاختبار في `docs/phases/P2-frontend/testing-guide.md`).
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
- Frontend: **Next.js + TS + Tailwind** في تطبيق منفصل، **قرار المؤسس 2026-10-25** ([ADR-002](docs/adr/ADR-002-frontend-nextjs.md)). التطبيق في `web/` (Next.js 16 App Router + Cache Components، Tailwind 4، shadcn/ui، TanStack Query). اقرا `web/AGENTS.md` قبل ما تكتب كود Next.js: الإصدار فيه تغييرات عن اللي تعرفه، والـdocs في `web/node_modules/next/dist/docs/`. الـscaffold بتاع Inertia/React مؤقت لحد ما الإعدادات تتنقل لـNext.js، **ممنوع تبني عليه شاشات جديدة**.
- Database: **PostgreSQL + pgvector**. Queues: Redis + Horizon (من S1)، و`scheduled_actions` مصدر الحقيقة لأي تأخير.
- AI: من P2 بس، عن طريق Laravel AI SDK ورا `AiGateway`. مفيش Python ولا LangGraph في الـMVP.
- Admin: Filament داخلي. Hosting: GCP Dammam (Frankfurt مؤقت).

## الكود: قواعد لازم تتّبع
- **Tenant = Team.** أي model فيه بيانات تاجر لازم يستخدم `App\Shared\Tenancy\BelongsToTenant` ويبقى فيه `tenant_id`. أي job بيلمس بيانات تاجر يستخدم `TenantAware` ومعاه `public int $tenantId`. الـtenant بييجي من السيرفر بس، ولو مش موجود الـquery بتفشل.
- الـModules في `app/Modules/*` وقواعدها في `app/Modules/README.md`، ومفروضة بـ`tests/Arch`.
- الفلوس بالهللة (integer)، والوقت UTC، والـquiet hours بتوقيت `Asia/Riyadh`.
- البراند: ألوان الثيم في `web/src/app/globals.css`، ومكوّنات البراند في `web/src/components/brand/` (نسخة Inertia القديمة في `resources/` لحد ما تتمسح). **الأصفر للفلوس بس** (`bg-revenue` / `text-revenue-ink`)، ورقم أصفر واحد في الشاشة. Navy هو الثيم الافتراضي. أرقام لاتيني، والعملة «ر.س».

## التشغيل محليًا
- `docker compose up -d pgsql` (PostgreSQL + pgvector على منفذ **54320**، لأن 5432 مستخدم على جهاز المؤسس).
- `composer dev` للـAPI (localhost:8000)، و`cd web && npm run dev` للتطبيق (localhost:3000). افتحهم على `localhost` مش `127.0.0.1` عشان الـsession cookie.
- `php artisan db:seed --class=DemoStoreSeeder` بيعمل متجر تجريبي (`owner@demo.test` / `agent@demo.test`، الباسورد `password`). محلي بس.
- `composer ci:check` لكل الفحوصات: vp check وtsc وRTL check (بيشمل `web/`) وlint وtsc بتوع `web/` وPint وPHPStan وPest.
- `cd web && npm run e2e`: Playwright على الـAPI الحقيقي، بداتابيز `codex_e2e` وبورتات 8010/3010.
- الـtests بتشتغل على PostgreSQL (`codex_testing`) مش SQLite، عشان isolation suite تبقى حقيقية.

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
