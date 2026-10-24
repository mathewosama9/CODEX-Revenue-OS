# 0. Decision Spine (المرجع الداخلي للأقسام)

> الحالة: مرجع داخلي اتكتب قبل الأقسام (2026-10-21). لو فيه اختلاف بينه وبين أي قسم، **القسم هو اللي بيتعتمد**.
> أخطاء معروفة لسه متصلحتش هنا: تكلفة Meta لمتجر 1,000 سلة (§7.2) حوالي 390–432 ريال مش 320، والـProof Pilot (§6.1) حوالي 72% من Sell & Recover لمدة 90 يوم مش 50%.

---

# CODEX Revenue OS: الـDecision Spine (المرجع الموحّد للـMaster Blueprint v1.0)

> **حالة الوثيقة:** مرجع إلزامي لكل كُتّاب أقسام الـBlueprint الـ13. أي رقم أو اسم أو ID أو قرار مكتوب هنا يتنقل **بالنص**. لو قسم محتاج يخالف حاجة هنا، يكتب الخلاف صراحةً في قسم "قرارات المؤسس" بدل ما يغيّرها بصمت.
> **التاريخ:** 2026-10-21. **المصادر:** `docs/00-master-prompt.md` وملفات `docs/research/*.md` (اطّلاع 2026-10-21).
> **التصنيف الإلزامي:** [مؤكدة] = عليها مصدر. [افتراض] = محتاجة اختبار. [اقتراح] = توصية مننا. [غير مؤكدة] = ملقيناش دليل في أي اتجاه.
> **Rule 1:** كل اللي هنا تخطيط. مفيش كود منتج قبل موافقة المؤسس على الـBlueprint، وحتى بعدها أول كود (spikes وCart Truth Audit) بيتكتب في P0b بس.

---

## 0. تقييم المقترحات الثلاثة (ملخّص)

| المعيار (من 5) | A: Validation-first | B: Differentiation-first | C: Risk-first |
|---|---|---|---|
| جودة الأدلة والتصنيف | 4 | 4 | **5** (أدق في الفصل بين مؤكد/افتراض، وأوضح في مخاطر Meta 4.7) |
| الواقعية لفريق صغير بميزانية محدودة | **5** (أقل بناء، concierge، Admin بـFilament) | 3 (فريق وميزانية أعلى، P1 طويل) | 4 (burn شهري واضح وrunway محسوب) |
| الالتزام بالـCritical Rules 1-12 | 5 | 4 (Cart Truth Audit فيه كود بدري، محتاج gating) | **5** (Gates وKill criteria وقرارات irreversible) |
| قوة التمايز ضد Karam وسلة | 3 | **5** (Cart Truth Audit، Proof Month، vertical depth في العطور) | 4 |
| **الإجمالي** | 17 | 16 | **18** |

**النتيجة:** الـspine مبني على **هيكل C** (Phases وGates وKill criteria والمعمارية)، وواخد من **A** الـconcierge والـpilot المدفوع مقدمًا والتقرير اليدوي في الأول وتقليص الـAdmin، وواخد من **B** الـCart Truth Audit وصيغة الـProof Month والتركيز الرأسي على العطور والتجميل وهيكل الأسعار 399/699/999. تفاصيل كل تعارض في القسم 13.

---

## 1. الأسماء والـIDs الرسمية

### 1.1 أسماء ثابتة
- اسم الشركة: **CODEX AI Systems**. اسم المنصة: **CODEX Revenue OS**. اسم أول منتج: **CODEX Commerce**.
- اسم الميزة التنافسية بعد إعادة الصياغة: **Profit-Proven Recovery**. الاسم القديم "Profit-Aware Autonomous Growth" يتذكر بس كأصل الفكرة.
- اسم التقرير الأساسي: **Incremental Profit Report**.
- اسم أداة الـdiscovery: **Cart Truth Audit**.
- اسم عرض الـpilot: **Proof Pilot** (90 يوم مدفوعة مقدمًا، فيها holdout إجباري).
- أسماء الطبقات الداخلية: `AiGateway`، `CommerceAdapter`، `scheduled_actions`، `webhook_events`، `audit_events`، `consent_records`.

### 1.2 الـModules (M01..M21)
الـ13 Module اللي في البرومبت اتعاد تقسيمهم، واتضاف 8 Modules أساسية (موضّحة بـ"جديد").

| ID | الاسم (English) | المقابل في البرومبت | أول Phase | ملاحظة |
|---|---|---|---|---|
| M01 | Identity, Access & Workspace | جزء من 13 + 12 (RBAC) | P1 | **جديد كـModule مستقل.** Tenant = Team، دوران بس في الـMVP (Owner, Agent) |
| M02 | Commerce Integrations (Commerce Adapter) | جزء من 10 | P0b/P1 | Salla أولًا، Zid في P4، Shopify/Woo في P5 |
| M03 | Messaging Channels | جزء من 2 | P1 | WhatsApp أولًا (Tech Provider + MM API)، Instagram/Messenger/Email/Live chat بعدين |
| M04 | Conversation Inbox & Handoff | جزء من 2 | P1 (lite) | Handoff queue + assign + notes + status بس في الـMVP |
| M05 | Catalog & Knowledge (RAG) | **جديد** | P2 | مزامنة الكتالوج، FAQ والسياسات، hybrid search (FTS + pgvector)، هامش لكل فئة |
| M06 | AI Sales Agent | 1 | P2 | Assisted ثم Scoped Autonomous |
| M07 | AI Gateway & Guardrails | **جديد** | P2 (أساسه P1) | metering لكل tenant، routing، price validator، golden set eval، PII pseudonymization |
| M08 | Revenue Recovery Engine | 4 | P1 | Abandoned Cart أولًا، COD conditional في P2، win-back/repeat في P4 |
| M09 | Measurement & Attribution | **جديد** (كان جوه 4) | P1 | holdout، experiment assignment، cost ledger، incremental profit، pooled priors |
| M10 | Consent & Compliance | **جديد** (كان جوه 3 و13) | P1 | consent ledger، opt-out، quiet hours، DPA، retention، ROPA، audit |
| M11 | Billing, Plans & Usage Metering | جزء من 13 | P1 (metering) / P3 (billing) | يدوي ثم Moyasar/Tap ثم Salla billing |
| M12 | Platform Administration & Ops | 13 | P1 (lite) | super admin، integration health، feature flags، observability |
| M13 | Smart CRM | 3 | P4 (lite) | profiles وsegments وtimeline مبنيين على بيانات سلة |
| M14 | Revenue Analytics & AI Business Analyst | 6 | P1 (تقرير واحد) / P5-P6 | NL analytics في P5، الـAnalyst الكامل في P6 |
| M15 | AI Marketing Studio & Campaigns | 5 | P5 | حملات بموافقة صريحة فقط، من غير تغيير ميزانيات إعلانية تلقائيًا |
| M16 | Operations Automation | 7 | P6 | order workflows وshipping notifications وinventory alerts |
| M17 | Automation Workflow Builder | 9 | P6 | الـMVP بيستخدم workflows ثابتة بالكود، مش builder |
| M18 | Integrations Marketplace & Public API | جزء من 10 | P5-P6 | webhooks outbound، REST API، n8n، shipping، ERP، accounting |
| M19 | AI Voice Agents | 8 | P6 (gated) | محتاج business case منفصل ودراسة قوانين المكالمات |
| M20 | Agency & Partner Program | 11 | P3 (referral) / P5 (dashboard) / P6 (white label, gated) | من غير white label قبل شهر 24 |
| M21 | Enterprise Platform | 12 | P6 | advanced RBAC، SLA، retention policies، deployment options |

### 1.3 الـPhases (P0..P6)

| ID | الاسم |
|---|---|
| P0 | Discovery & Platform Gates (P0a: من غير كود. P0b: spikes + Cart Truth Audit بعد اعتماد الـBlueprint) |
| P1 | Measured Recovery Pilot (Private Salla App) |
| P2 | Conversational Selling MVP |
| P3 | Public Launch & Monetization (Salla App Store) |
| P4 | Profit Engine v2 + Zid + CRM Lite |
| P5 | Channels, Partners & GCC Expansion |
| P6 | Revenue OS Platform |

**تعريف "MVP":** الـMVP = اللي بيتسلّم في P1 + P2، ويتعتبر "اتطلق واتسيّل" عند نهاية **P3**. مفيش أي Module متقدم (M13 وما بعده) بيتبني قبل ما P3 exit criteria تتحقق.

