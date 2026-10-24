# WhatsApp Business Platform الرسمية لمورّد SaaS يخدم تجار السعودية

- **تاريخ الاطلاع:** 2026-10-21
- **النطاق:** الوصول الرسمي (Cloud API مقابل BSP)، برامج Tech Provider وSolution Partner، الـEmbedded Signup، الـBusiness Verification، التسعير الحالي وأسعار السعودية، فئات القوالب وإعادة التصنيف، حدود الإرسال والجودة، الـOpt-in، سياسة الـAI Providers، حدود الرسائل التسويقية لكل مستخدم، والقواعد السعودية (PDPL، نظام التجارة الإلكترونية، CST).
- **المنهجية:**
  1. قراءة مباشرة لوثائق Meta الرسمية: صفحات الـPricing والـPricing updates والـTemplate categorization والـMessaging limits والـPer-user limits والـOpt-in والـEmbedded Signup والـCoexistence والـMarketing Messages API والـAI Provider pricing والـChangelog، ونص Meta Terms for WhatsApp Business Platform وسياسة WhatsApp Business Messaging Policy.
  2. لأن Meta بتنشر أسعار كل دولة في ملفات CSV/PDF معرفتش أقراها مباشرة، راجعت سعر السعودية على 3 مصادر ثانوية مستقلة (Nonito وFlowcall وAlibaba Cloud) وقارنتها بسعر الربط الثابت 3.75 SAR/USD.
  3. مصادر قانونية (مكاتب محاماة ومشغلين سعوديين) للـPDPL ونظام التجارة الإلكترونية وتنظيمات CST.
- **حدود البحث:**
  - ملفات الـrate card الرسمية (CSV/PDF) ما اتقرتش مباشرة، وأسعار السعودية بالأرقام جاية من مصادر ثانوية متطابقة مع بعض. اتجاه التغيير (زيادة أو تخفيض) وتاريخه متأكد منهم من Meta.
  - ميزانية البحث على الويب خلصت قبل ما أتأكد من: دعم الـCoexistence في السعودية، وموقف Meta بعد قرار الـEU، والنص الرسمي لتنظيمات CST الحالية وهل بتشمل تطبيقات الـOTT.
  - بعض الحقائق اتأكدت من ملخص نتائج البحث بس، من غير فتح الصفحة نفسها. دي متعلَّم عليها **(مصدر ثانوي)**.
  - مفيش استشارة قانونية. القسم القانوني توصيفي، مش رأي قانوني.

> **الوسوم:** [مؤكدة] = ليها مصدر (رابط + تاريخ الاطلاع 2026-10-21) · [افتراض] = محتاجة تتختبر · [اقتراح] = توصية مننا · **غير مؤكدة** = ملقيتلهاش تأكيد أو المصادر متعارضة فيها.

---

## 1. الخلاصة التنفيذية

