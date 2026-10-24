# 1. Executive Summary

> الحالة: مسودة v1.0 — بانتظار موافقة المؤسس (2026-10-21)

> **مفتاح التصنيف:** [مؤكدة] = عليها مصدر (رابط + تاريخ اطلاع **2026-10-21**). [افتراض] = محتاجة اختبار مع العملاء أو بالبيانات. [اقتراح] = توصية مننا. **غير مؤكدة** = ملقيناش دليل في أي اتجاه، ومش معناها "غير موجودة".
> القسم ده ملخّص للأقسام 2–14 زي ما هي مكتوبة. لو قسم اختلف مع الـDecision Spine، الملخّص ماشي على القسم وبيذكر الخلاف. **Rule 1:** ده تخطيط بس، ومفيش كود منتج قبل اعتماد الـBlueprint.

---

## 1.1 الخلاصة في 8 سطور

1. **CODEX Commerce** هو أول منتج من **CODEX Revenue OS**: تطبيق على سلة بيشتغل على WhatsApp الرسمي، وبيسترجع السلات المتروكة وبيقيس **الربح الإضافي الحقيقي** بمجموعة تحكم (holdout). بعد كده بيضيف AI بيبيع جوه المحادثة.
2. **الحكم:** الفكرة زي ما هي مكتوبة في البرومبت **ضعيفة تجاريًا**. لو ضيّقناها وغيّرنا زاويتها تبقى **متوسطة لقوية، وقابلة للاختبار بتكلفة قليلة** ([القسم 2](02-critical-assessment.md)).
3. أغلب الـMVP الأصلي **موجود فعلًا** في السوق: Karam AI بيقدّمه بـ268–698 ريال/شهر [مؤكدة: https://karamai.co/en/pricing، 2026-10-21]، وسلة عندها تذكير سلات native [مؤكدة: https://help.salla.sa/en/article/abandoned-cart-reminders-with-a-discount/vzit7tzwz9e5pcv37nnenyqv، 2026-10-21].
4. الميزة التنافسية اتعاد تسميتها لـ**Profit-Proven Recovery**، واتشالت منها كلمة "Autonomous". التمايز في **holdout إجباري + Incremental Profit Report + خصم محكوم بهامش الفئة**، وكل ده **قابل للنسخ** [افتراض].
5. **أخطر افتراض في المشروع:** التاجر هيدفع مقابل رقم **أصغر وأصدق** [افتراض]. محدش اختبره، وهنختبره بفلوس حقيقية قبل أي كود منتج.
6. **7 مراحل (P0..P6).** الـMVP = اللي بيتسلّم في P1 + P2، وبيتحسب "اتطلق واتسيّل" عند نهاية P3. مفيش Module من M13 لـM21 بيتبني قبل كده.
7. **الفلوس:** P0 بتكلف 25–50 ألف ريال. والوصول لنهاية P3 بيكلف **300–525 ألف** صافي [افتراض]، من غير راتب المؤسس. وبالـburn المكتوب لـP4 وP5، **مفيش break-even في أول 24 شهر** [افتراض/حساب، [القسم 11](11-budget-timeline.md)].
8. **التوصية:** نبدأ **P0 Discovery & Platform Gates** الأسبوع ده (5–7 أسابيع). وقاعدة القتل واضحة: لو أقل من 5 تجار دفعوا Proof Pilot مقدمًا، منبنيش.

---

## 1.2 إيه هو CODEX Commerce، وإيه اللي هو مش هو

| هو | مش هو |
|---|---|
| منصة **Profit Recovery لمتاجر سلة** على WhatsApp الرسمي فقط (Rule 8) | "AI Chatbot" أو "AI Agent" كهوية للمنتج. السبب خطر تصنيف Meta كـAI Provider (البند 4.7) [مؤكدة: الشرط، https://www.facebook.com/legal/Meta-Terms-for-WhatsApp-Business-Platform، 2026-10-21؛ **غير مؤكدة**: إنه هيتطبق علينا] |
| استرجاع سلات بقواعد حتمية (توقيت، consent، quiet hours، مفيش خصم في أول رسالة)، ومعاه holdout 10% | Omnichannel Inbox، أو Workflow builder، أو Marketing Studio. دي **Parity** عند المنافسين ومتأجلة لـP5–P6 |
| تقرير واحد فيه 4 أرقام: الإيراد المنسوب، والإيراد الإضافي التقديري كنطاق، والتكلفة، والربح الإضافي التقديري | وعد بـ"رقم ربحك الدقيق من أول شهر". ده مستحيل إحصائيًا للمتجر المتوسط [افتراض: حسابات power] |
| AI Sales Agent محصور في الكتالوج **من P2**. بيبدأ Assisted لمدة ≥4 أسابيع، وبيحوّل لإنسان لما يلزم | AI بيقرر خصومات أو معاملات مالية أو حملات أو ميزانيات. ده **ممنوع للأبد** (Rule 9) |
| "Revenue OS" كرؤية طويلة (P6) | "Revenue OS" كرسالة بيع النهارده |

---

## 1.3 الحكم النقدي (من [القسم 2](02-critical-assessment.md))

| البُعد | التقييم | السبب المختصر |
|---|---|---|
| حجم الألم | متوسط [افتراض] | التذكير نفسه شبه ببلاش. الألم المحتمل هو **تكلفة** الاسترجاع ومحدش عارف عائده |
| الاستعداد للدفع | **مجهول** [افتراض] | مفيش دليل إن تاجر سعودي دفع قبل كده مقابل قياس incrementality |
| السوق القابل للخدمة | محدود [افتراض] | SAM حوالي 2,000–5,700 متجر على سلة وزد، والهدف الواقعي 50–150 متجر بيدفع في 18 شهر ([market-and-pricing.md](../research/market-and-pricing.md)). يكفي شركة صغيرة مربحة |
| المنافسة | عالية [مؤكدة: الأسماء والأسعار في [القسم 3](03-competitor-analysis.md)] | سلة native، وZid WhatsApp، وKaram، وATMET، وMeta Business Agent |
| الاعتماد على منصات | عالي جدًا [مؤكدة: الشروط] | Meta (التسعير والبند 4.7)، وسلة (من حقها توقف أي تطبيق [مؤكدة: https://partners.salla.com/legal/apps-policy، 2026-10-21])، والمنظّم (مسودة بتلزم بتخزين البيانات جوه المملكة [مؤكدة كمسودة: https://www.clydeco.com/en/insights/2026/10/sdaia-publishes-draft-amendments-to-the-pdpl، 2026-10-21]) |
| ملاءمة الفريق | ضعيفة للرؤية الكاملة، وجيدة للنسخة المضيّقة [افتراض] | الـ13 Module محتاجين شركة فيها 50 شخص، مش مؤسس بيكتب الكود (Rules 5 و12) |
| تكلفة الاختبار | منخفضة [اقتراح] | لو P0 فشلت، الخسارة ≤50 ألف ريال وأسابيع، مش 6 شهور بناء |

---

## 1.4 المشكلة والـICP

**المشكلة [افتراض]:** متاجر سلة المتوسطة بتدفع كل شهر رسوم واتساب وخصومات عشان تسترجع مبيعات، ونسبة كبيرة من المبيعات دي كانت هتحصل كده كده. ومفيش أداة بتقولهم بصراحة: الرسالة دي تستاهل ولا لأ، والخصم ده بيكسّبهم ولا بيخسّرهم.

**الأدلة:**
- تذكير بعد 30–60 دقيقة **قلّل** الشراء مقارنةً بمجموعة التحكم [مؤكدة: Li et al. 2021، https://ximarketing.ai/class/papers/retargeting.pdf، 2026-10-21].
- تذكير SMS من غير خصم مكانش له أثر معنوي (1.21% مقابل 1.41%) [مؤكدة: https://www.msi.org/wp-content/uploads/2021/09/MSI_Report_21-133-1.pdf، 2026-10-21].
- كل الدراسات دي من برّه الخليج.

| البُعد | الـICP [اقتراح] |
|---|---|
| المنصة والقطاع | سلة فقط لحد P4. القطاعات: **عطور وBeauty وPersonal Care**. الـFashion بيدخل في المقابلات بس |
| الحجم | 300–3,000 طلب/شهر، و**≥400 سلة مؤهلة (فيها موبايل) في الشهر** |
| مستبعد (Anti-ICP) | المتاجر اللي أقل من 300 طلب، والـenterprise، وأي حد رافض الـholdout أو عايز الـAI يقرر الخصم ([القسم 4](04-positioning-differentiation.md) §4.4.2) |

---

## 1.5 واقع المنافسة والتمايز الصادق

| المنافس | التهديد | المهم عنه |
|---|---|---|
| **Karam AI** | الأخطر بشكل مباشر [افتراض] | سعودي، على سلة وزد، وعنده agent وinbox وCOD وjourneys وAutopilot بـ268–698 ريال [مؤكدة: https://karamai.co/en/pricing، 2026-10-21]. القياس عنده "orders after a chat"، مع إن دليلهم نفسه بيعترف إن مش كل طلب سببه الرسالة [مؤكدة: https://karamai.co/en/guides/abandoned-carts، 2026-10-21] |
| **سلة نفسها** | الأخطر بشكل غير مباشر [افتراض] | عندها تذكير native بخصم ولحد 5 تذكيرات [مؤكدة: help.salla.sa، الرابط في 1.1]. واحتمال إنها تطلق AI agent native خلال 12–24 شهر متوسط لعالي [افتراض] |
| **Meta Business Agent** | بيحوّل "AI بيرد بالعربي" لسلعة | سعره $2 لكل 1M token من 2026-07-01 [مؤكدة: https://developers.facebook.com/docs/whatsapp/pricing، 2026-10-21] |
| تطبيقات رخيصة | بتعمل سقف نفسي للسعر | 49–119 ريال، ومنها ATMET بـAI سعودي بـ49/99/249 [مؤكدة: https://apps.salla.sa/en/app/931067979، 2026-10-21] |
| Kasbly وMersal وThikaa | متوسط لمنخفض [افتراض] | Kasbly عنده shadow mode ومستويات استقلالية وسقف خصم [مؤكدة: https://docs.kasbly.com/ar/ai-agent/overview/، 2026-10-21]، يعني الموافقات **Parity** مش ميزة |

**التمايز الصادق:**
- **مش ابتكار عالميًا:** الـholdout موجود عند Klaviyo بشرط 400k profile [مؤكدة: https://help.klaviyo.com/hc/en-us/articles/18138290642971، 2026-10-21]، وعند Braze وPostscript. وNano AI بيوعد بتقرير قياس بعد 30 يوم [مؤكدة: https://www.nano-ai.net/en/services/salla-zid-whatsapp-ai، 2026-10-21]. عشان كده جملة "أول من يقيس" ممنوعة.
- **عند المنافسين الأربعة:** الـholdout **غير مؤكد** عندهم كلهم. ده الصف الوحيد في الـfeature matrix اللي فاضي عند الأربعة ([القسم 3](03-competitor-analysis.md) §3.7).
- **الجديد الممكن:** إن الـholdout يبقى default ومفهوم لمتجر 300–3,000 طلب، ومربوط بالهامش والتكلفة [افتراض].
- **الـmoat:** مش في الفكرة. Karam يقدر ينسخ الـholdout في أسابيع [افتراض]. اللي بيحمينا هو الـpooled priors لكل قطاع، والمنهجية المنشورة، والسمعة، والوقت. والـpooled priors بتتحسب من **نتائج طلبات سلة بس**، لأن شروط Meta بتمنع استخدام بيانات WhatsApp لتدريب أو تحسين أي نموذج [مؤكدة: رابط Meta Terms في 1.2] (ADR-009).
- **نقطة ضعف لازم تتقال:** Recover بـ399 من غير AI. ده حوالي **3.4 ضعف** Karam Starter (119)، اللي فيه journey السلة المتروكة [مؤكدة: الأسعار في https://karamai.co/en/pricing؛ النسبة حسابنا]. لو التاجر مش شايف قيمة التقرير، الباقة دي مش هتتباع ([القسم 3](03-competitor-analysis.md) §3.9).

**التسعير [اقتراح، بيتختبر في P0–P1]:**

| البند | السعر |
|---|---|
| Proof Pilot | 1,500 ريال مقدم لـ90 يوم، والـholdout فيه إجباري |
| Recover | 399 |
| Sell & Recover | 699 |
| Pro | 999+ |
| رسوم Meta | **pass-through** على كارت التاجر، من غير markup |

---

## 1.6 نطاق الـMVP في جدول واحد

| الوظيفة (من البرومبت) | القرار | داخل الـMVP | Phase |
|---|---|---|---|
| SaaS Workspace | Shrink | Tenant = Team، ودوران بس (Owner, Agent) | P1 |
| Salla Integration | Keep (private app أولًا) | OAuth، webhooks + reconciliation، `checkout_url`. **إنشاء cart: Cut** (مفيش endpoint موثّق، **غير مؤكدة**) | P0b/P1 → public P3 |
| WhatsApp Integration | Keep | Tech Provider، Embedded Signup، Coexistence، MM API من أول يوم | P1 |
| Abandoned Cart Recovery | **Keep = الـwedge** | holdout 10%، consent، quiet hours، scoring بقواعد، محرك سياسات خصم | P1 |
| Basic Revenue Analytics | Keep + Reframe | = Incremental Profit Report: يدوي أول 4 أسابيع، وبعدين صفحة واحدة | P1 |
| Security & Consent | Keep كامل، من غير تفاوض | consent ledger، opt-out <24 ساعة، `audit_events`، isolation tests، الـPII برّه الـLLM، DPA عربي | P1 |
| Shared Inbox | Shrink قوي | handoff queue + assign + notes + status | P1 lite / P2 |
| Subscription & Usage | Manual | metering من أول يوم، وفوترة يدوية، وSalla billing في P3 | P1→P3 |
| Admin Dashboard | Shrink | Filament داخلي | P1 |
| AI Sales Agent | Shrink + تأجيل | Assisted ≥4 أسابيع، وبعدين Scoped Autonomous على الكتالوج | **P2** |
| COD Confirmation | مشروط | بس لو P0 أكدت إنه الألم الأكبر | P2 أو P4 |

**المجهود:** الـMVP كله ≈ 44–46 person-week بالـbuffer [افتراض، [القسم 6](06-mvp-vs-future-matrix.md) B.2]. P1 لوحدها ≈ 18 person-week، والطاقة المتاحة 15–18. يعني P1 **على الحافة**، وأي إضافة ليها لازم يتشال قصادها حاجة.

---

## 1.7 المراحل

> كل المدد والميزانيات [افتراض]، وكل الـExit Criteria [اقتراح]. التفاصيل في [القسم 7](07-master-development-phases.md) و[القسم 8](08-phase-dependencies.md).

| ID | الاسم | المدة | أهم Exit Criteria (كلها لازم تتحقق) |
|---|---|---|---|
| **P0** | Discovery & Platform Gates | 5–7 أسابيع | ≥20 مقابلة. ≥8 LOI، و**≥5 دفعوا 1,500 ريال مقدم**. ≥60% بيفضّلوا "ربح إضافي صادق". ≥5 متاجر عندها ≥400 سلة مؤهلة. Meta BV مقبولة. رأي قانوني مكتوب. ADR-001..011 معتمدة |
| **P1** | Measured Recovery Pilot (من غير AI agent) | 10–12 أسبوع في الـspine، و**14–18 واقعيًا** (R33) | ≥6 متاجر live لمدة ≥60 يوم. **≥3 من أول 5 جددوا بـ≥399**. ≥4,000 سلة اتعالجت. صفر تسريب cross-tenant. 100% opt-out <24 ساعة |
| **P2** | Conversational Selling MVP | 8–12 أسبوع | دقة السعر والمخزون **100%** على ≥300 حالة سعودي. تكلفة المحادثة ≤$0.03. handoff ≤40%. 10–15 متجر بيدفع |
| **P3** | Public Launch & Monetization | 8–12 أسبوع | Listing منشور على سلة. 30–50 متجر بيدفع. MRR 15–30 ألف. gross margin ≥60%. churn ≤5%/شهر. **= الـMVP اتسيّل** |
| **P4** | Profit Engine v2 + Zid + CRM Lite | 4–6 شهور | 100–150 متجر. ≥15% من الإيراد من زد. NRR ≥100% |
| **P5** | Channels, Partners & GCC | 6–12 شهر | ≥300 متجر. ARR ≥2.5–3 مليون. أول سوق خليجي |
| **P6** | Revenue OS Platform | 12–24 شهر | gate منفصلة لكل Module: ≥5–10 عملاء ملتزمين يدفعوا تمنه |

**Kill / Pivot [اقتراح]:**
- آخر P0: لو أقل من 5 دفعوا، منبنيش.
- لو Meta صنّفتنا AI Provider: نكمّل recovery من غير AI.
- آخر P1: لو أقل من 2 جددوا، أو الأثر الإضافي ≈ صفر، نعمل pivot لـ"توفير التكلفة" أو COD.
- أي Gate تفشل مرتين: وقفة مراجعة كاملة.

---

## 1.8 المعمارية والفريق والميزانية (العناوين)

**المعمارية [اقتراح، [القسم 9](09-technical-architecture.md)]:**
- **التطبيق:** **Laravel 13 Modular Monolith** واحد، والواجهة Inertia + React. **Next.js** و**Python/LangGraph** مرفوضين في الـMVP (Rule 6).
- **البيانات والـtenancy:** **PostgreSQL + pgvector**. DB واحدة بـ`tenant_id`، ومعاها isolation tests إجبارية في CI (Rule 7).
- **التنفيذ في الخلفية:** Redis + Horizon، وجدول `scheduled_actions` هو مصدر الحقيقة لأي جدولة.
- **الـAI:** في P2 بس، عن طريق Agent واحد ورا `AiGateway`. الخصم بيطلع من محرك سياسات مكتوب بالكود، **والـAI عمره ما بيقرره**.
- **الاستضافة:** GCP Dammam، ولو اتأخرت نستخدم Frankfurt مؤقتًا بتصميم portable.
- **التكلفة:** البنية $150–400/شهر [افتراض].

**الفريق [افتراض، [القسم 10](10-team-structure.md)]:**
- **P0:** المؤسس + مستشار قانوني بالساعة.
- **P1:** نضيف Laravel full-stack full-time + مصمم part-time.
- **P2:** نضيف AI/QA part-time + CS سعودي part-time.
- **P3:** نضيف CS full-time.
- **التعيين بيمشي بالـGates، مش بالتقويم.** والمؤسس هو اللي بيبيع في P0–P1، وده إلزامي.

**الميزانية [افتراض، [القسم 11](11-budget-timeline.md)]:**

| البند | الرقم |
|---|---|
| P0 | 25–50 ألف ريال |
| لحد نهاية P3 (P1 = 12 أسبوع) | 300–525 ألف صافي |
| لحد نهاية P3 لو P1 اتمدت | لحد ~590 ألف |
| الكاش المطلوب متأمّن قبل P1 | ~600 ألف (Base) أو ~430 ألف (Lean) |
| لحد شهر 24 بالـburn المكتوب | 1.3–1.8 مليون، ومفيش break-even |
| لحد شهر 24 بـburn ثابت ~75 ألف | احتياج أقصى ~0.95–1.0 مليون، والـbreak-even حوالي شهر 27–28 |

- **راتب المؤسس = صفر** في كل الأرقام. كل 15 ألف ريال/شهر راتب بيحتاج **+41 عميل** عشان نوصل للـbreak-even.
- **التمويل:** القرار بيتاخد عند P3 exit، على أرقام حقيقية.

---

## 1.9 أكبر 5 مخاطر (من [القسم 13](13-risks-mitigation.md))

| ID | الخطر | الاحتمال / الأثر | التخفيف |
|---|---|---|---|
| R01 | التاجر ميدفعش مقابل "ربح إضافي صادق" | [افتراض] عالي / قاتل | Proof Pilot مدفوع في P0، واختبار زاوية "التوفير" بالتوازي |
| R08 | Meta تصنّفنا AI Provider (البند 4.7) | **غير مؤكدة**، متوسط / قاتل | رأي قانوني + سؤال مكتوب لـMeta. الـpositioning كـcommerce platform. P1 من غير agent |
| R13 + R14 | سلة تقفل الفجوة بميزة native، أو تضغط بشروط المتجر (عمولة ~30% [افتراض]) | [افتراض] عالي / كبير | Commerce Adapter، وزد في P4، وبيع الـpilots والسنوي مباشر |
| R16 | تخزين البيانات جوه المملكة ونقلها عبر الحدود | [مؤكدة كمسودة] عالي / كبير | Dammam + تصميم portable. وممنوع نقول "بياناتك كلها في السعودية"، لأن WhatsApp مفيهوش region سعودي [مؤكدة: https://developers.facebook.com/docs/whatsapp/cloud-api/reference/registration، 2026-10-21] |
| R29 | إرهاق المؤسس (كود + بيع + pilots) | [افتراض] عالي / كبير | Gates صارمة، وأول تعيين مع بداية P1، ومفيش Module برّه النطاق |

فيه خطرين "صفر تسامح" لازم يتذكروا هنا: **تسريب بيانات بين الـtenants (R23)**، و**سعر أو خصم غلط من الـAI (R20)**. أي حادثة واحدة منهم = وقف شغل + post-mortem.

---

## 1.10 القرارات المطلوبة من المؤسس دلوقتي

**البوابة A: عشان نبدأ P0a من غير كود** ([القسم 14](14-key-decisions.md) §14.7.3)

| القرار | توصيتنا |
|---|---|
| اعتماد P0 بميزانية 25–50 ألف (منها قانوني 15–30 ألف)، والـKill criteria تبقى مكتوبة (D-B07) | **موافقة** |
| إعادة الصياغة لـProfit-Proven Recovery وشيل "Autonomous" (D-P01) | **موافقة** |
| الـvertical المبدئي: عطور + Beauty + Personal Care (D-B01) | **موافقة** |
| holdout 10% إجباري في الـpilots (D-P05) | **إجباري** |
| أسعار 399/699/999 + Proof Pilot 1,500 كفرضية للاختبار (D-B03، D-B04) | **موافقة كفرضية** |
| تقديم Meta BV كـTech Provider وطلب GCP Dammam فورًا (D-T07، D-T05) | **موافقة** |
| المؤسس بيبيع بنفسه في P0–P1 (D-B09) | **إلزامي** |

**قرارات مفتوحة لازم تتقفل قبل P1** (مش مطلوبة النهارده، بس لازم تبقى قدامك):
- **مدة P1 (R33):** شرط "≥6 متاجر live لمدة ≥60 يوم" مش هيتحقق في 10–12 أسبوع. قدامنا خيارين:
  - (أ) نمدّ P1 لـ14–18 أسبوع. ده بيكلّف +12–62 ألف ريال.
  - (ب) نليّن الشرط لـ"≥3 منهم عدّوا 60 يوم". القسم 11 بيوصي بـ(ب) [اقتراح].
- **ضمان الـProof Pilot:** لأن المبلغ مدفوع مقدم، "الشهر التالت مجاني" عمليًا معناه استرداد أو رصيد ~500 ريال. ولازم شرط "مش موجب" يتعرّف إحصائيًا في العقد (ADR-013).
- **الكيان القانوني (one-way):** أول فاتورة بتطلع **جوه P0** (deposits الـpilots). يعني BDR-001، أو حل مؤقت موثّق، لازم يتقفل حوالي أسبوع 5–6.
- **`data_localization_region` لأرقام WhatsApp (one-way):** لازم يتحدد قبل أول رقم، بعد الرأي القانوني.
- **تعريف gross margin في P3 exit، وراتب المؤسس:** الاتنين بيغيّروا حسابات الـbreak-even.
- **تعارضات رقمية في الـspine محتاجة تتصلح:**
  - تكلفة Meta لمتجر 1,000 سلة: ~390–432 ريال/شهر بسعر Meta المباشر، مش 320 (الـ320 محسوبة على سعر Salla Chat).
  - الـProof Pilot ≈ 72% من Sell & Recover لمدة 90 يوم، مش 50%.

---

## 1.11 أول Phase: P0 Discovery & Platform Gates (تبدأ الأسبوع ده)

| السبب | التفصيل |
|---|---|
| أكبر 3 مخاطر قاتلة **مش تقنية** | الدفع للصراحة (R01)، وMeta 4.7 (R08)، وشروط سلة (R13/R14). ولا سطر كود بيحلهم (Rules 1، 10، 12) |
| مسارات طويلة مش في إيدنا | Meta BV وApp Review، وSalla Partners، وDammam Invoiced Billing، والرأي القانوني. لازم يبدأوا في الأسبوع الأول |
| قرارات one-way قبل البناء | الكيان، وregion بيانات WhatsApp، والاستضافة، ونموذج الـtenancy |
| التوقيت التنظيمي | استطلاع تعديلات PDPL بيقفل 2026-11-05 [مؤكدة: رابط Clyde & Co في 1.3]، يعني الصيغة النهائية ممكن تطلع جوه P0 |
| تكلفة الفشل صغيرة | ≤50 ألف ريال وأسابيع [افتراض] |
| بيانات حقيقية مش آراء | Cart Truth Audit (E17): أداة read-only على سلة، من غير WhatsApp ولا LLM، بتقيس عدد السلات المؤهلة ونسبة الـconsent |

**P0 هتتنفذ على جزئين:**
- **P0a (أسبوع 1–3، من غير كود):** تقديم BV، وفتح Salla Partners وdemo store، وطلب Dammam، والتعاقد مع المستشار، وقايمة 60 متجر، وأول 10 مقابلات.
- **P0b (أسبوع 3–7، بعد اعتماد الـBlueprint):** spikes، وCart Truth Audit، وADRs، وتحصيل الـdeposits، وقرار الـwedge. الخطة أسبوع بأسبوع في [القسم 12](12-first-90-days.md).

---

## 1.12 فهرس الـBlueprint والبحث

| # | القسم | الملف |
|---|---|---|
| 1 | Executive Summary | [01-executive-summary.md](01-executive-summary.md) |
| 2 | Critical Assessment of the Business Idea | [02-critical-assessment.md](02-critical-assessment.md) |
| 3 | Competitor Analysis | [03-competitor-analysis.md](03-competitor-analysis.md) |
| 4 | Product Positioning & Differentiation | [04-positioning-differentiation.md](04-positioning-differentiation.md) |
| 5 | Complete Product Modules Map | [05-product-modules-map.md](05-product-modules-map.md) |
| 6 | MVP vs Future Features Matrix | [06-mvp-vs-future-matrix.md](06-mvp-vs-future-matrix.md) |
| 7 | Master Development Phases | [07-master-development-phases.md](07-master-development-phases.md) |
| 8 | Phase Dependencies | [08-phase-dependencies.md](08-phase-dependencies.md) |
| 9 | Technical Architecture | [09-technical-architecture.md](09-technical-architecture.md) |
| 10 | Team Structure | [10-team-structure.md](10-team-structure.md) |
| 11 | Budget & Timeline | [11-budget-timeline.md](11-budget-timeline.md) |
| 12 | First 90 Days | [12-first-90-days.md](12-first-90-days.md) |
| 13 | Risks & Mitigation | [13-risks-mitigation.md](13-risks-mitigation.md) |
| 14 | Key Decisions | [14-key-decisions.md](14-key-decisions.md) |

| البحث الخام | الملف |
|---|---|
| البرومبت الأصلي | [00-master-prompt.md](../00-master-prompt.md) |
| مصفوفة المنافسين | [competitive-feature-matrix.md](../research/competitive-feature-matrix.md) |
| Karam AI · Mersal AI · Thikaa · Kasbly | [karam-ai.md](../research/karam-ai.md) · [mersal-ai.md](../research/mersal-ai.md) · [thikaa.md](../research/thikaa.md) · [kasbly.md](../research/kasbly.md) |
| السوق والأسعار | [market-and-pricing.md](../research/market-and-pricing.md) |
| منصة سلة | [salla-platform.md](../research/salla-platform.md) |
| WhatsApp Business Platform | [whatsapp-business-platform.md](../research/whatsapp-business-platform.md) |
| الـIncrementality في استرجاع السلات | [cart-recovery-incrementality.md](../research/cart-recovery-incrementality.md) |
| تكلفة الـLLM بالعربي | [ai-llm-costs-arabic.md](../research/ai-llm-costs-arabic.md) |
| الـStack التقني | [tech-stack-options.md](../research/tech-stack-options.md) |
| PDPL والتنظيم السعودي | [saudi-regulatory-pdpl.md](../research/saudi-regulatory-pdpl.md) |

---

## ملاحظات للمراجعة

1. الملخّص ده ماشي على **الأقسام** في كل مكان اختلفت فيه عن الـspine:
   - مدة P1 الواقعية (14–18 أسبوع، R33).
   - الـrunway لحد P3 (300–525 ألف بدل 300–500).
   - M14 بيبدأ P5، والتقرير E07 جوه M09.
   - السنوي في P3.
   - أكبر 5 مخاطر حسب سجل القسم 13 (R01، R08، R13/R14، R16، R29)، مش ترتيب الـspine.
2. الأرقام المتعارضة (تكلفة Meta، ونسبة خصم الـProof Pilot) اتذكرت في 1.10 ومتنقلتش كحقائق. لازم تتصلح في الـspine قبل ما تدخل أي مادة بيع.