### 1.4 الـMVP Epics (E01..E18)

| ID | Epic | Module | Phase | أولوية |
|---|---|---|---|---|
| E01 | Workspace, Auth & Tenancy foundation | M01 | P1 | P0 |
| E02 | Salla Adapter: OAuth (Easy Mode)، token refresh بـlock، webhooks، reconciliation polling | M02 | P0b/P1 | P0 |
| E03 | WhatsApp Tech Provider onboarding: Embedded Signup v4، WABA للتاجر، Coexistence، templates، MM API | M03 | P1 | P0 |
| E04 | Consent ledger، opt-out بكلمات عربي، quiet hours | M10 | P1 | P0 |
| E05 | Recovery workflow: `scheduled_actions`، eligibility، rule-based scoring، discount policy engine + approvals | M08 | P1 | P0 |
| E06 | Holdout & Experiment engine + Cost ledger (Meta، خصومات، LLM) | M09 | P1 | P0 |
| E07 | Incremental Profit Report (يدوي أول 4 أسابيع، بعدين صفحة واحدة في المنتج) | M09/M14 | P1 | P0 |
| E08 | إدخال الهامش لكل فئة (5–10 فئات) وقت الـonboarding | M05/M09 | P1 | P0 |
| E09 | Catalog sync & Knowledge base (FAQ، شحن، استرجاع) + hybrid search | M05 | P2 | P0 |
| E10 | AiGateway: metering، routing، price/stock validator، golden set eval (≥300 حالة سعودي) | M07 | P2 | P0 |
| E11 | AI Sales Agent: Assisted mode ثم Scoped Autonomous، `checkout_url`، handoff | M06 | P2 | P0 |
| E12 | Handoff Inbox lite: queue، assign، notes، status، سجل محادثات الـAI | M04 | P1 (lite)/P2 | P1 |
| E13 | Usage metering (من أول يوم) + فوترة يدوية/روابط دفع ثم Salla billing | M11 | P1→P3 | P1 |
| E14 | Internal admin (Filament) + integration health + token/quality monitoring | M12 | P1 | P1 |
| E15 | Security baseline: `audit_events` append-only، PII pseudonymization، isolation tests في CI، Sentry/Nightwatch بـredaction | M10/M12 | P1 | P0 |
| E16 | COD Confirmation (مشروط بنتيجة P0) | M08 | P2 | P1 (conditional) |
| E17 | Cart Truth Audit (read-only على سلة، من غير WhatsApp ولا LLM) | M02/M09 | P0b | P1 |
| E18 | Salla App Store public listing + self-serve onboarding | M02/M11 | P3 | P0 للـP3 |

---

## 2. الحكم التجاري، المشكلة الأساسية، والـICP

### 2.1 الحكم
**الفكرة كما هي مكتوبة في البرومبت ضعيفة تجاريًا. لو ضيّقناها وغيّرنا زاويتها تبقى متوسطة لقوية، وقابلة للاختبار بتكلفة قليلة.**

أسباب الضعف:
- **استرجاع السلات بالواتساب بقى commodity:** سلة عندها تذكير native بخصم ولحد 5 تذكيرات [مؤكدة: salla-platform.md]، وSalla Chat بيبعت رسالة تسويق بحوالي 0.16 ريال [مؤكدة: market-and-pricing.md]، وZid WhatsApp ببلاش في Professional و109 ريال في غيره [مؤكدة]، وفيه تطبيقات بـ49–119 ريال، منها ATMET بـAI agent سعودي بـ49–249 [مؤكدة].
- **"AI بيرد بالعربي" بقى سلعة:** Meta Business Agent من 2026-07-01 بـ$2 لكل 1M token [مؤكدة]، وKaram بيقدّم أغلب الـMVP بسعر فعلي 268–698 ريال [مؤكدة].
- **اقتصاد الرسائل بيتضغط:** Marketing في السعودية 0.0576 USD (+~27% في 7 شهور) [مؤكدة، مصادر ثانوية متطابقة]، والـService بقى بفلوس بعد 1,000 رسالة لكل رقم في الشهر من 2026-10-01 [مؤكدة].
- **اعتماد على 3 منصات:** Meta (القسم 4.7 ومنع التدريب)، وسلة (Easy Mode إجباري، وعمولة ~30% [افتراض]، وتقدر توقف أي تطبيق [مؤكدة])، والمنظّم (مسودة تخزين البيانات جوه المملكة [مؤكدة كمسودة]).
- **13 Module = منتج لشركة فيها 50 شخص**، مش لمؤسس بيكتب الكود (Rules 5 و12).

### 2.2 المشكلة الأساسية (صيغة رسمية)
> [افتراض] متاجر سلة المتوسطة بتدفع كل شهر رسوم واتساب وخصومات عشان تسترجع مبيعات، ونسبة كبيرة منها كانت هتحصل كده كده. ومفيش أداة بتقولهم بصراحة: الرسالة دي تستاهل ولا لأ، والخصم ده بيكسّبك ولا بيخسّرك.

الأدلة:
- [مؤكدة] تذكير بعد 30–60 دقيقة **قلّل** الشراء مقارنةً بمجموعة التحكم (Li et al., Journal of Marketing 2021).
- [مؤكدة] تذكير SMS من غير خصم لسلة عمرها 4–28 يوم مكانش له أثر معنوي (1.21% مقابل 1.41%) (MSI 2021).
- [مؤكدة] كل الأدوات بتعرض إيراد منسوب بـlast-touch (Klaviyo نافذة 5 أيام).

**أخطر افتراض في المشروع كله:** [افتراض] التاجر هيدفع مقابل رقم أصغر وأصدق. P0 بيختبره بفلوس حقيقية. البديل لو فشل: زاوية "بنوفّر عليك رسائل وخصومات ملهاش لازمة" أو COD.

**سؤال مفتوح لـP0:** أنهي ألم أكبر: استرجاع السلات، ولا تأكيد الـCOD، ولا الرد على محادثات البيع.

### 2.3 الـICP (للـpilot)

| البُعد | التحديد |
|---|---|
| المنصة | **سلة فقط** حتى P4 |
| القطاع | **العطور والـBeauty والـPersonal Care أولًا**. الـFashion بيدخل المقابلات للمقارنة، لكن الـagent مش متضبط ليه قبل P4 (المقاسات والمرتجعات) |
| الحجم | 300–3,000 طلب في الشهر، و≥400 سلة مؤهلة (فيها موبايل) في الشهر |
| السلوك | بيستخدم WhatsApp فعلًا (يدوي أو Karam أو Salla Chat أو تطبيق رخيص)، وبيصرف على خصومات أو رسائل |
| صاحب القرار | المالك أو مدير التسويق/العمليات، وبيبص على الهامش |
| مستبعد | أقل من 300 طلب (سقف السعر 49–119 ريال والحجم مش كفاية للقياس)، والـenterprise |
| حجم السوق | SAM حوالي 2,000–5,700 متجر على سلة وزد [افتراض: market-and-pricing.md]، والهدف الواقعي 50–150 متجر بيدفع في 18 شهر [افتراض] |

---

## 3. الـPositioning والحكم على Profit-Aware Autonomous Growth

### 3.1 Positioning statement [اقتراح، يتختبر في P0]
> لمتاجر العطور والتجميل على سلة اللي بتصرف على رسائل واتساب وخصومات من غير ما تعرف عائدها الحقيقي: **CODEX Commerce منصة مبيعات واسترجاع على واتساب الرسمي، بتقولك كام ربحت فعلًا بسببها.** كل تدخّل له مجموعة مقارنة، وكل خصم محكوم بأرضية هامش، وكل رد مبني على كتالوج متجرك الحي. بعكس الأدوات اللي بتحسب أي طلب بعد الرسالة على إنه نجاح.

- **الـCategory:** "Profit Recovery لمتاجر سلة". **مش** "AI Chatbot" ولا "Omnichannel Inbox".