| # | النتيجة | الوسم |
|---|---|---|
| 1 | رسائل Abandoned Cart **تسويقية (Marketing)** عند Meta، حتى لو العميل هو اللي طلبها. محاولة تمريرها كـUtility بتتصنف Marketing تلقائيًا، وتكرار ده عليه عقوبات بتوصل لتقييد الـportfolio كله | [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/template-categorization.md (2026-10-21) |
| 2 | سعر رسالة Marketing في السعودية **0.0576 USD (حوالي 0.2159 SAR)** من 2026-10-01، وكان 0.0455 قبل 2026-04-01 و0.0501 من 2026-04-01. يعني زاد حوالي 27% في 7 شهور | [مؤكدة] (مصادر ثانوية متطابقة، والاتجاه متأكد من Meta) |
| 3 | من **2026-10-01** رسائل الـService (الردود الحرة جوه نافذة الـ24 ساعة) **مبقتش مجانية**: أول 1,000 رسالة لكل رقم في الشهر ببلاش، وبعدها بتتحسب (السعودية حوالي 0.0107 USD). ورسائل الـUtility جوه النافذة كمان بقت بتتحسب | [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing/updates-to-pricing (2026-10-21) |
| 4 | شروط Meta (القسم 4.7) **بتمنع** مزوّدي الـAI من استخدام المنصة لو الـAI هو الوظيفة **الأساسية** المعروضة (assistants عامة)، **وبتسمح** للشركة إنها تستعين بمزوّد AI كـSolution Provider يخدم عملاءها | [مؤكدة] https://www.facebook.com/legal/Meta-Terms-for-WhatsApp-Business-Platform (2026-10-21) |
| 5 | نفس القسم **بيمنع استخدام بيانات WhatsApp لتدريب أو تحسين أي نموذج AI**، حتى لو البيانات مجهّلة أو مجمّعة. الاستثناء الوحيد هو fine-tuning لنموذج **حصري للشركة نفسها** | [مؤكدة] نفس المصدر |
| 6 | Meta أطلقت **Meta Business Agent** (فئة تسعير جديدة من 2026-07-01) بسعر 2 USD لكل مليون token، يعني حوالي 4-5 سنت للرسالة. ده منافس مباشر لأي AI Sales Agent | [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing (2026-10-21) |
| 7 | الإرسال التسويقي عن طريق Cloud API هيتوقف في **2027-10-13** (والحسابات الجديدة من 2027-04-13). الـMarketing لازم يعدّي على **Marketing Messages API** | [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/marketing-messages/overview.md (2026-10-21) |
| 8 | الـTech Provider **مالوش credit line**: التاجر لازم يضيف وسيلة دفع خاصة بيه لـMeta. الـcredit line والفوترة المباشرة متاحين للـSolution Partner بس، أو عن طريق Multi-Partner Solution | [مؤكدة] https://developers.facebook.com/docs/whatsapp/solution-providers (2026-10-21) |
| 9 | حد الـonboarding الافتراضي **10 عملاء جدد كل 7 أيام**، ويوصل لـ200 بعد Business Verification وApp Review (وAccess Verification، ودي اتشالت حسب مصادر ثانوية) | [مؤكدة] https://developers.facebook.com/docs/whatsapp/embedded-signup (2026-10-21) |
| 10 | المادة 25 من نظام حماية البيانات الشخصية (PDPL) بتشترط **موافقة مسبقة موثّقة** وآلية إلغاء واضحة لأي رسائل إعلانية، وسدايا أعلنت 48 قرار مخالفة منها رسائل تسويقية من غير موافقة | [مؤكدة] (مصادر ثانوية) |

---

## 2. طرق الوصول: Cloud API مباشرة مقابل BSP

### 2.1 حقائق أساسية
- الـOn-Premises API انتهى. آخر نسخة منه انتهت صلاحيتها في 2025-10-23، والطرق الرسمية الباقية هي Cloud API (مباشرة أو عن طريق BSP). [مؤكدة] https://developers.facebook.com/docs/whatsapp/on-premises/sunset (2026-10-21) (مصدر ثانوي: ملخص بحث)
- Meta بتقسم الشركاء لثلاث فئات: **Solution Partners** (BSP: ليهم credit line وبيعملوا فواتير للعملاء مباشرة، والانضمام "عملية طويلة")، و**Tech Providers** (نفس الخدمات من غير credit line، والعميل بيضيف وسيلة دفعه)، و**Tech Partners** (Tech Providers مؤهلين يبقوا Meta Business Partners ويدخلوا Accelerator Program). [مؤكدة] https://developers.facebook.com/docs/whatsapp/solution-providers (2026-10-21)
- الـ**Multi-Partner Solution** بيسمح لـTech Provider إنه يشترك مع Solution Partner "يشارك الـcredit line بتاعه مع العملاء اللي اتعملهم onboarding من خلال الحل المشترك". [مؤكدة] نفس المصدر

### 2.2 مقارنة الخيارات

| المعيار | Tech Provider مباشر (Cloud API) | ISV فوق BSP (مثلًا Twilio / 360dialog) | Multi-Partner (Tech Provider + Solution Partner) | Solution Partner بنفسنا |
|---|---|---|---|---|
| رسوم فوق Meta | مفيش | Twilio: **0.005 USD لكل رسالة، داخلة أو خارجة** [مؤكدة] https://www.twilio.com/en-us/whatsapp/pricing (2026-10-21). 360dialog: اشتراك **59 / 119 / 299 USD شهريًا لكل رقم**، و**7% surcharge** على رسائل الـMarketing اللي بتتبعت من الـendpoint العادي بدل MM API [مؤكدة] https://docs.360dialog.com/docs/360dialog/prices-plans-and-payment-options (2026-10-21) | حسب الاتفاق مع الشريك: **غير مؤكدة** | مفيش |
| مين بيدفع لـMeta | التاجر بكارته | الـBSP، وبيعمل فاتورة | الـSolution Partner بالـcredit line | إحنا |
| المتطلبات | Business Verification + App Review + Advanced access لـ`whatsapp_business_messaging` و`whatsapp_business_management` [مؤكدة] https://developers.facebook.com/docs/whatsapp/solution-providers/get-started-for-tech-providers (2026-10-21) | عقد مع الـBSP | الاتنين | Meta Business Partner و"عملية طويلة" [مؤكدة] |
| التحكم والاعتمادية | كامل | معتمد على الـBSP | متوسط | كامل |
| مناسب للـMVP؟ | [اقتراح] نعم، كمسار أساسي | [اقتراح] بديل لو التحقق اتأخر | [اقتراح] لما يثبت إن الدفع بالكارت عائق | [اقتراح] لا |

- [افتراض] نسبة من تجار سلة الصغار ممكن يواجهوا صعوبة في إضافة كارت دولي لـMeta. ده محتاج يتختبر في المقابلات قبل ما نقرر نروح لـMulti-Partner.
- [مؤكدة] المنافس Thikaa بيقدم QR pairing غير رسمي جنب Cloud API، وKasbly بيربط بالـtoken يدويًا من غير Embedded Signup. (راجع `docs/research/thikaa.md` و`docs/research/kasbly.md`)

---

## 3. Tech Provider والـEmbedded Signup والـBusiness Verification

### 3.1 خطوات Tech Provider
- Meta app فيها WhatsApp use case، بعدها Business Verification (لازم تخلص **قبل** App Review)، بعدها App Review (فيديو لرسالة اتبعتت واتسلمت + فيديو لإنشاء template)، بعدها Advanced access للصلاحيتين، وأخيرًا Webhooks. من غير `whatsapp_business_management` المتقدمة، أي API call على حسابات مش بتاعتك بترجع error 200. [مؤكدة] https://developers.facebook.com/docs/whatsapp/solution-providers/get-started-for-tech-providers (2026-10-21)
- العملاء اللي بيعدّوا الـonboarding من خلال Tech Provider "لازم يضيفوا credit card". [مؤكدة] نفس المصدر

### 3.2 Embedded Signup
- بيعمل الـassets (WABA والرقم) ويديك business token، وبعدها بتسجل الرقم وتشترك في الـwebhooks. [مؤكدة] https://developers.facebook.com/docs/whatsapp/embedded-signup (2026-10-21)
- **حدود الـonboarding:** 10 عملاء جدد في نافذة 7 أيام متحركة بشكل افتراضي، و200 بعد Business Verification وApp Review وAccess Verification، وأكتر من 200 في الأسبوع محتاج تقديم كـMeta Business Partner. [مؤكدة] نفس المصدر + https://www.infobip.com/docs/whatsapp/tech-provider-program/business-onboarding (مصدر ثانوي)
- **تعارض:** حسب مصدر ثانوي، Meta أعلنت إن **Access Verification مبقتش مطلوبة** للـTech Provider. التاريخ مختلف بين المصادر: 2026-02-04 حسب ملخص بحث للـchangelog، و2026-10-09 حسب https://singhamandeep.com/?p=3959. وصفحة Meta الرسمية للـEmbedded Signup لسه ذاكرة Access Verification كشرط لحد الـ200. **غير مؤكدة**.
- الإصدارات v2 وv3 **اتوقفت في 2026-10-15**، والمطلوب v4. [مؤكدة] https://developers.facebook.com/docs/whatsapp/embedded-signup (2026-10-21)
- v4 فيها flow اسمه "Phone Number First" (public preview من 2026-05-12). [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/changelog (2026-10-21)

### 3.3 Coexistence (التاجر يفضل شغال على تطبيق WhatsApp Business بنفس الرقم)
- مدعوم لو إحنا Tech Provider أو Solution Partner. الشروط: تطبيق نسخة ≥ 2.24.17، والـhistory sync لازم يبدأ خلال 24 ساعة. الـthroughput ثابت عند **20 mps**. الـbroadcast lists والـview-once والـlive location معطّلين. الرسايل من التطبيق مجانية، والرسايل من الـAPI بأسعار Cloud API. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/embedded-signup/onboarding-business-app-users.md (2026-10-21)
- هل الـCoexistence متاح في السعودية؟ **غير مؤكدة** (الصفحة مفيهاش قائمة دول). [افتراض] غالبًا متاح، لأن Thikaa بيقدمه لعملاء سعوديين حسب `docs/research/thikaa.md`.

### 3.4 Business Verification
- لازم تخلص قبل App Review للـTech Provider. [مؤكدة] (المصدر في 3.1)
- العميل اللي بيعمل verification بيقدر يوصل لحد الـ2,000. وفي Solution Partners بيقدروا يقدموا التحقق بالنيابة عن العميل (Partner-led). [مؤكدة] https://developers.facebook.com/docs/whatsapp/solution-providers + https://developers.facebook.com/documentation/business-messaging/whatsapp/messaging-limits.md (2026-10-21)
- المستندات المعتادة: إثبات الكيان القانوني (للسعودية غالبًا السجل التجاري) + إثبات عنوان أو هاتف، وموقع ودومين بنفس الاسم القانوني. [مؤكدة] https://docs.360dialog.com/docs/waba-management/whatsapp-business-verification/meta-business-verification (مصدر ثانوي). القائمة الرسمية لكل دولة والسعودية بالذات: **غير مؤكدة**.
- مدة التحقق من 1 لـ14 يوم عمل حسب مصادر ثانوية مختلفة. **غير مؤكدة**، و[افتراض] نخطط على أسبوعين لـ4 أسابيع للمسار الكامل (Verification + App Review).

---

## 4. التسعير

### 4.1 النموذج الحالي (Per-Message)
- من 2025-07-01 Meta بتحسب على كل رسالة **delivered** من البزنس للمستخدم، والرسائل من المستخدم للبزنس مجانية. السعر بيختلف حسب الفئة وكود دولة المستلم. [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing (2026-10-21)
- الفئات: Templates (Marketing وUtility وAuthentication) وبتتبعت في أي وقت. Non-template (Service، وMeta Business Agent) جوه نافذة الـ24 ساعة بس. [مؤكدة] نفس المصدر
- الـ**Volume tiers** بتطبق على Utility وAuthentication بس، على مستوى الـbusiness portfolio، وبتتصفّر كل شهر. [مؤكدة] نفس المصدر
- Meta بتغير الأسعار في بداية كل ربع سنة بس، وبتدي إشعار قبلها بشهر على الأقل. [مؤكدة] نفس المصدر
- في 2026 ظهرت ميزة **max price** لرسائل الـMarketing عن طريق MM API. [مؤكدة] نفس المصدر (مذكورة من غير تفاصيل)

### 4.2 قواعد المجاني (اتغيرت في 2026-10-01)

| القاعدة | قبل 2026-10-01 | من 2026-10-01 | المصدر |
|---|---|---|---|
| Service (رد حر جوه الـCSW) | مجاني من غير حد | **أول 1,000 رسالة/رقم/شهر مجانية، وبعدها مدفوعة.** ومن غير وسيلة دفع، Meta بتوقف تسليم الرسايل بعد ما الحد المجاني يخلص | [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing/updates-to-pricing (2026-10-21) |
| Utility جوه الـCSW | مجانية (من 2025-07-01) | **مدفوعة** (pricing type اتغير من `free_customer_service` لـ`regular`) | [مؤكدة] نفس المصدر + https://developers.facebook.com/docs/whatsapp/pricing |
| Free Entry Point (FEP) | **لحد 7 أيام**، بيبدأ لما المستخدم يبعت من Click-to-WhatsApp ad على Android أو iOS والبزنس يرد جوه الـCSW. كل الفئات مجانية ما عدا Meta Business Agent | بدون تغيير | [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing (2026-10-21) |
| الجهات الحكومية وغير الربحية | — | الـService مجانية بعد الحد لحد 2027-12-31 | [مؤكدة] المصدر الأول |

- **تعارض:** صفحة whatsappbusiness.com التسويقية لسه بتقول إن الـService messages "مش بتتحسب"، وده بيتعارض مع وثائق المطورين. [مؤكدة] https://whatsappbusiness.com/products/platform-pricing/ (2026-10-21). **المرجح إن وثائق المطورين هي الأحدث.**
- **تعارض:** مصدر ثانوي واحد (wati) بيقول إن الـService كانت "مجانية من نوفمبر 2024". ده متوافق مع تاريخ Meta ومش متعارض.

### 4.3 أسعار السعودية (rate card بالـUSD، ساري من 2026-10-01)

| الفئة | USD | SAR (كارت الريال) | المصادر |
|---|---|---|---|
| Marketing | **0.0576** | **0.2159** | [مؤكدة] https://www.nonito.io/whatsapp-business-api-pricing ؛ https://www.flowcall.co/blog/whatsapp-business-api-pricing ؛ https://www.alibabacloud.com/help/en/chatapp/whatsapp-message-rate-adjustments-and-service-conversation-message-billing (2026-10-21). كلها مصادر ثانوية بتنقل عن rate card بتاع Meta |
| Utility | 0.0107 | 0.0401 | [مؤكدة] Nonito + Flowcall |
| Authentication | 0.0107 | 0.0401 | [مؤكدة] Nonito + Flowcall |
| Authentication-International | 0.0598 | 0.224 | [مؤكدة] Nonito + Flowcall |
| Service (بعد أول 1,000) | 0.0107 | 0.0401 | [مؤكدة] Nonito + Flowcall. وAlibaba بيبيع بـ0.0118 = 1.1 × سعر Meta، وده بيأكد الرقم بشكل غير مباشر |
| Meta Business Agent | 2.00 USD / 1M tokens (سعر عالمي، حوالي 0.04-0.05 للرسالة) | — | [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing (2026-10-21) |

**تاريخ سعر الـMarketing في السعودية:**

| الفترة | USD | المصدر |
|---|---|---|
| لحد 2026-03-31 | 0.0455 | [مؤكدة] https://www.alibabacloud.com/help/en/chatapp/notice-about-whatsapp-message-fee-update-on-april-1-2026 + https://www.ycloud.com/blog/whatsapp-api-message-pricing-update-effective-april-1-2026 (مصادر ثانوية) |
| 2026-04-01 → 2026-09-30 | 0.0501 (= حوالي 0.1877 SAR) | [مؤكدة] نفس المصادر، وMeta بتأكد إن السعر "زاد" في 2026-04-01 https://developers.facebook.com/docs/whatsapp/pricing/updates-to-pricing |
| من 2026-10-01 | 0.0576 | [مؤكدة] Alibaba (0.0501 → 0.0576)، وMeta بتأكد "Increase" في 2026-10-01 |

- **تفسير التعارض:** مواقع زي whautomate بتعرض 0.1877 SAR للـMarketing، ودي أرقام الربع اللي فات (0.0501 × 3.75). ومصدر تالت بيذكر 0.05187 USD، ومالوش أساس واضح. **غير مؤكدة**.
- Meta خفّضت سعر Utility/Authentication في السعودية في 2025-10-01، وAuthentication في 2025-02-01. [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing/updates-to-pricing (2026-10-21)
- [افتراض] الاتجاه واضح: Meta بترفع سعر الـMarketing في السعودية كل ربعين تقريبًا، وبتنزّل سعر Utility. لازم نتعامل مع الـMarketing كتكلفة متغيرة بتزيد.

---

## 5. القوالب (Templates): الفئات والموافقة وإعادة التصنيف

| الفئة | التعريف | أمثلة مهمة لينا | المصدر |
|---|---|---|---|
| Marketing | الأوسع: awareness ومبيعات وretargeting. أي قالب فيه محتوى مختلط أو مش واضح بيتحط هنا افتراضيًا | **"You left {{items}} in your cart!" مثال Retargeting، و"marketing even if requested by users"** | [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/template-categorization.md (2026-10-21) |
| Utility | متابعة لفعل أو طلب من المستخدم، من غير ترويج، وخاص بطلبه أو حسابه | تأكيد الطلب، الشحن، الاستبيان المرتبط بتفاعل معيّن | [مؤكدة] نفس المصدر |
| Authentication | OTP بس، من Template Library، ومن غير URLs أو وسائط أو إيموجي | — | [مؤكدة] نفس المصدر |

**قواعد إعادة التصنيف:**
- من 2024-07-01 في مراجعة دورية للقوالب المعتمدة. لو قالب Utility طلع Marketing، بيتغير تصنيفه مع **إشعار قبلها بيوم واحد**. [مؤكدة] نفس المصدر
- من **2025-04-09**: أي قالب Utility شكله تسويقي بيتعتمد على إنه **Marketing من الأول**. [مؤكدة] نفس المصدر
- من **2025-04-16**: اللي اتحذّر قبل كده بيتغير تصنيفه **فورًا** من غير إشعار. [مؤكدة] نفس المصدر
- الطعن متاح خلال **60 يوم** من Business Support Home. القوالب اللي هتترفض بسبب تصنيف Authentication مالهاش طعن. [مؤكدة] نفس المصدر
- **عقوبات إساءة استخدام Utility:** تحذير، بعدها rate limits (7 أيام على الأقل)، بعدها إعادة تصنيف إجبارية (7 أيام، أو 30 لو اتكررت)، وبعدها **تقييد على مستوى الـportfolio لمدة 30 يوم**. [مؤكدة] نفس المصدر
- المراجعة بتاخد "لحد 24 ساعة"، والطعن بيتبت فيه خلال 24 ساعة. أسباب الرفض الشائعة: أخطاء في المتغيرات، محتوى مخالف، تكرار نفس النص. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/template-review.md (2026-10-21)
- **تعارض:** بلوجات vendors (زي chakrahq) بتقول إن تذكير السلة من غير خصم ممكن يتصنف Utility. نص Meta صريح إن ده Marketing. **نعتمد نص Meta.**

---

## 6. حدود الإرسال والجودة

### 6.1 Messaging limits
- البداية **250** مستخدم فريد كل 24 ساعة متحركة، **على مستوى الـbusiness portfolio** (كل الأرقام بتتشارك فيه). وبتتحسب بس الرسائل اللي **برّه** الـCSW. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/messaging-limits.md (2026-10-21)
- للوصول لـ2,000 فيه 3 طرق: Business Verification، أو تحقق عن طريق الشريك، أو 2,000 رسالة عالية الجودة لأرقام فريدة خلال 30 يوم. بعد كده الزيادة تلقائية (10K، بعدها 100K، بعدها unlimited) لو الجودة عالية واستخدمت نص الحد على الأقل في آخر 7 أيام، والترقية بتحصل خلال 6 ساعات. [مؤكدة] نفس المصدر
- [اقتراح] **ده بيأثر على تصميم الـMulti-Tenancy:** كل تاجر لازم يبقى في business portfolio خاص بيه (Embedded Signup بيعمل ده)، ومينفعش نجمع التجار تحت portfolio بتاعنا، علشان مشكلة جودة عند تاجر واحد متقفلش الكل.

### 6.2 Quality rating
- الجودة للقالب: GREEN / YELLOW / RED / UNKNOWN، وبتعتمد على الـfeedback السلبي ونسبة القراءة. القالب ممكن يتعمله pause أو يتعطّل، ومش هيتبعت إلا لو حالته APPROVED. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/template-quality.md (2026-10-21)
- مدة الـpause (3 ساعات / 6 ساعات) المذكورة في مصادر قديمة: **غير مؤكدة** في الوثائق الحالية.
- السياسة بتنص إن الـblocks والـreports ممكن تحدد عدد الرسايل اللي البزنس يقدر يبعتها. [مؤكدة] https://whatsappbusiness.com/policy/ (2026-10-21)

### 6.3 حدود الـMarketing لكل مستخدم (Frequency cap)
- **مفيش رقم ثابت.** WhatsApp بتحدد عدد قوالب الـMarketing اللي المستخدم يستقبلها من البزنس في فترة معينة حسب نسبة قراءته للرسائل التسويقية وعدد الرسايل اللي جاياله. والحد ده بيتغير مع الوقت. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/templates/marketing-templates/per-user-limits.md (2026-10-21)
- الرفض بيرجع **error 131049**، ولازم تستنى 24 ساعة على الأقل قبل ما تعيد. وإعادة المحاولة المتكررة ممكن تعمل block مؤقت لحد 24 ساعة. **رسائل الـMarketing اللي بتتبعت جوه CSW مفتوحة مش بتتحسب على الحد.** [مؤكدة] نفس المصدر
- قوالب الـMarketing مش بتوصل لأرقام أمريكية (+1). [مؤكدة] نفس المصدر. ودي مش مهمة للسعودية.
- بما إن الحساب على الـ**delivered** بس، فالرسالة اللي بتترفض بـ131049 [افتراض] مش بتتحسب. ده استنتاج من قاعدة "charged only when delivered" https://developers.facebook.com/docs/whatsapp/pricing، والصفحة نفسها مبتقولش ده صراحة.

---

## 7. الـOpt-in وسياسة الرسائل

- لازم تاخد **opt-in** قبل ما تبعت أي رسالة. الـopt-in لازم يوضح إن الشخص موافق يستقبل رسائل من البزنس **ويذكر اسمه**. ومش لازم يذكر WhatsApp بالاسم "طالما ملتزم بالقوانين المحلية". [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/getting-opt-in.md (2026-10-21)
- القنوات المقبولة: SMS، الموقع، IVR، توقيع ورقي أو حضوري. والـopt-in المنفصل لكل فئة **مستحسن** مش إلزامي. [مؤكدة] نفس المصدر
- لازم تحترم أي طلب إيقاف، سواء جه على WhatsApp أو برّه. [مؤكدة] https://whatsappbusiness.com/policy/ (آخر تحديث 2026-09-23) (2026-10-21)
- **الأتمتة:** مسموحة جوه نافذة الـ24 ساعة، بشرط وجود "prompt, clear, and direct escalation paths" لإنسان (تحويل للوكيل جوه الشات، أو تليفون، أو إيميل...). [مؤكدة] نفس المصدر
- ممنوع تطلب أو تبعت **رقم كارت كامل**. [مؤكدة] نفس المصدر
- ممنوعات تجارية تخص قطاعنا: "Medical and healthcare products"، والكحول، والتبغ... [مؤكدة] نفس المصدر. هل منتجات Personal Care والمكملات بتدخل تحت "healthcare"؟ **غير مؤكدة**، ومحتاجة مراجعة للـCommerce Policy لكل كتالوج.

---

## 8. سياسة الـAI Providers (حرجة للـAI Sales Agent)

### 8.1 النص والتواريخ
- **القسم 4.7، "AI Providers":** مزوّدو ومطوّرو تقنيات الـAI/ML (ومنها الـLLMs ومنصات الـGenerative AI) "strictly prohibited from accessing or using the WhatsApp Business Platform, whether directly or indirectly" **لما التقنيات دي تبقى الوظيفة الأساسية (مش العرضية أو المساعدة) المعروضة للاستخدام**. ومع ذلك، الشركة "may retain an AI Provider as a Solution Provider". [مؤكدة] https://www.facebook.com/legal/Meta-Terms-for-WhatsApp-Business-Platform (آخر تعديل 2026-09-23) (2026-10-21)
- **بيانات التدريب:** ممنوع "directly or indirectly" تسمح باستخدام WhatsApp Business Platform Data (حتى لو مجهّلة أو مجمّعة أو مشتقة) علشان "create, develop, train, or improve any machine learning or artificial intelligence systems". الاستثناء الوحيد fine-tuning لنموذج "for your exclusive use". [مؤكدة] نفس المصدر
- السياسة سارية من **2026-01-15**. وMeta قالت لـTechCrunch إن الهدف هو مزوّدي الـassistants العامة، وإن الشركات اللي بتستخدم الـAI لخدمة عملائها (المثال كان شركة سفر عندها bot خدمة عملاء) مش مقصودة. وMeta هي اللي بتحدد إيه اللي داخل في المنع. [مؤكدة] https://techcrunch.com/2025/10/18/whatssapp-changes-its-terms-to-bar-general-purpose-chatbots-from-its-platform/ (2026-10-21)
- OpenAI وMicrosoft (Copilot) وPerplexity وقفوا الـassistants بتاعتهم على WhatsApp. [مؤكدة] نفس المصدر

### 8.2 مسموح أو ممنوع (حسب قراءتنا)

| الاستخدام | الحكم | الوسم |
|---|---|---|
| Assistant عام بيرد على أي سؤال (ChatGPT-like) على رقم مزوّد الـAI | ممنوع برّه EEA والبرازيل | [مؤكدة] |
| Bot لتاجر معيّن بيرد عن منتجاته وطلباته وشحنه ويسترجع سلته، على رقم التاجر | مسموح (AI عرضي لخدمة البزنس، ومزوّد الـAI "Solution Provider") | [مؤكدة] للمبدأ (TechCrunch + نص الشروط). وتطبيقه على CODEX [افتراض] |
| CODEX كـTech Provider منتجه الأساسي "AI Sales Agent" | **منطقة رمادية:** الشركة نفسها "AI Provider"، لكن الـAI بيخدم بزنس التاجر مش كـassistant عام. Meta عندها صلاحية تقديرية | **غير مؤكدة** |
| تدريب أو تحسين نموذج مشترك بين التجار على محادثات WhatsApp | **ممنوع** | [مؤكدة] |
| Fine-tuning نموذج خاص بتاجر واحد على بياناته | مسموح بشرط إنه ميدرّبش نماذج تانية | [مؤكدة] |
| استخدام LLM vendor (OpenAI أو Anthropic...) للـinference | مسموح كـSolution Provider، بشرط إن الـvendor ميدرّبش على البيانات | [مؤكدة] للشرط + [اقتراح] نعتمد على zero-retention / no-training contracts |

### 8.3 الاستثناءات الجغرافية والتنظيمية
- الـ**EU** فرض interim measures (يونيو 2026) بتلزم Meta ترجع وصول الـassistants المنافسة بشروط ما قبل 2025-10-15، وده لحين القرار النهائي. [مؤكدة] https://www.lawyer-monthly.com/2026/06/eu-orders-meta-restore-ai-access-whatsapp/ (2026-10-21)
- Meta بتحسب على الـAI Providers سعر Service في البرازيل (من 2026-03-11)، وكانت بتحسب في الـEU/EEA وإيطاليا ووقفت في 2026-05-13، وتركيا من غير رسوم لحد 2027-04-11. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing/ai-providers.md (2026-10-21)
- **السعودية مش ضمن أي استثناء.** [مؤكدة] (مش مذكورة في الصفحة اللي فاتت). [افتراض] المنع الكامل ساري على السعودية.

### 8.4 Meta Business Agent (منافس من جوه المنصة)
- فئة "Meta Business Agent" اتعملت في 2026-07-01: رسائل non-template شغالة بـMeta Business Agent Platform، بسعر 2 USD لكل مليون token (حوالي 4-5 سنت للرسالة)، **ومش مجانية حتى جوه الـFEP**. [مؤكدة] https://developers.facebook.com/docs/whatsapp/pricing (2026-10-21)
- الإطلاق كان في 2026-06-03 في Conversations، وأكتر من مليون بزنس بيستخدموه، والفوترة بدأت 2026-08-01. **غير مؤكدة** (مصادر ثانوية زي https://www.wati.io/en/blog/meta-whatsapp-ai-token-pricing/ متعارضة في التواريخ).
- [افتراض] Meta دلوقتي **منافس وصاحب المنصة في نفس الوقت**: بتقدم agent بيرد على أسئلة المنتجات بسعر شفاف، والميزة التنافسية لـCODEX لازم تكون في اللي Meta مش بتعمله، زي ربط ربحية الطلب بسلة/مخزون Salla، والسياسات المالية للخصومات، والـattribution.

---

## 9. القواعد السعودية للرسائل التجارية

| التشريع | المتطلب | الوسم |
|---|---|---|
| **PDPL، المادة 25** (SDAIA) | ممنوع استخدام وسائل الاتصال الشخصية لإرسال مواد إعلانية إلا (1) بموافقة مسبقة من المستلم و(2) مع آلية واضحة لإيقافها. واللائحة بتشترط إن الموافقة تكون حرة **وموثّقة**، والمرسل يكون واضح، والإيقاف يحصل ببلاش أول ما يطلبه المستلم | [مؤكدة] https://ksapdpl.com/ksa-saudi-pdpl-article-25-restrictions-on-direct-marketing-and-awareness-messages/ (مصدر ثانوي بينقل النص) + https://cms.law/en/are/legal-updates/one-year-anniversary-saudi-personal-data-protection-law (2026-10-21) |
| PDPL، العقوبات | غرامة لحد 5 مليون SAR، وبتتضاعف لو اتكررت | [مؤكدة] https://cms.law/en/are/legal-updates/one-year-anniversary-saudi-personal-data-protection-law (2026-10-21) |
| PDPL، التطبيق الفعلي | 48 قرار مخالفة عن سنة 2025 اتعلنوا في 2026-01-16، ومنها "رسائل تسويقية من غير موافقة" | [مؤكدة] https://profectus.sa/resources/sdaia-enforcement-decisions (مصدر ثانوي). والتفاصيل والغرامات لكل حالة: **غير مؤكدة** |
| موافقة الطلب ≠ موافقة التسويق | الموافقة على تحديثات الطلب مش موافقة على العروض | [مؤكدة] (إرشادات ممارسين، مصدر ثانوي). [افتراض] الشراء السابق لوحده مش كفاية كموافقة |
| **نظام التجارة الإلكترونية ولائحته** (2020) | الإعلان لازم يحدد اسم المرسل وبيانات التواصل، ويتعلّم عليه إنه إعلان، وفيه طريقة لإيقاف الإعلانات. والبيانات الشخصية متستخدمش في التسويق من غير موافقة. والعقوبة لحد 1 مليون SAR | [مؤكدة] https://www.tamimi.com/law-update/july-2020/articles/theres-something-in-your-cart-an-update-on-e-commerce-in-saudi-arabia/ + https://www.globalcompliancenews.com/2020/03/16/saudi-arabia-regulates-e-commerce/ (مصادر ثانوية). وهل اتعدّلت بعد 2020؟ **غير مؤكدة** |
| **تنظيمات CST للحد من الرسائل الاقتحامية** | Opt-in وopt-out صريحين، والموافقة اللي جوه سياسة الخصوصية أو العقد "مش صالحة"، وعبء الإثبات على المرسل، والإيقاف خلال 24 ساعة | [مؤكدة] https://taqnyat.sa/assets/download/compliance-en.pdf + https://www.stc.com.sa/content/dam/corporatesite/common/business/pdf/RegulationsforCurbingSPAMMessagesCallspdfEN.pdf (2026-10-21) |
| انطباق CST على WhatsApp/OTT | تعريف الـSPAM بيقول "any electronic means… including but not limited to email, SMS…" ومش بيذكر WhatsApp صراحة | **غير مؤكدة** |

- [اقتراح] نطبّق أعلى معيار مشترك بين الكل: موافقة صريحة منفصلة للتسويق عبر WhatsApp تتسجل مع timestamp ومصدرها (checkout، صفحة، رسالة)، ورسالة فيها اسم المتجر، وطريقة إيقاف في كل رسالة تسويقية (زر "إيقاف العروض")، وإيقاف خلال أقل من 24 ساعة.

---

## 10. تقدير تكلفة رسالة الاسترجاع (Abandoned Cart)

### 10.1 الافتراضات
- سعر Marketing في السعودية 0.0576 USD (0.2159 SAR) لكل رسالة **delivered**. [مؤكدة] (قسم 4.3)
- معدل التسليم 95%، والرسالتين التانية والتالتة بيتبعتوا بس للي لسه مكملش الطلب (90% ثم 85%). [افتراض]
- نسبة الاسترجاع للسلات المتروكة اللي وصلها تذكير: 5% / 10% / 15%. [افتراض] **لازم تتقاس في pilot**
- ردود الـAI agent بعد ما العميل يرد (Service) حوالي 6 رسائل للمحادثة. أول 1,000 مجانية لكل رقم في الشهر، وبعدها 0.0107 USD. [افتراض] للعدد، و[مؤكدة] للسعر
- مش داخل في الحساب: تكلفة الـLLM، ورسوم الـBSP، وتكلفة الخصم نفسه.

### 10.2 الحساب

| السيناريو | رسائل Marketing لكل سلة (متوقعة) | تكلفة Meta لكل سلة | تكلفة لكل طلب مسترجع @5% | @10% | @15% |
|---|---|---|---|---|---|
| A: رسالة واحدة | 0.95 | 0.055 USD (0.21 SAR) | 1.09 USD (4.1 SAR) | 0.55 USD (2.1 SAR) | 0.36 USD (1.4 SAR) |
| B: تسلسل 3 رسائل | 0.95 + 0.86 + 0.81 ≈ 2.62 | 0.151 USD (0.57 SAR) | 3.02 USD (11.3 SAR) | 1.51 USD (5.7 SAR) | 1.01 USD (3.8 SAR) |
| B + محادثة AI (6 Service بعد الحد المجاني) لـ20% من السلات | +0.013 USD/سلة | 0.164 USD (0.62 SAR) | 3.28 USD (12.3 SAR) | 1.64 USD (6.2 SAR) | 1.09 USD (4.1 SAR) |
| B + محادثة AI عن طريق Twilio (+0.005 لكل رسالة داخلة وخارجة، حوالي 5 رسائل لكل سلة) | — | حوالي 0.19 USD | حوالي 3.8 USD | حوالي 1.9 USD | حوالي 1.25 USD |

- [افتراض] لو متوسط سلة العطور/Beauty حوالي 250 SAR، تكلفة الاسترجاع (من غير LLM) بتتراوح بين **حوالي 1% و5% من قيمة الطلب**. يعني الرسائل مش هي العائق الأساسي في الاقتصاد، **الخصم** هو اللي بيحدد الربحية. وده بيدعم فرضية Profit-Aware بس محتاج قياس.
- [افتراض] الرسالة التالتة بتجيب عائد حدّي أقل، وبتزوّد خطر الـblocks وانخفاض نسبة القراءة، وده بيخفّض الـper-user limit والجودة. [اقتراح] الافتراضي يبقى رسالتين بالكتير، والتالتة بخصم بس لو هامش الربح يسمح.
- [اقتراح] **تخفيض التكلفة:** (1) نخلّي العميل يرد (زر "كمّل طلبك"، أو سؤال) علشان يفتح CSW، وبعدها رسائل الـMarketing جوه النافذة مش بتتحسب على الـper-user limit، والردود الحرة بسعر Service (0.0107 بدل 0.0576). (2) حملات Click-to-WhatsApp ads بتفتح FEP لمدة 7 أيام مجانية. (3) Volume tiers مش بتطبق على الـMarketing، يعني مفيش خصم كمية.
- [افتراض] الزيادة في السعر (+27% في 7 شهور) لو استمرت، تكلفة رسالة الاسترجاع ممكن تعدّي 0.065 USD في 2027. لازم نسعّر الباقات بهامش أمان أو نحمّل رسوم Meta على التاجر مباشرة (pass-through).

---

## 11. المخاطر (السياسات والتشغيل)

| # | الخطر | الاحتمال | الأثر | التخفيف [اقتراح] |
|---|---|---|---|---|
| 1 | Meta تعتبر CODEX "AI Provider" محظور (القسم 4.7) | منخفض لمتوسط [افتراض] | قاتل (قفل الوصول) | نصمم الـagent إنه "business-specific": كتالوج التاجر وطلباته بس، يرفض الأسئلة العامة، يدّي human handoff، والـpositioning يبقى "Commerce/Revenue platform" مش "AI chatbot" |
| 2 | استخدام محادثات WhatsApp لتحسين نموذج مشترك أو Benchmarks عابرة للتجار | متوسط (إغراء منتج) | مخالفة صريحة | فصل بيانات WhatsApp لكل tenant، وممنوع أي تدريب مشترك، وعقود LLM من غير retention أو training، وتوثيق ده في ADR |
| 3 | تمرير تذكير السلة كـUtility | عالي لو مفيش ضوابط | تقييد الـportfolio 30 يوم | Template library داخلي مصنّف Marketing، ومنع التاجر يغيّر الفئة |
| 4 | رسوم Service (2026-10-01) بتأكل هامش الـAI agent | مؤكد | متوسط | عدّاد Service لكل رقم، والتسعير يكون pass-through أو usage-based |
| 5 | انتقال الـMarketing لـMM API (2027-04 / 2027-10) | مؤكد | متوسط (عمل هندسي) | نبني الـMarketing على MM API من البداية |
| 6 | الـPer-user limit وerror 131049 بيخفضوا التسليم | متوسط | متوسط (أرقام استرجاع أقل) | Retry policy أكبر من 24 ساعة، وقياس الـdelivery rate في الـanalytics |
| 7 | Meta Business Agent بينافس بسعر وتكامل أصلي | متوسط لعالي [افتراض] | عالي على الـpositioning | التميّز بالربحية وتكامل Salla والسياسات، مش بـ"يرد على الأسئلة" |
| 8 | مخالفة PDPL / التجارة الإلكترونية في الموافقات | متوسط | غرامة لحد 5M SAR وسمعة | سجل consent موثّق، والتاجر هو الـController ومسؤوليته منصوص عليها في العقد |
| 9 | حد الـonboarding (10 في الأسبوع) قبل اكتمال التحقق | مؤكد | منخفض للـMVP | نبدأ Business Verification وApp Review بدري، قبل أول pilot |
| 10 | عائق إضافة كارت الدفع لـMeta عند التاجر | غير معروف [افتراض] | متوسط على التفعيل | نقيسه في الـpilot، وMulti-Partner كخطة بديلة |
| 11 | تغيّر شروط Meta (اتعدلت 2026-09-23) أو أسعارها كل ربع | عالي | متوسط | مراقبة ربع سنوية للـchangelog والـrate card |

---

## انعكاسات على CODEX

1. **[اقتراح] مسار الوصول للـMVP:** نبقى **Tech Provider** بـEmbedded Signup **v4** مع **Coexistence** (التاجر يفضل على تطبيقه). ونبدأ Business Verification وApp Review **دلوقتي**، لأنهم على المسار الحرج وبياخدوا من أسابيع لشهور، وحد الـ10 تجار في الأسبوع كفاية للـpilot. والـBSP والـMulti-Partner يفضلوا خطة بديلة.
2. **[اقتراح] Abandoned Cart Recovery = Marketing دائمًا:** التسعير والتصميم يبنوا على 0.0576 USD للرسالة، ومحاولة "تخفيضها" لـUtility ممنوعة. وده مخاطرة على الحساب كله.
3. **[اقتراح] Marketing على MM API من اليوم الأول**، علشان منكتبش كود هيتلغي في 2027.
4. **[اقتراح] قيد معماري حرج (Rule 7):** بيانات WhatsApp **ممنوع تتستخدم لتدريب أو تحسين أي نموذج مشترك**. ده بيحدّ من فكرة "التعلّم من كل التجار" اللي ممكن تكون جزء من Profit-Aware. البديل: قواعد وسياسات ومقاييس من بيانات Salla (طلبات وهوامش)، مش من محادثات WhatsApp، وده محتاج ADR. واستخدام محادثات WhatsApp المجمّعة في Benchmarks برضه ممنوع لأن النص بيمنع "derived/aggregate" للتدريب. حدود الـanalytics غير التدريبية **غير مؤكدة**، ومحتاجة مراجعة قانونية.
5. **[اقتراح] تصميم الـAI Sales Agent:** محصور في نطاق التاجر، ويرفض الأسئلة العامة، وفيه human handoff واضح (شرط السياسة)، ومبيطلبش أرقام كروت، ومبينفذش خصم إلا في حدود سياسة بيحددها التاجر (Rule 9).
6. **[اقتراح] التسعير التجاري:** رسوم Meta تتحسب pass-through أو تتحسب كـusage، ومتدخلش جوه الاشتراك الثابت، بسبب الزيادات الربع سنوية، ورسوم Service الجديدة، والعملة (كارت SAR متاح).
7. **[اقتراح] الـConsent جزء من الـMVP مش إضافة:** سجل موافقة لكل عميل نهائي (المصدر والوقت والفئة)، وopt-out بضغطة، وإيقاف تلقائي. وده بيغطي Meta وPDPL والتجارة الإلكترونية وCST مع بعض.
8. **[افتراض] اقتصاد الاسترجاع مقبول:** حوالي 2 لـ12 SAR لكل طلب مسترجع (من غير LLM) قصاد متوسط سلة مفترض 250 SAR. الخطر الاقتصادي الحقيقي في الخصومات ومعدل الاسترجاع الفعلي، ومحتاجين نقيسهم في pilot قبل ما نوعد بـROI.
9. **[افتراض] Positioning:** وجود Meta Business Agent بيقلل قيمة "AI بيرد على العملاء" كميزة. والتميّز المحتمل: الربط بالربحية والمخزون، والسياسات، والـattribution، ودعم العربي/الخليجي. وده لسه فرضية (Rule 3).
10. **[اقتراح] Multi-tenancy:** كل تاجر في WABA وbusiness portfolio خاصين بيه (حدود وجودة مستقلة)، وكل حاجة تتسجل بالـtenant: الـtokens والـtemplates والـwebhooks.

## أسئلة مفتوحة

1. هل Meta بتعتبر SaaS منتجه الأساسي "AI Sales Agent للتجار" "AI Provider" تحت القسم 4.7؟ محتاجين تأكيد مكتوب من Meta (Direct Support بعد Tech Provider) أو رأي قانوني.
2. هل الـanalytics المجمّعة غير التدريبية (Benchmarks بين التجار) من بيانات WhatsApp مسموحة، ولا بتعتبر "derived data" ممنوعة؟
3. الأرقام الرسمية لأسعار السعودية من ملف CSV بتاع Meta (لتأكيد 0.0576 / 0.0107 من المصدر الأساسي).
4. هل Access Verification اتلغت فعلًا، وإمتى (فبراير ولا أكتوبر 2026)؟ وهل حد الـ200 في الأسبوع محتاجها؟
5. هل الـCoexistence مدعوم في السعودية؟ وهل حد الـ20 mps بيأثر على الحملات؟
6. هل تنظيمات CST للرسائل الاقتحامية بتنطبق على WhatsApp؟ وهل في تحديث 2025/2026 بيشمل OTT؟
7. هل منتجات العناية الشخصية والمكملات بتقع تحت "medical and healthcare products" الممنوعة في Commerce Policy؟
8. هل تجار سلة المستهدفين عندهم كروت دولية جاهزة لـMeta، ولا لازم credit line عن طريق Solution Partner؟
9. نسبة الاسترجاع الفعلية والـdelivery rate (أثر 131049) لتجار عطور/Beauty سعوديين: لازم تتقاس في pilot.
10. تواريخ إطلاق وفوترة Meta Business Agent ونطاقه في السعودية، ومدى تكامله مع كتالوج Salla.
11. هل في نسخة محدّثة من اللائحة التنفيذية لنظام التجارة الإلكترونية بعد 2020 بتأثر على رسائل WhatsApp الإعلانية؟
