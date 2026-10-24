# CODEX Revenue OS

## ملخص المشروع
- **CODEX Revenue OS** منصة Multi-Tenant B2B SaaS من **CODEX AI Systems** (https://codexai.systems)، هدفها تبقى AI Revenue Operating System للشركات.
- مش AI Chatbot. النظام بيفهم نشاط الشركة، ويحلل بياناتها، ويكتشف المشاكل والفرص، ويقترح وينفّذ إجراءات في حدود قواعد وصلاحيات محددة.
- السوق الأول السعودية، وبعدها الخليج ومصر. القطاع الأول E-commerce: عطور، Beauty، Fashion، Personal Care، ومتاجر D2C.
- أول منتج (MVP) اسمه **CODEX Commerce**: تكامل مع Salla، وWhatsApp Business Platform الرسمية، وAI Sales Agent، وShared Inbox، وAbandoned Cart Recovery، وBasic Revenue Analytics، وBilling/Usage، وAdmin، وConsent.
- الميزة التنافسية المقترحة **Profit-Aware Autonomous Growth**، ولسه **فرضية محتاجة تتختبر** مقابل المنافسين: Thikaa وKasbly وKaram AI وMersal AI.

المرجع الكامل هو `docs/00-master-prompt.md`. شغل الـBlueprint في `docs/blueprint/`، والبحث الخام في `docs/research/`، والقرارات المعمارية في `docs/adr/`.

## المرحلة الحالية
تخطيط فقط (Master Blueprint v1.0). مفيش كود للمنتج. كل قسم بيتكتب ويستنى موافقة المؤسس قبل اللي بعده.

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

## الـStack المقترح (⚠️ لسه مش معتمد، ومحتاج يتراجع ويتقرر في ADR)
- Frontend: Next.js + TypeScript + Tailwind CSS
- Backend: Laravel، كـ**Modular Monolith** (المفضّل)
- Database: PostgreSQL
- Cache/Queues: Redis
- AI: LLM APIs + RAG + Tool Calling، ومعاهم Python/FastAPI أو LangGraph بس لو في مبرر واضح
- Infra: Docker، Cloud Hosting، Object Storage، Monitoring

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
- دعم كامل لـ**RTL** (العربي) و**LTR** (الإنجليزي)، مع `dir` ديناميكي حسب الـlocale.
- استخدم Tailwind **logical properties** بس: `ms-` و`me-` و`ps-` و`pe-` و`start-` و`end-` و`text-start` و`text-end`. ممنوع `ml-` و`mr-` و`pl-` و`pr-` و`left-` و`right-` و`text-left` و`text-right` للاتجاهات.
- الأيقونات اللي ليها اتجاه (أسهم وchevrons) لازم تنعكس في RTL.

## أسلوب الكتابة
- بالعربي الواضح، والمصطلحات التقنية بالإنجليزي.
- نقدي ومبني على أدلة. ممنوع مدح الفكرة من غير تحليل.
- استخدم جداول للمقارنات.