### 3.2 ممنوع في أي رسالة تسويقية
| الجملة الممنوعة | السبب |
|---|---|
| "AI Chatbot" أو "AI Agent" كعنوان رئيسي | خطر تصنيف Meta كـAI Provider (القسم 4.7) [مؤكدة: الشرط؛ غير مؤكدة: تطبيقه علينا] |
| "أول من يقيس الـincrementality" | Klaviyo وBraze وBloomreach وPostscript عندهم holdouts، وKaram بيكتب عن الفكرة [مؤكدة] |
| "بياناتك كلها في السعودية" | WhatsApp Cloud API مفيهوش تخزين في السعودية، والـLLMs بتعالج برّه المملكة [مؤكدة] |
| "Autonomous" | Rule 9، ومش قيمة إضافية |
| "رقم ربحك الدقيق من أول شهر" | مستحيل إحصائيًا للمتجر المتوسط [افتراض: حسابات power] |
| "% استرجاع" من غير holdout | نفس الخطأ اللي بننتقده |

الصيغة المسموحة للبيانات: **"PDPL by design + البيانات الأساسية مستضافة في السعودية (بعد ترحيل Dammam)"**.

### 3.3 الحكم: **Narrow + Reframe** (منلغيهاش)

| العنصر الأصلي | القرار | الدليل |
|---|---|---|
| 1. السلات الأكثر قابلية للاسترجاع | **Keep كقواعد مش ML** (عمر السلة، قيمتها، تركيزها، هامش الفئة) | [مؤكدة] خصائص السلة شرحت التباين أحسن من تاريخ الشراء (MSI) |
| 2. هل المتابعة تستاهل تكلفتها | **Keep.** أوضح قيمة بتتقاس بالفلوس (suppression) | [افتراض] متجر 1,000 سلة × رسالتين ≈ 320 ريال/شهر لـMeta |
| 3. خصم ميخسّرش الصفقة | **Shrink لقواعد:** مفيش خصم في أول رسالة، أرضية هامش لكل فئة، لا خصم للعميل الحالي إلا بموافقة، أي حاجة فوق السقف = approval. **الـAI عمره ما بيقرر الخصم** | [مؤكدة] Anderson & Simester 2004 (cannibalization عند العملاء الحاليين). Rule 9 |
| 4. التوقيت المناسب | **Keep كقاعدة افتراضية:** أول رسالة بعد ≥60 دقيقة، وquiet hours. التحسين بالـholdout المجمّع | [مؤكدة] Li et al. 2021. CST 22:00–09:00 [مؤكدة كنص؛ انطباقه على WhatsApp غير مؤكد] |
| 5. Control groups | **Keep، قلب الميزة:** holdout افتراضي 10% على مستوى العميل بـhash ثابت، **إجباري في الـpilots** | [غير مؤكدة] مش موجود عند الأربع منافسين بشكل مؤكد |
| 6. نتائج مالية واضحة | **Keep = المنتج الأساسي** (Incremental Profit Report) | [مؤكدة] Karam بيعرض "orders after chat" |
| 7. شرح التوصيات | **Parity** | [مؤكدة] Karam Autopilot |
| 8. Approval workflows | **Parity إجبارية** | [مؤكدة] Kasbly shadow mode وautonomy levels |
| "Autonomous" | **Cut** من الاسم والـMVP | Rule 9، Meta 4.7، خطر الهلوسة |

### 3.4 إيه اللي بنوعد بيه وإيه اللي مش بنوعد بيه
- **بنوعد:** "تقدير صادق للربح الإضافي، في صورة نطاق مع win probability، بيتحسن مع الوقت، ومبني على بيانات متاجر شبهك". وتقرير بيفصل 4 أرقام: إيراد منسوب، إيراد إضافي تقديري (نطاق)، تكلفة الرسائل والخصومات، ربح إضافي تقديري.
- **مش بنوعد:** رقم incrementality خاص بالمتجر في أول شهر، uplift model لكل تاجر، COGS لكل SKU، قرارات مستقلة بالخصم.
- **مصدر الاستدامة [اقتراح]:** (1) priors مجمّعة من متاجر العطور والتجميل على سلة (network effect). (2) منهجية منشورة. (3) سمعة "الأرقام الصادقة". Karam ممكن ينسخ الـholdout في أسابيع [افتراض]، فالسبق في **البيانات والوقت**، مش الفكرة.
- **قيد إلزامي (ADR-009):** الـpooling بيتحسب من **نتائج طلبات سلة وتخصيص المجموعات بس**، مش من نصوص محادثات WhatsApp [مؤكدة: شروط Meta بتمنع استخدام بيانات WABA ولو مجمّعة لتدريب أو تحسين أي نموذج]، ولازم يتذكر في الـDPA ويعدّي على رأي قانوني لأنه ممكن يحوّل CODEX من Processor لـController [افتراض].

---

## 4. نطاق الـMVP

### 4.1 الـ10 وظايف بتوع المؤسس

| # | الوظيفة | القرار | التفاصيل | Epic |
|---|---|---|---|---|
| 1 | SaaS Workspace | **Shrink** | Tenant = Team من Laravel React starter kit. دوران (Owner, Agent). من غير RBAC متقدم ولا multi-workspace للوكالات | E01 |
| 2 | Salla Integration | **Keep (private app أولًا)** | قراءة products/orders/abandoned carts/customers، webhooks + reconciliation polling، token refresh في job واحدة بـdistributed lock. **إنشاء cart: Cut** (مفيش endpoint موثّق)، نستخدم `checkout_url` | E02 |
| 3 | WhatsApp Integration | **Keep** | Tech Provider + Embedded Signup v4 + Coexistence، WABA وportfolio لكل تاجر، رسوم Meta على كارت التاجر، Marketing على MM API من أول يوم | E03 |
| 4 | AI Sales Agent | **Shrink + تأجيل لـP2** | Assisted (مسودة والموظف يوافق) ≥4 أسابيع، بعدين Scoped Autonomous: كتالوج، ترشيح (عطور: نوتات/مناسبة/ثبات من بيانات التاجر)، شحن واسترجاع من الـFAQ، `checkout_url`، handoff. **ممنوع:** خصومات، payment links مخصصة، أسئلة عامة، طلب بيانات كارت. الرد باللهجة السعودية، والفهم سعودي + مصري | E09–E11 |
| 5 | Shared Inbox | **Shrink قوي** | Coexistence بيخلّي الموظف على تطبيق WhatsApp Business. بنبني handoff queue + assign + notes + status + سجل محادثات الـAI + زرار takeover. من غير tags متقدمة ولا SLA analytics | E12 |
| 6 | Abandoned Cart Recovery | **Keep = الـwedge** | holdout 10%، consent إجباري، quiet hours، أول رسالة ≥60 دقيقة، مفيش خصم في أول رسالة، تصنيف Marketing دايمًا، scoring بقواعد، `scheduled_actions` | E05 |
| 7 | Basic Revenue Analytics | **Keep + Reframe** | = Incremental Profit Report. أول 4 أسابيع من P1 بيتطلع يدوي (SQL + template)، وبعدين صفحة واحدة في المنتج. من غير dashboards عامة | E06–E07 |
| 8 | Subscription & Usage | **Manual** | فواتير وروابط دفع (Moyasar/Tap) في P1–P2، metering آلي من أول يوم، Salla billing في P3 | E13 |
| 9 | Admin Dashboard | **Shrink** | Filament panel داخلي: tenants، صحة التكاملات، حالة الـtokens، quality rating للرقم، الاستهلاك. من غير "SaaS revenue analytics" قبل 30 عميل | E14 |
| 10 | Security & Consent | **Keep كامل، مفيش تفاوض** | consent ledger (المصدر والوقت والفئة)، opt-out بكلمات عربي <24 ساعة، quiet hours، `audit_events` append-only، PII خارج الـLLM، isolation tests، DPA عربي، سياسة خصوصية، incident runbook (إخطار التاجر خلال 24 ساعة)، ROPA وقائمة sub-processors، retention قابلة للضبط | E04، E15 |

### 4.2 حاجات اتضافت للـMVP
Experiment & Holdout engine (E06)، Cost ledger (E06)، هامش لكل فئة (E08)، AiGateway وgolden set (E10)، Commerce Adapter ونموذج بيانات داخلي مستقل عن payloads سلة (E02)، Integration health وreconciliation (E02/E14)، Cart Truth Audit (E17)، عُدّة امتثال (DPA، DPIA template، privacy policy)، Onboarding بإيدنا كخدمة (إعداد WABA والـtemplates والـBV).

### 4.3 MVP In / Out

| داخل الـMVP (P1–P3) | خارج الـMVP (مع الـPhase) |
|---|---|
| Salla (private ثم public) | Zid (P4)، Shopify/Woo (P5) |
| WhatsApp رسمي فقط | Instagram/Messenger (P5)، Email/Live chat (P5–P6)، أي WhatsApp غير رسمي (مرفوض للأبد، Rule 8) |
| Abandoned Cart Recovery + holdout | Failed payment، win-back، repeat، cross-sell (P4) |
| COD Confirmation (P2، **لو P0 أكدت**) | Uplift/ML models (P4+ بعد بيانات مجمّعة كفاية) |
| AI Sales Agent محصور في الكتالوج | Marketing Studio وBroadcasts (P5) |
| Incremental Profit Report | NL analytics (P5)، AI Business Analyst (P6) |
| Handoff inbox lite | Inbox كامل بـtags وSLA (P5) |
| Usage metering + فوترة يدوي ثم Salla billing | Usage overage billing (P3 لو سلة سمحت، وإلا P4 مباشر) |
| Internal admin | Workflow builder (P6)، Ops automation (P6) |
| — | Smart CRM (P4 lite)، Voice (P6 gated)، Agency dashboard (P5)، White label (P6 gated)، Enterprise (P6)، Outcome fee (P4) |

---

## 5. جدول المراحل

> كل المدد والفرق والميزانيات [افتراض]. الميزانية بالريال **من غير راتب المؤسس**، وبافتراض مهندسين في مصر (الفريق في السعودية ≈ 2x). Burn = شهري.

| ID | الاسم | الهدف | الـModules | المدة | الفريق | الميزانية | Exit Criteria (كلها لازم تتحقق) |
|---|---|---|---|---|---|---|---|
| **P0** | Discovery & Platform Gates | نقتل أكبر المجاهيل التجارية والمنصّاتية قبل كود المنتج | P0a: من غير كود. P0b: M02 (spikes + E17) | 5–7 أسابيع | المؤسس + مستشار قانوني بالساعة + (اختياري) باحث part-time في السعودية | 25–50 ألف إجمالي (burn 5–10 ألف + قانوني 15–30 ألف) | (1) ≥20 مقابلة في الـICP (هدف 25). (2) ≥8 design partners موقعين LOI، و**≥5 منهم دفعوا Proof Pilot مقدمًا** (1,500 ريال). (3) ≥60% بيفضّلوا "ربح إضافي صادق" على "إيراد مسترجع". (4) ≥5 متاجر عندهم ≥400 سلة مؤهلة/شهر (بيانات حقيقية من Cart Truth Audit). (5) ≥5 قبلوا holdout 10%. (6) Meta BV مقبولة وApp Review متقدّم. (7) رأي قانوني مكتوب: Meta 4.7، دور CODEX تحت PDPL، الكيان والضريبة. (8) Spikes ناجحة على demo store: `checkout_url`، webhooks السلات، token refresh، إرسال template على MM API. (9) ADR-001..011 معتمدة. (10) قرار الـwedge (Recovery / +COD) |
| **P1** | Measured Recovery Pilot | استرجاع end-to-end بـholdout وتقرير ربح، **من غير AI agent** | M01، M02، M03، M04 (lite)، M08، M09، M10، M11 (metering)، M12 (lite) | 10–12 أسبوع | المؤسس (dev + بيع) + 1 Laravel full-stack full-time + مصمم part-time | burn 25–45 ألف ≈ 75–135 ألف للمرحلة. البنية $150–400/شهر | (1) ≥6 متاجر live ≥60 يوم. (2) ≥4 منهم بيدفعوا، و≥3 من أول 5 جددوا بسعر كامل ≥399. (3) ≥4,000 سلة مؤهلة اتعالجت (علاج + تحكم). (4) أول تقدير مجمّع للأثر بـinterval معلن. (5) صفر تسريب cross-tenant في isolation suite. (6) 100% opt-out <24 ساعة. (7) صفر تقييد أو quality warnings من Meta. (8) صفر webhooks ضايعة من غير reconciliation. (9) ≥3 تجار بيقولوا إنهم غيّروا قرار (خصم أو إرسال) بسبب التقرير. (10) وقت الفريق اليدوي ≤4 ساعات/تاجر/أسبوع |
| **P2** | Conversational Selling MVP | Agent بيقفل البيعة جوه المحادثة + COD لو اتأكد | M05، M06، M07، M04، M08 (COD) | 8–12 أسبوع | فوقهم: مهندس AI/QA part-time (eval وgolden set) + CS part-time بيتكلم سعودي | burn 35–55 ألف ≈ 90–140 ألف. LLM $100–500/شهر | (1) دقة السعر والمخزون **100%** على ≥300 حالة باللهجة السعودية. (2) الـvalidator بيمنع 100% من الأرقام اللي مش من نتائج الـtools. (3) تكلفة المحادثة ≤$0.03. (4) handoff rate ≤40%. (5) ≥4 أسابيع Assisted قبل Autonomous لأي tenant. (6) ≥10–15 متجر بيدفع. (7) retention ≥80% بعد 3 شهور للي بدأوا في P1. (8) صفر حوادث خصم أو سعر غلط اتبعت لعميل |
| **P3** | Public Launch & Monetization | توزيع قابل للتكرار عن طريق متجر سلة + فوترة | M11، M02 (public)، M12، M20 (referral) | 8–12 أسبوع (مراجعة سلة 3–6 أسابيع بالتوازي) | فوقهم: CS/Support عربي full-time | burn 45–70 ألف ≈ 110–175 ألف + تسويق 10–20 ألف | (1) Listing منشور على Salla App Store. (2) 30–50 متجر بيدفع. (3) MRR 15–30 ألف ريال. (4) gross margin ≥60% بعد عمولة سلة والـAI والاستضافة. (5) logo churn ≤5%/شهر. (6) self-serve onboarding ≤48 ساعة لـ70% من التجار. (7) الإنتاج على GCP Dammam (أو قرار ADR-005 موثّق بالبديل). **= الـMVP اتطلق واتسيّل** |
| **P4** | Profit Engine v2 + Zid + CRM Lite | تقليل الاعتماد على سلة وتعميق الميزة | M02 (Zid)، M08 (win-back، repeat، failed payment)، M09 (pooled priors)، M13 (lite)، M11 (overage، annual، outcome fee) | 4–6 شهور | 4–6 أشخاص (+ مطوّر تكاملات + مبيعات/شراكات) | burn 70–120 ألف | (1) 100–150 متجر بيدفع. (2) ARR 0.5–1.25 مليون ريال. (3) ≥15% من الإيراد من زد. (4) NRR ≥100%. (5) pooled priors بنطاقات ثقة أضيق ≥30% من تقدير التاجر لوحده. (6) gross margin ≥65%. (7) CAC payback ≤6 شهور |
| **P5** | Channels, Partners & GCC | قنوات وشركاء وسوق تاني | M03 (Instagram، Messenger، Email)، M04 (inbox كامل)، M15 (حملات بموافقة)، M14 (NL analytics)، M02 (Shopify/Woo حسب الطلب)، M18 (webhooks/API/n8n)، M20 (agency dashboard) | 6–12 شهر | 7–10 أشخاص | burn 120–200 ألف (من الإيراد أو تمويل) | (1) ≥300 متجر. (2) ARR ≥2.5–3 مليون ريال. (3) NRR ≥100%. (4) ≥20% من العملاء الجداد من partners. (5) أول سوق خليجي (الإمارات) فيه ≥20 متجر |
| **P6** | Revenue OS Platform | المنصة الكاملة، Module بـModule بـbusiness case | M14 (AI Business Analyst)، M16، M17، M18 (ERP، shipping، accounting)، M19 (Voice، gated)، M20 (White label، gated)، M21 (Enterprise)، مصر وباقي الخليج | 12–24 شهر | 10+ | حسب الإيراد/التمويل | كل Module بيبدأ بس لو ≥5–10 عملاء ملتزمين يدفعوا تمنه (gate منفصلة لكل Module). Voice محتاج دراسة تكلفة وقوانين مكالمات. White label بعد شهر 24 على الأقل |

**Kill / Pivot criteria [اقتراح، تتعتمد رسميًا]:**
- آخر P0: أقل من **5** تجار دفعوا مقدم → منبنيش. نراجع الـwedge (COD أو "توفير التكلفة").
- Meta رفضت أو صنّفتنا AI Provider → recovery/COD من غير محادثة AI، أو قناة تانية.
- آخر P1: أقل من **2** جددوا، أو الأثر الإضافي ≈ صفر والتجار مش هيدفعوا للصراحة → Pivot لـ"توفير التكلفة" أو COD.
- أي Gate فشلت مرتين → وقفة مراجعة كاملة قبل أي إنفاق جديد.

**الجدول الزمني التقريبي [افتراض]:** P0: أواخر أكتوبر–منتصف ديسمبر 2026. P1: ديسمبر 2026–مارس 2027. P2: مارس–مايو 2027. P3: يونيو–أغسطس 2027. P4: سبتمبر 2027–فبراير 2028. P5: 2028. P6: من 2029 (أو أبدر لو الأرقام سمحت).

---

## 6. التسعير ونماذج الإيراد

### 6.1 فرضية التسعير [اقتراح، تتختبر في P0–P1]
الأسعار شهرية، من غير VAT. **رسوم Meta دايمًا على WABA وكارت التاجر مباشرة، من غير markup.**

| الباقة | السعر | لمين | المحتوى |
|---|---|---|---|
| Proof Pilot | **1,500 ريال مقدم لـ90 يوم** (≈50% من Sell & Recover) | أول 5–10 design partners | holdout إجباري، تقرير أسبوعي، onboarding بإيدنا، case study. بيتخصم من أول اشتراك سنوي. وعد: لو التقدير المجمّع للربح الإضافي طلع مش موجب، الشهر التالت مجاني |
| Recover | **399** | 300–800 طلب/شهر | استرجاع بـholdout، Incremental Profit Report، handoff inbox، لحد 1,500 سلة مؤهلة |
| Sell & Recover | **699** | 800–3,000 طلب/شهر | فوقه: AI Sales Agent بحصة 1,000 محادثة (الزيادة 0.25 ريال/محادثة من P3/P4) |
| Pro | **999+** | 3,000+ | حجم أكبر، COD، أكتر من رقم، مراجعة شهرية، أولوية دعم |

- المرجع: Karam فعليًا 268–698 [مؤكدة]. أي سعر فوق 700 لازم يبرره دليل مالي.
- [افتراض] على متجر سلة بعمولة ~30%: 699 → ~490 صافي، و399 → ~280. الـpilots والعقود السنوية تتباع مباشرة (Moyasar/Tap).
- AI COGS للمتجر المتوسط $9–45/شهر (~35–170 ريال) مع routing رخيص [افتراض]. القاعدة: AI COGS + دعم ≤35% من الصافي.

### 6.2 نماذج الإيراد

| النموذج | القرار | التوقيت | السبب |
|---|---|---|---|
| Monthly Subscriptions | **Now** | P1 | الأساس |
| Usage-Based Pricing | **Now (metering) / Later (overage billing)** | metering P1، billing P3–P4 | Service بقت بفلوس [مؤكدة]. مسموحية usage billing عن طريق سلة [غير مؤكدة] |
| Annual Subscriptions | **Later** | P3 | بخصم = شهرين، بعد قياس churn ≥3 شهور |
| Premium AI Agents | **Later، ومدمج في الباقات** | P4–P5 | الـAI كـadd-on منفصل هو نقطة الضعف اللي بنهاجم بيها Karam |
| Agency Partnerships | **Later، referral فقط** | P3 (referral)، P5 (dashboard) | عمولة محددة بمدة. Mersal بيوضح إن الوكالة ممكن تبقى منافس [مؤكدة] |
| Enterprise Plans | **Later** | P6 | مفيش طلب مثبت، وتقيل على فريق صغير |
| Custom Integrations | **Reject** لحد P5 | — | فخ الخدمات (Rule 12). استثناء: مدفوع بالكامل وبيتحول لـfeature عامة |
| White Label | **Reject** لحد شهر 24 على الأقل | P6 gated | بيضعف البراند ويعقّد علاقتنا بـMeta |
| (إضافي) Outcome fee 2–5% على الربح الإضافي المثبت | **Later، اختياري** | P4 | Zoko 3% [مؤكدة]. محتاج pooled estimate موثوق الأول |

---

## 7. جدول الأرقام الموحّدة

> العملة: 1 USD = 3.75 SAR. أي قسم بيستخدم رقم من دول ينقله **بالظبط** بتصنيفه.

### 7.1 السوق والمنافسين
| الرقم | القيمة | التصنيف والمصدر |
|---|---|---|
| متاجر سلة النشطة (معلن) | 68,000+ (متناقض: 60,000 في صفحة الشركاء، وStoreLeads 26,138 live) | [مؤكدة، متناقضة] salla.com، storeleads.app |
| متاجر زد (معلن) | 12,000–13,500+ (StoreLeads 14,439 live) | [مؤكدة، متناقضة] zid.sa، storeleads |
| سجلات تجارة إلكترونية في السعودية (Q4 2025) | 43,854 | [مؤكدة] Okaz عن وزارة التجارة |
| SAM | 2,000–5,700 متجر (300+ طلب، القطاعات المستهدفة) | [افتراض] |
| هدف 18 شهر | 50–150 متجر بيدفع | [افتراض] |
| Karam السعر الفعلي | 268–698 ريال/شهر | [مؤكدة] apps.salla.sa |
| تطبيقات استرجاع رخيصة | 49–119 ريال/شهر | [مؤكدة] apps.salla.sa |
| ATMET | 49/99/249 ريال | [مؤكدة] |
| Zid WhatsApp | مجاني في Professional، 109 ريال غيره | [مؤكدة] zid.sa |
| Salla Chat رسالة تسويق | ~0.16 ريال (4.06 نقطة) | [مؤكدة] help.salla.sa |
| عمولة متجر سلة | ~30% | [افتراض] حاسبة partners.salla.com |
| مراجعة متجر سلة | 3–6 أسابيع للتخطيط (مفيش SLA) | [افتراض] مبني على [مؤكدة] شكوى pending ~15 يوم |
| مستحقات سلة | تتصرف بعد ~15 يوم | [مؤكدة] apps-policy |
| معدل ترك السلة عالميًا | 70.22% | [مؤكدة] Baymard 2025 (السعودية 77% [غير مؤكدة]) |

### 7.2 WhatsApp وMeta
| الرقم | القيمة | التصنيف والمصدر |
|---|---|---|
| رسالة Marketing السعودية | 0.0576 USD (~0.216 ريال) من 2026-10-01 | [مؤكدة، مصادر ثانوية؛ فيه أرقام متعارضة 0.0518–0.0662] |
| Utility/Service/Auth السعودية | 0.0107 USD (~0.040 ريال) | [مؤكدة، ثانوية] |
| Service مجاني | أول 1,000 رسالة/رقم/شهر من 2026-10-01 | [مؤكدة] Meta pricing |
| Free Entry Point (CTWA) | لحد 7 أيام | [مؤكدة] |
| Meta Business Agent | $2/1M token (~4–5 سنت/رسالة) من 2026-07-01 | [مؤكدة] |
| حد الإرسال | 250 مستخدم/24 ساعة → 2,000 بعد BV | [مؤكدة] |
| Embedded Signup onboarding | 10 عملاء/7 أيام → 200 بعد التحقق | [مؤكدة] |
| Coexistence throughput | 20 mps | [مؤكدة] (دعمه في السعودية [غير مؤكدة]) |
| Cloud API يوقف Marketing | الحسابات الجديدة 2027-04-13، الكل 2027-10-13 | [مؤكدة] |
| Webhooks Meta retries | لحد 7 أيام، ومكررة | [مؤكدة] |
| تكلفة Meta لكل طلب مسترجع | ~4–12 ريال (تسلسل 3 رسائل، استرجاع 5–15%) | [افتراض] |
| تكلفة Meta لمتجر 1,000 سلة × 2 رسالة | ~320 ريال/شهر | [افتراض] |

### 7.3 سلة تقنيًا
| الرقم | القيمة | المصدر |
|---|---|---|
| Access token / Refresh token | 14 يوم / شهر، الـrefresh مرة واحدة (إعادة الاستخدام = إعادة تثبيت) | [مؤكدة] docs.salla.dev |
| Rate limit | Plus 120، Pro 360، Special 720 req/min. Customers 500/10 دقايق | [مؤكدة] |
| Webhook retries | 3 محاولات، ~5 دقايق، timeout ~30 ث، HMAC-SHA256 `X-Salla-Signature` | [مؤكدة] |
| Zid rate limit | 60 req/min لكل تطبيق لكل متجر | [مؤكدة] |
| Deprecations | مُهَل أسبوعين–17 يوم | [مؤكدة] changelog |

### 7.4 AI وLLM
| الرقم | القيمة | المصدر |
|---|---|---|
| Haiku 5.5 | $0.10 / $0.50 لكل 1M (cache read $0.01) | [مؤكدة] platform.claude.com |
| Sonnet 5.5 | $2 / $10 (cache read $0.10) | [مؤكدة] |
| GPT-6 Luna | $0.10 / $0.50 | [مؤكدة] OpenAI pricing |
| Gemini 3.8 Flash | $0.75/$3.75 لحد 2026-12-31، ثم $1.50/$7.50 | [مؤكدة] |
| تكلفة محادثة بيع (8 رسائل) | ~$0.005 Haiku/Luna، ~$0.088 Sonnet | [افتراض] |
| **سقف تكلفة المحادثة (Exit criterion)** | **≤$0.03** | [اقتراح] |
| AI COGS/tenant/شهر (routing 80/20) | Light $9، Medium $45، Heavy $180. نطاق التخطيط $5–15 / $15–60 / $50–250 | [افتراض] |
| Golden set | ≥300 حالة سعودي، دقة سعر 100% | [اقتراح] |
| Handoff rate المستهدف | ≤40% | [اقتراح] |

### 7.5 القياس والـRecovery
| الرقم | القيمة | المصدر |
|---|---|---|
| Holdout الافتراضي | 10% على مستوى العميل، hash ثابت | [اقتراح] |
| أول رسالة | ≥60 دقيقة بعد ترك السلة | [اقتراح] مبني على [مؤكدة] Li et al. 2021 |
| Quiet hours افتراضيًا | 22:00–09:00 (رمضان 01:00–12:00) | [مؤكدة] CST كنص؛ [غير مؤكدة] انطباقه على WhatsApp |
| Opt-out | تنفيذ <24 ساعة | [مؤكدة] CST + Meta + PDPL |
| Power | 5%→7%: ~4,400 (50/50)، 6,300 (80/20)، 10,700 (90/10). متجر 500 سلة/شهر ≈ سنة بـ80/20 | [افتراض] حسابات |
| CUPED | ~50% تقليل تباين (Bing) | [مؤكدة]؛ أثره على السلات [غير مؤكدة] |
| فئات الهامش لكل تاجر | 5–10 فئات | [اقتراح] |

### 7.6 الامتثال والبنية
| الرقم | القيمة | المصدر |
|---|---|---|
| غرامة PDPL | لحد 5 مليون ريال/مخالفة، تتضاعف عند التكرار | [مؤكدة] |
| استطلاع تعديلات PDPL | 6 أكتوبر–5 نوفمبر 2026، النفاذ بعد 60 يوم من النشر | [مؤكدة] |
| إخطار الاختراق | 72 ساعة لـSDAIA (مسودة)؛ 3 أيام للمستهلك والوزارة (نظام التجارة الإلكترونية)؛ 24 ساعة للتاجر (عقدنا) | [مؤكدة]/[اقتراح] |
| فرق السعر Dammam مقابل EU | +24–45% compute، ~$80–200/شهر لـMVP صغير | [مؤكدة، ثانوية] |
| Azure Saudi East / AWS Saudi | نوفمبر / ديسمبر 2026 | [مؤكدة] |
| ZATCA Wave 25 | 2027-02-01 (للمقيمين فوق 187,500 ريال) | [مؤكدة] |
| كيان سعودي | ~40–60 ألف ريال | [افتراض، تقدير مورّد] |
| البنية التحتية MVP | $150–400/شهر | [افتراض] |

### 7.7 أهداف التنفيذ (Roadmap 30/90/180/365/730 يوم من بداية P0)
| النقطة | العملاء | MRR (ريال) | أخرى | التصنيف |
|---|---|---|---|---|
| 30 يوم | 0 live، ≥10 مقابلات، ≥3 LOIs | 0 | BV مقدّمة، طلب Dammam مقدّم، مستشار قانوني متعاقد | [اقتراح] |
| 90 يوم | ≥5 Proof Pilots مدفوعة، 2–4 live | 0–2,000 (+7,500 pilot fees مقدم) | P0 مقفولة، P1 في النص | [افتراض] |
| 6 شهور | 6–10 بيدفعوا | 2,500–5,000 | P1 مقفولة، أول pooled estimate | [افتراض] |
| 12 شهر | 30–60 بيدفعوا | 15,000–35,000 | P3 مقفولة، Listing منشور، بداية P4 | [افتراض] |
| 24 شهر | 120–250 بيدفعوا | 60,000–150,000 | P4 مقفولة، Zid ≥15% من الإيراد، P5 بدأت | [افتراض] |
| Runway لحد نهاية P3 | — | — | **300–500 ألف ريال** (~9–11 شهر) | [افتراض] |

---

## 8. قرارات المعمارية الرئيسية

| القرار | الحكم | السبب |
|---|---|---|
| Backend | **Laravel 13 Modular Monolith**، domain folders (مش nwidart) + Pest arch tests لفرض الحدود | Rule 6. [مؤكدة] حزم الـmodules مش بتفرض حدود |
| Frontend | **Inertia 3 + React 19 + TS + Tailwind 4 + shadcn/ui** (React starter kit). **Next.js مرفوض للتطبيق**، ومسموح بس لموقع التسويق لاحقًا لو احتجناه | [مؤكدة] Next المنفصل = Sanctum SPA + CORS + deploy تاني. RTL/LTR بـlogical properties إلزامي |
| DB | **PostgreSQL + pgvector** | [مؤكدة] Laravel vector search محتاج pgvector. وقت تعلّم للمؤسس (خبرته MySQL) محسوب في P1 |
| Queues | Redis + Horizon. الـdelays في جدول `scheduled_actions` + Scheduler كل دقيقة بـ`SKIP LOCKED` وإعادة فحص الشروط وقت التنفيذ | [اقتراح] إلغاء وaudit وportability |
| Idempotency | جدول `webhook_events` بـunique (provider, event_id) + idempotency keys للإرسال | [مؤكدة] تكرار Meta لحد 7 أيام، وسلة 3 retries |
| Tenancy | **DB واحدة + `tenant_id` + Global Scope + isolation tests إجبارية في CI + Jobs بتحمل `tenant_id` صريح.** RLS طبقة دفاع تانية بعد اختيار الاستضافة. **ممنوع** schema/DB-per-tenant | [مؤكدة] RLS في stancl v4 experimental وFK cascades بتتخطاه |
| WhatsApp tenancy | WABA + business portfolio لكل تاجر | [مؤكدة] الحدود والجودة على مستوى الـportfolio |
| Hosting | **نقدّم على GCP Dammam (Invoiced Billing/CNTXT) في أول أسبوعين من P0.** لو مش متاح قبل نهاية P0: الـpilot في **Frankfurt** بتصميم portable (Docker، Postgres standard، S3-compatible) وترحيل قبل نهاية P3. **ممنوع** AWS UAE/Bahrain. Backup في region تانية | [مؤكدة] مسودة Art. 23، [مؤكدة] Dammam عن طريق CNTXT، [مؤكدة] ضربات AWS الخليج 2026 |
| WhatsApp data region | يتحدد **قبل أول رقم** (ADR-006). السعودية مش متاحة (AE/BH/ZA) | [مؤكدة] القرار irreversible |
| AI orchestration | **Agent واحد + tools** بـ**Laravel AI SDK v1** ورا interface داخلي `AiGateway`. Approvable tools لأي خصم أو كوبون. الـrecovery = workflows حتمية مش agent | [مؤكدة] SDK رسمي v1.3. [مؤكدة] نصيحة Anthropic بالـAPIs المباشرة |
| Python / FastAPI / LangGraph | **لأ في الـMVP.** يتراجع بس في P4+ لو احتجنا batch statistics/uplift models أو Voice في P6، كـworker منفصل بـADR | Rule 6. الـflows خطية |
| Models | افتراضي رخيص (Haiku 5.5 أو GPT-6 Luna) + تصعيد لـSonnet 5.5 بقواعد. الاختيار النهائي بعد eval داخلي | [مؤكدة] الأسعار بتتغير كل 1–2 شهر |
| Anti-hallucination | السعر والمخزون من tool الكتالوج الحي، placeholders بيعوّضها الكود، validator حتمي، الخصم من محرك سياسات بالكود بس | [اقتراح] Rule 9 |
| PII و LLM | pseudonymization (الـLLM مبيشوفش الاسم ولا الرقم) + عقود zero-retention/no-training | [افتراض قانوني] بيقلل خطر النقل عبر الحدود |
| Search/RAG | Hybrid: Postgres FTS/trigram + pgvector، normalization عربي، embeddings عبر API | [مؤكدة] BGE-M3/e5 الأفضل عربي؛ التكلفة مهملة |
| Observability | Sentry + Nightwatch بـPII redaction إجباري. OpenTelemetry/Grafana يتأجلوا | [مؤكدة] الداتا بتروح US/EU |
| Feature flags / entitlements | Laravel Pennant scoped على Team، ومنفصل عن plan entitlements | [اقتراح] |
| Admin | Filament panel داخلي | [اقتراح] |

### 8.1 قائمة الـADRs المقترحة (`docs/adr/`)

| ADR | العنوان | لازم يتعتمد قبل |
|---|---|---|
| ADR-001 | Modular Monolith وحدود الـModules (M01..M12 للـMVP) | P0b |
| ADR-002 | Frontend: Inertia 3 + React بدل Next.js | P1 |
| ADR-003 | PostgreSQL + pgvector | P1 |
| ADR-004 | Tenancy model (shared DB + tenant_id، RLS لاحقًا) | P1 |
| ADR-005 | Hosting & data residency (Dammam أولًا، Frankfurt fallback، خطة ترحيل) | P1 |
| ADR-006 | WhatsApp onboarding: Tech Provider، رسوم Meta على كارت التاجر، MM API، data localization region | أول رقم |
| ADR-007 | AI orchestration: Laravel AI SDK + AiGateway، من غير Python/LangGraph | P2 |
| ADR-008 | PII separation وسياسة بيانات الـLLM | P1 |
| ADR-009 | Cross-merchant pooled data policy (نتائج سلة فقط) | P1 |
| ADR-010 | Workflow execution: `scheduled_actions`، idempotency، `webhook_events` | P1 |
| ADR-011 | Commerce Adapter ونموذج البيانات الداخلي | P0b |
| ADR-012 | Billing & payments: يدوي → Moyasar/Tap → Salla billing | P3 |
| ADR-013 | Measurement methodology: holdout، hash assignment، pooled estimates، win probability | P1 |
| ADR-014 | Discount policy engine & approvals | P1 |
| ADR-015 | Observability وPII redaction | P1 |
| BDR-001 | الكيان القانوني والضريبة (Business Decision Record) | قبل أول فاتورة |

---

## 9. ترتيب التكاملات

| # | التكامل | Phase | السبب |
|---|---|---|---|
| 1 | **Salla private app** | P0b spikes → P1 | قرار المؤسس، ~5:1 على زد في عدد المتاجر المعلن، ومش بنستنى مراجعة المتجر |
| 2 | **WhatsApp Cloud API + MM API** (Tech Provider) | BV في P0 → P1 | على المسار الحرج. MM API إجباري قبل 2027-04-13 للحسابات الجديدة |
| 3 | **Moyasar / Tap** (فوترتنا) | روابط في P1 → tokenization في P3 | Stripe مش متاح في السعودية [مؤكدة] |
| 4 | **Salla App Store public + billing** | P3 | التوزيع. Data-handling review أشد علينا (قنوات تواصل + AI) [مؤكدة] |
| 5 | **حالات طلب سلة لـCOD** | P2 (لو P0 أكدت) وإلا P4 | Karam عنده [مؤكدة]. غالبًا Utility أرخص [افتراض: التصنيف لازم يتأكد] |
| 6 | **Zid** | P4 | تحوّط ضد سلة. StoreLeads بيعد متاجر زد live في السعودية أكتر من سلة [مؤكدة، متناقضة] |
| 7 | **Instagram DM / Messenger** | P5 | بس لو المقابلات أكدت الطلب |
| 8 | **Shopify / WooCommerce** | P5 | لبراندات D2C الأكبر لو اتأكد وجودهم |
| 9 | **Outbound webhooks / REST API / n8n** | P5 | للوكالات والتكاملات المخصصة |
| 10 | **Shipping / ERP / Accounting** | P6 | Ops automation |
| 11 | **Voice providers** | P6 gated | تكلفة وقوانين |

---

## 10. أكبر 12 خطر

| # | الخطر | الاحتمال / الأثر | التخفيف |
|---|---|---|---|
| 1 | Meta تصنّف CODEX كـAI Provider (القسم 4.7) | [غير مؤكدة] منخفض–متوسط / قاتل | رأي قانوني + سؤال مكتوب لـMeta في P0، positioning كـcommerce platform، agent محصور في الكتالوج + handoff، والـrecovery شغال من غير AI (P1 أصلًا من غير agent) |
| 2 | التاجر مش هيدفع مقابل رقم أصغر وأصدق | [افتراض] متوسط–عالي / قاتل للـpositioning | Proof Pilot مدفوع في P0، واختبار زاوية "التوفير" بالتوازي |
| 3 | سلة تطلق AI agent أو holdout native، أو تغيّر سياسة التطبيقات | [افتراض] متوسط–عالي في 12–24 شهر / عالي | Commerce Adapter، زد في P4، بيانات مجمّعة، vertical depth |
| 4 | Karam ينسخ الـholdout والـmargin logic | [افتراض] عالي / متوسط | السرعة، benchmarks قطاعية، منهجية منشورة، سمعة |
| 5 | PDPL: تخزين محلي، تسويق من غير consent، تحوّلنا لـController بسبب الـpooling | [مؤكدة: المسودة والغرامة] عالي / عالي | consent ledger، quiet hours، PII خارج الـLLM، hosting portable، DPA/DPIA، ADR-009 |
| 6 | Statistical power ضعيف للمتجر الصغير | [افتراض] عالي / متوسط | ICP ≥300 طلب، pooled priors، CUPED، نطاقات وwin probability |
| 7 | الـconsent وحدود Meta (131049) بيقللوا السلات المؤهلة | [افتراض] عالي / متوسط | قياس فعلي في Cart Truth Audit وP1، تسعير على السلات المؤهلة، opt-in عند الـcheckout |
| 8 | هلوسة السعر أو الخصم | متوسط / عالي | validator حتمي، Assisted mode ≥4 أسابيع، دقة 100% كـexit criterion |
| 9 | عمولة سلة ~30% وقيود usage billing وبطء المراجعة | [افتراض/مؤكدة جزئيًا] عالي / متوسط | فصل رسوم Meta، طلب الشروط الرسمية في P0، قناة مباشرة للـpilots والسنوي |
| 10 | زيادات رسوم Meta (+27% في 7 شهور، والـService بقت بفلوس) | [مؤكدة كاتجاه] عالي / متوسط | pass-through، والـsuppression نفسه بيبقى قيمة بيعية |
| 11 | Founder bandwidth والـrunway | [افتراض] عالي / عالي | Gates صارمة، أول تعيين Laravel dev في P1، مفيش Module برّه النطاق |
| 12 | هشاشة تقنية للمنصات (refresh token أحادي، 3 retries، deprecations بأسبوعين، كارت دولي لـMeta) | عالي / متوسط | lock، reconciliation، `webhook_events`، متابعة changelog أسبوعية، سؤال الكارت في المقابلات + Multi-Partner كبديل |

---

## 11. القرارات المطلوبة من المؤسس (مع توصيتنا)

| # | القرار | توصيتنا |
|---|---|---|
| 1 | اعتماد P0 قبل أي كود منتج، بميزانية 25–50 ألف ريال وKill criteria مكتوبة | **موافقة** |
| 2 | إعادة صياغة الميزة لـ"Profit-Proven Recovery" وشيل "Autonomous" | **موافقة** |
| 3 | تقليص الـMVP حسب القسم 4 (inbox lite، admin وbilling يدوي، الـagent في P2 Assisted أولًا) | **موافقة** |
| 4 | الـvertical الأول | **عطور + Beauty + Personal Care**، والـFashion في المقابلات بس |
| 5 | holdout 10% إجباري في الـpilots | **إجباري** |
| 6 | الكيان القانوني (سعودي / إماراتي / من برّه بممثل ضريبي) | **قرار بعد الرأي القانوني في P0، وقبل أول فاتورة.** الميل لكيان سعودي لو الـpilots نجحت (Dammam وMoyasar وثقة التاجر) |
| 7 | مسار رسوم Meta | **Tech Provider + كارت التاجر**، وMulti-Partner مع Solution Partner كخطة بديلة |
| 8 | الاستضافة | **تقديم Dammam فورًا، وFrankfurt مؤقت لو اتأخر، وترحيل قبل نهاية P3** |
| 9 | الـStack: شيل Next.js وPython/LangGraph، واعتماد Inertia وLaravel AI SDK | **موافقة** (ADR-002، ADR-007) |
| 10 | سياسة البيانات المجمّعة (نتائج سلة فقط) | **موافقة** كـADR-009 + بند DPA |
| 11 | الأسعار المرجعية 399/699/999 + Proof Pilot 1,500 | **موافقة كفرضية للاختبار** |
| 12 | COD في P2 ولا P4 | **بعد نتيجة P0** |
| 13 | أول تعيين | **Laravel full-stack full-time مع بداية P1**، والمؤسس في البيع والـpilots |
| 14 | ميزانية قانونية 15–30 ألف في P0 | **موافقة** |
| 15 | مين بيبيع في P0–P1 | **المؤسس بنفسه، إلزامي** |

---

## 12. التقسيم الموصى بيه وأول Phase

### 12.1 التقسيم الكلي
7 Phases (P0..P6) بتغطي **كل** الرؤية:
- **P0–P3 = CODEX Commerce MVP** (اكتشاف ← استرجاع مُقاس ← بيع بالمحادثة ← إطلاق وتسييل). M01–M12 بس.
- **P4 = تعميق وتنويع** (Zid، Profit Engine v2، CRM Lite) = M13 + توسيع M08/M09/M11.
- **P5 = قنوات وشركاء وخليج** = M14 (NL)، M15، M18 (API/n8n)، M20 (agency dashboard)، قنوات M03.
- **P6 = Revenue OS** = M14 (Analyst)، M16، M17، M18 (ERP/shipping/accounting)، M19 Voice (gated)، M20 White label (gated)، M21 Enterprise، مصر.
- **قاعدة ثابتة:** مفيش Module من M13 لـM21 بيتبني قبل P3 exit. وكل Module في P6 له gate بـ≥5–10 عملاء ملتزمين يدفعوا.

### 12.2 أول Phase: **P0 Discovery & Platform Gates**، تبدأ الأسبوع ده
**ليه:**
1. أكبر 3 مخاطر قاتلة **مش تقنية**: تصنيف Meta، استعداد التاجر يدفع للصراحة، شروط سلة. ولا سطر كود بيحلهم (Rules 1، 10، 12).
2. مسارات طويلة على المسار الحرج ومش في إيدنا: Meta BV وApp Review، Salla Partner والـprivate app، Dammam Invoiced Billing، الرأي القانوني، الكيان.
3. قرارات irreversible لازم تتاخد قبل البناء: WhatsApp localization region، الكيان، الاستضافة.
4. لائحة PDPL النهائية (الاستطلاع بيقفل 5 نوفمبر 2026) هتتوضح جوه نافذة P0.
5. لو P0 فشلت، الخسارة ≤50 ألف ريال وأسابيع، مش 6 شهور بناء.

**P0a (أسبوع 1–3، من غير كود):** تقديم Meta BV، فتح Salla Partners + demo store، طلب GCP Invoiced Billing، التعاقد مع مستشار قانوني، سكريبت مقابلات (الألم، السعر، الـholdout، COD، كارت Meta، عدد السلات)، قايمة 60 متجر مستهدف، أول 10 مقابلات، عرض Proof Pilot في صفحة واحدة.
**P0b (أسبوع 3–7، بعد اعتماد الـBlueprint):** spikes على demo store (`checkout_url`/`pending_payment`، webhooks، token refresh، template على MM API)، Cart Truth Audit read-only (2–3 أسابيع، E17) كـlead magnet ومصدر بيانات حقيقية، ADR-001..011، تحصيل الـdeposits، قرار الـwedge.

---

## 13. التعارضات اللي اتحسمت بين المقترحات

| التعارض | A | B | C | الفايز والسبب |
|---|---|---|---|---|
| توقيت الـAI agent | Copilot في P1 | بعد الـrecovery (P2) | P2، Assisted أولًا | **C/B.** P1 من غير agent بيقلل خطر Meta 4.7 وبيخلّي الـrecovery خطة بديلة. أخدنا من A فكرة Copilot كـ"Assisted mode" في P2 |
| Shared Inbox | شبه مقطوع | Shrink قوي | Shrink | **B.** Coexistence + handoff queue/assign/notes/status. القطع الكامل بيكسر شرط Meta للـhuman handoff |
| الـVertical | عطور + Beauty + Fashion | عطور + Beauty + Personal Care، Fashion بعدين | الأربعة | **B.** عمق رأسي أقوى ضد الـagent العام، والـFashion بيعقّد الـagent (مقاسات ومرتجعات). Fashion يفضل في المقابلات |
| Gate الـdesign partners | ≥5 دفعوا مقدم | ≥8 التزام أو deposit | ≥8 موقعين بـ≥50% | **دمج:** ≥8 LOI و≥5 دفعوا. ≥8 مدفوعين صعب في 5–7 أسابيع، و5 هو أقل عدد يطلّع pooled estimate مفيد |
| شكل الـpilot | 1,500 ريال مقدم لـ90 يوم | خصم 50% + Proof Month (مش موجب = مفيش دفع) | خصم 50% 3–6 شهور | **A + B:** دفع مقدم (التزام حقيقي، Rule 10) + ضمان B (الشهر التالت مجاني لو التقدير مش موجب) |
| هيكل الأسعار | 499/899 | 399/699/999 | 399/699/899+ | **B.** 399 قريب من سقف Karam الأدنى، و699 عند سقفه الأعلى مع agent مدمج |
| التقرير في P1 | يدوي (SQL + Sheet) | منتج من الأول | منتج أسبوعي | **A ثم C:** يدوي أول 4 أسابيع (نتعلم إيه اللي التاجر بيقراه)، وبعدين صفحة واحدة في المنتج قبل نهاية P1 |
| Cart Truth Audit | — | في P0 كـlead magnet | — | **B، بشرط:** في P0b بعد اعتماد الـBlueprint (Rule 1)، read-only، من غير WhatsApp ولا LLM |
| الاستضافة | EU للـpilot ثم Dammam قبل P2 | Dammam لو متاح وإلا Frankfurt | تقديم Dammam فورًا، fallback Frankfurt، ترحيل قبل P3 | **C.** أكثر واقعية لـInvoiced Billing والـPDPL، ومحدد الموعد |
| الميزانية والفريق | P1 25–50 ألف، dev part-time | P1 90–150 ألف | burn 25–45 ألف/شهر | **C.** dev full-time في P1 ضروري لـbandwidth المؤسس (B)، والـburn الشهري أوضح للـrunway. A أقل من الواقع |
| Admin | Filament + أدوات | لوحة بسيطة | super admin داخلي | **A.** Filament أرخص طريق لنفس نطاق C |
| Golden set | — | ≥200 | ≥300 | **C.** دقة السعر صفر تسامح، والعينة الأكبر أأمن |
| Handoff rate | — | ≥40% محلول من غير إنسان | <35% | **وسط: ≤40%.** مفيش benchmark عربي، و35% طموح في أول نسخة |
| P1 volume gate | — | ≥5 متاجر 60 يوم | ≥5,000 سلة | **≥4,000 سلة + ≥6 متاجر.** 6 × ~400 × شهرين ≈ 4,800، و5,000 كان على الحافة |
| لهجة الـagent | سعودي + مصري | سعودي بس | سعودي | **C/B للرد** (سعودي)، مع فهم المصري. golden set مصري في P6 مع دخول مصر |
| Zid | P4 (أو P3) | P4 | P4 | **P4** بالإجماع |
| Outcome fee | بعدين، اختياري | P4 | P4 | **P4**، بعد pooled estimate موثوق |
| White label | مرفوض دلوقتي | مرفوض 24 شهر | مرفوض 24 شهر | **مرفوض لحد شهر 24، وبعدها P6 gated** |
| عدد الـPhases | P0–P5 | P0–P6 | P0–P6 | **C (P0–P6)** بتسمية مدموجة: P3 = Public Launch (B/C)، وP4 فيه Zid + CRM lite عشان كل Modules البرومبت تلاقي مكان |
