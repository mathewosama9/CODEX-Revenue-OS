# البحث التنظيمي والامتثال في السعودية: PDPL وتوطين البيانات والرسائل التسويقية والضرائب وتأسيس الكيان

- **تاريخ البحث والوصول لكل المصادر:** 2026-10-21
- **النطاق:** كل ما يخص منصة B2B SaaS متعددة المستأجرين (CODEX Commerce) تعالج بيانات المتسوقين لدى متاجر سعودية، وتستخدم WhatsApp Business Platform الرسمية وLLM APIs.
- **المنهجية:** بحث ويب موسّع، مع أولوية للمصادر الرسمية والأولية التي أمكن فتحها: نص NCA Cloud Cybersecurity Controls ‏(CCC-2:2024)، ونص لائحة CST للحد من الرسائل الاقتحامية (الإصدار الثالث، أكتوبر 2022)، وصفحة ZATCA عن الممثل الضريبي، ووثائق Google Cloud وAnthropic وMeta الرسمية، وإعلانات Microsoft وAWS، وسياسة متجر تطبيقات سلة. واستكملتُ النقص بتحليلات مكاتب محاماة معروفة (Clyde & Co وReed Smith وAl Tamimi وGowling WLG وKing & Spalding وMayer Brown وBaker McKenzie وBird & Bird وCMS)، لأن أغلب صفحات SDAIA الرسمية (dgp.sdaia.gov.sa) لم تُفتح عبر الأدوات. الأرقام المهمة تحققتُ منها من مصدرين كلما أمكن، والتناقضات مسجلة في القسم 12. هذه نسخة محدّثة تحل محل نسخة سابقة من الملف بنفس التاريخ: أضافت ZATCA للمقيمين وغير المقيمين، وstc cloud، والقانون المصري، وتوطين WhatsApp، وسياسة سلة، وأسعار GCP.
- **مفتاح التصنيف:**
  - **[مؤكدة]**: من مصدر أولي (الجهة التنظيمية أو المزود نفسه)، مع الرابط وتاريخ الاطلاع.
  - **[مؤكدة، ثانوي]**: المصدر مكتب محاماة أو جهة متخصصة، وليس نص الجهة التنظيمية.
  - **[افتراض]**: استنتاج يحتاج تحققًا قانونيًا أو اختبارًا.
  - **[اقتراح]**: توصية لـCODEX.
  - **غير مؤكدة**: لم أستطع التحقق منها.

### القيود (اقرأها قبل الاعتماد على أي شيء هنا)
1. **هذا ليس رأيًا قانونيًا ولا ضريبيًا.** أي قرار نهائي عن الكيان أو الاستضافة أو العقود أو VAT يحتاج محاميًا ومستشارًا ضريبيًا سعوديين مرخصين. **[اقتراح]**
2. لم أقرأ النص العربي الرسمي لـPDPL واللائحة التنفيذية ولائحة النقل. أرقام المواد مأخوذة من تحليلات ثانوية، وبعضها متضارب.
3. بعض الصفحات رجعت 403 أو 402 أو 307: Baker McKenzie (Saudi data processors)، وBird & Bird (السجل الوطني)، وAl Tamimi (مسودة أكتوبر 2026)، وOracle pricing، وazizme.com.
4. **الوضع يتغير الآن:** مسودة تعديلات اللائحة التنفيذية لـPDPL معروضة للاستطلاع حتى **5 نوفمبر 2026**، أي بعد 15 يومًا من تاريخ هذا البحث. وAzure Saudi تفتح في نوفمبر 2026، وAWS Saudi في ديسمبر 2026. لازم نراجع هذا الملف في يناير 2027.
5. أسعار السحابة مأخوذة من مُجمّعات أسعار طرف ثالث، وليست من قوائم المزودين الرسمية.

---

## 1. الملخص التنفيذي (أهم 15 نقطة)

| # | النقطة | التصنيف |
|---|---|---|
| 1 | PDPL نافذ بالكامل منذ 14 سبتمبر 2024، بعد سنة انتقالية بدأت في 14 سبتمبر 2023 | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2026/03/enforcement-of-the-saudi-pdp-law (2026-10-21) |
| 2 | التطبيق فعلي: أُعلن في يناير 2026 عن 48 قرارًا بإثبات مخالفات وفرض عقوبات. الغرامة حتى 5 ملايين ريال للمخالفة، وتتضاعف عند التكرار | **[مؤكدة، ثانوي]** Clyde & Co (السابق)، و https://www.jdsupra.com/legalnews/active-enforcement-of-saudi-arabia-3202525/ (2026-10-21) |
| 3 | **أخطر نقطة لنا:** مسودة أكتوبر 2026 (المادة 23) تُلزم الـcontroller بتخزين البيانات الشخصية **داخل المملكة**، مع بقاء النقل للخارج مسموحًا وفق الضوابط. الاستطلاع من 6 أكتوبر إلى 5 نوفمبر 2026، والنفاذ بعد 60 يومًا من النشر في الجريدة الرسمية إن اعتُمدت | **[مؤكدة، ثانوي]** من 3 مصادر: https://www.clydeco.com/en/insights/2026/10/sdaia-publishes-draft-amendments-to-the-pdpl و https://www.reedsmith.com/our-insights/blogs/viewpoints/102o5at/sdaia-consults-on-extensive-amendments-to-the-saudi-pdpl-implementing-regulation/ و https://www.tamimi.com/news/sdaia-opens-public-consultation-on-proposed-amendments-to-the-pdpl-implementing-regulation/ (2026-10-21) |
| 4 | المسودة تجعل التسجيل في السجل الوطني **إلزاميًا على كل controller ينقل بيانات للخارج**، وتجعل إخطار SDAIA بالاختراق خلال 72 ساعة **بلا شرط الضرر** | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| 5 | لا توجد قائمة ملاءمة (adequacy) منشورة. النقل اليوم يعتمد عمليًا على Saudi SCCs (4 نماذج غير قابلة للتعديل) أو BCRs أو شهادة، مع risk assessment | **[مؤكدة، ثانوي]** https://www.kslaw.com/insights/articles/international-personal-data-transfers-under-saudi-arabias-data-protection-law و https://www.mayerbrown.com/en/insights/publications/2024/10/updates-to-saudi-arabias-personal-data-protection-regulations-sccs-guidelines-and-more (2026-10-21) |
| 6 | **WhatsApp Cloud API لا يدعم تخزين البيانات في السعودية.** الـlocal storage متاح في الإمارات والبحرين (من دول MEA)، والرسائل "may be stored on Meta data centers internationally while being processed". يعني ذلك أن قناة WhatsApp نفسها نقل خارج المملكة مهما كانت استضافتنا | **[مؤكدة]** https://developers.facebook.com/docs/whatsapp/cloud-api/reference/registration و https://developers.facebook.com/docs/whatsapp/cloud-api/overview/local-storage (2026-10-21) |
| 7 | Google Cloud Dammam ‏(me-central2) شغّالة، وVertex AI فيها Gemini وClaude ونماذج مفتوحة، **لكن** Google تقول: "Endpoints don't guarantee data residency or in-region ML processing" | **[مؤكدة]** https://docs.cloud.google.com/vertex-ai/generative-ai/docs/learn/locations (2026-10-21) |
| 8 | الوصول للدمام: العميل بعنوان فوترة سعودي **مُلزم** بالشراء عبر CNTXT، والأجنبي يحتاج **Invoiced Billing** (لا يكفي الدفع بالبطاقة) | **[مؤكدة]** https://docs.cloud.google.com/docs/dammam-region-access (2026-10-21) |
| 9 | Azure Saudi Arabia East: "available to customers in November 2026"، أي **غير متاحة بعد**. AWS Saudi: "on track to launch in December 2026". Oracle عندها منطقتان شغّالتان (جدة منذ 2020، والرياض) | **[مؤكدة]** https://news.microsoft.com/source/emea/2026/08/microsoft-announces-saudi-arabia-east-datacenter-region-will-be-available-in-november-2026/ و https://english.aawsat.com/technology/5313287-aws-launch-first-cloud-infrastructure-region-saudi-arabia-december (2026-10-21). وOracle **[مؤكدة، ثانوي]** https://gulfnews.com/business/markets/oracle-boosts-saudi-arabias-ai-economy-with-new-public-cloud-region-1.103754361 (2026-10-21) |
| 10 | Anthropic API المباشر: `inference_geo` قيمتان فقط ("global" و"us")، وworkspace geo المتاح "us" فقط. ولا يوجد خيار شرق أوسطي | **[مؤكدة]** https://platform.claude.com/docs/en/build-with-claude/data-residency (2026-10-21) |
| 11 | NCA CCC وECC لا تُلزمنا مباشرة، لأن نطاقها الجهات الحكومية وCNI ومن يخدمها | **[مؤكدة]** نص CCC-2:2024: https://cdn.nca.gov.sa/api/files/public/upload/6d5408a3-d8e6-4e96-963b-2c7198e5b7c2_CCC-2-2024-EN-.pdf (2026-10-21) |
| 12 | لائحة CST للرسائل الاقتحامية: موافقة صريحة (لا تكفي سياسة الخصوصية)، وإثبات الموافقة، والإيقاف خلال 24 ساعة، ومنع الإرسال الترويجي من 10 مساءً إلى 9 صباحًا. ونصها لا يذكر WhatsApp صراحةً | **[مؤكدة]** https://www.stc.com.sa/content/dam/corporatesite/common/business/pdf/RegulationsforCurbingSPAMMessagesCallspdfEN.pdf (2026-10-21) |
| 13 | ZATCA: "All Non-Resident taxable persons must have one Tax Representative" (آخر تحديث للصفحة 30 أغسطس 2026). ولا حد تسجيل لغير المقيم الذي يبيع خدمات إلكترونية لغير المسجلين في VAT | الممثل **[مؤكدة]** https://zatca.gov.sa/en/RulesRegulations/VAT/Pages/tax-agentsrepresentatives.aspx (2026-10-21). أما الحد فهو **[مؤكدة، ثانوي]** https://www.fonoa.com/resources/country-tax-guides/saudi-arabia/tax-on-digital-services (2026-10-21) |
| 14 | الفوترة الإلكترونية (Fatoora) لا تنطبق على غير المقيم حتى لو كان مسجلًا في VAT. وعلى المقيم: الموجة 25 تشمل من تجاوزت إيراداته 187,500 ريال في أي سنة من 2022 إلى 2025، والربط مطلوب بحلول 1 فبراير 2027 | **[مؤكدة، ثانوي]** https://www.wafeq.com/en-sa/tax-and-reporting/saudi-arabia-e-invoicing-fatoorah-what-foreign-businesses-need-to-know و https://www.vatupdate.com/2026/07/27/zatca-announces-wave-25-of-e-invoicing-threshold-halved-to-sar-187500-integration-deadline-1-february-2027/ (2026-10-21) |
| 15 | لو كان الكيان أو الفريق في مصر: اللائحة التنفيذية لقانون حماية البيانات المصري (151/2020) صدرت في نوفمبر 2025، والمهلة تنتهي في **31 أكتوبر 2026**. وتشترط تراخيص، وممثلًا محليًا للجهات الأجنبية، وDPO | **[مؤكدة، ثانوي]** https://www.bakermckenzie.com/en/insight/publications/2026/10/egypt-personal-data-protection-law-update و https://cms.law/en/are/legal-updates/egypt-s-pdpl-executive-regulations-issued-one-year-compliance-countdown-begins (2026-10-21) |

---

## 2. PDPL: الحالة والتطبيق

### 2.1 الجدول الزمني

| التاريخ | الحدث | التصنيف |
|---|---|---|
| سبتمبر 2021 | إصدار PDPL بالمرسوم الملكي م/19 | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2021/09/saudi-arabia-issues-personal-data-protection-law (2026-10-21) |
| مارس 2023 | تعديل بالمرسوم م/148، وأُضيفت المصلحة المشروعة (legitimate interest) كأساس قانوني | **[مؤكدة، ثانوي]** https://compliancehub.wiki/saudi-pdpl-sdaia-active-enforcement-extraterritorial-scope-gulf-compliance-2026/ (2026-10-21) |
| 7 سبتمبر 2023 | نشر اللائحة التنفيذية ولائحة النقل خارج المملكة | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2023/09/saudi-arabia-issues-implementing-regulations (2026-10-21) |
| 14 سبتمبر 2023 | دخول النظام حيز النفاذ، مع سنة انتقالية | **[مؤكدة، ثانوي]** المصدر السابق |
| أغسطس 2024 | قواعد تعيين مسؤول حماية البيانات (DPO) وقواعد السجل الوطني للـcontrollers | **[مؤكدة، ثانوي]** https://www.lexis.ae/2024/08/29/ksa-issues-rules-for-appointing-personal-data-officers/ و https://turtl.tamimi.com/story/the-rules-governing-the-national-register-of-controllers-within-the-kingdom-expanded (2026-10-21) |
| سبتمبر 2024 | تعديل لائحة النقل، ونشر Saudi SCCs | **[مؤكدة، ثانوي]** https://insightplus.bakermckenzie.com/bm/data-technology/saudi-arabia-updates-data-transfer-regulations-and-introduces-the-first-set-of-standard-contractual-clauses (2026-10-21) |
| 14 سبتمبر 2024 | نهاية الفترة الانتقالية، وبداية التطبيق الكامل | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2026/03/enforcement-of-the-saudi-pdp-law (2026-10-21) |
| فبراير 2025 | إرشادات SDAIA لتقييم مخاطر النقل | **[مؤكدة، ثانوي]** https://www.kslaw.com/insights/articles/international-personal-data-transfers-under-saudi-arabias-data-protection-law (2026-10-21) |
| أبريل ومايو 2025 | استطلاع أول على تعديلات اللائحة، ولم يُعتمد | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2025/05/saudi-arabia-new-pdp-law-consultation و https://www.twobirds.com/en/insights/2026/japan/saudi-arabia-sdaia-returns-with-a-revised-draft-of-changes-to-the-data-protection-regulations (2026-10-21) |
| يناير 2026 | إعلان 48 قرار مخالفة | **[مؤكدة، ثانوي]** Clyde & Co وJD Supra (السابقان) |
| 6 أكتوبر إلى 5 نوفمبر 2026 | استطلاع ثانٍ على مسودة تعديلات اللائحة على منصة "استطلاع" | **[مؤكدة، ثانوي]** Clyde & Co وReed Smith وAl Tamimi (أكتوبر 2026) |
| 8 أكتوبر 2026 | فتح تسجيل الجهات الأجنبية في السجل الوطني | **[مؤكدة، ثانوي]** https://www.clydeco.com/en/insights/2026/10/sdaia-opens-controller-registration-for-overseas-o (2026-10-21) |

### 2.2 النطاق والتطبيق والعقوبات

| البند | التفاصيل | التصنيف |
|---|---|---|
| النطاق خارج الحدود | النظام يطال معالجة بيانات المقيمين في المملكة حتى لو تمت من خارجها، ومنها البيع لعملاء في المملكة | **[مؤكدة، ثانوي]** https://compliancehub.wiki/saudi-pdpl-sdaia-active-enforcement-extraterritorial-scope-gulf-compliance-2026/ و Clyde & Co (أكتوبر 2026). نص المادة 2 **غير مؤكد** |
| ممثل داخل المملكة للـcontroller الأجنبي | Clyde & Co (2021): الالتزام مؤجل حتى 5 سنوات. Goodwin (2022): يصفه كالتزام قائم | **متضارب، غير مؤكدة** في النص الحالي |
| القرارات | 48 قرارًا بإثبات مخالفات. الأسماء والمبالغ غير منشورة | **[مؤكدة، ثانوي]** Clyde & Co وJD Supra |
| أنواع المخالفات | معالجة بلا أساس قانوني، وإفصاح غير مشروع، وضعف الضوابط التقنية والتنظيمية | **[مؤكدة، ثانوي]** https://captaincompliance.com/education/saudi-arabias-data-protection-authority-enters-active-enforcement-phase-under-pdpl/ (2026-10-21) |
| ادعاء أن القرارات استهدفت تسويق WhatsApp بلا موافقة | تقوله مدونة مزود واحد فقط | **غير مؤكدة** https://watily.com/blog/en/saudi-pdpl-whatsapp-marketing-compliance-sme-2026/ (2026-10-21) |
| الغرامة الإدارية | حتى 5 ملايين ريال، تتضاعف عند التكرار، مع إمكانية نشر العقوبة | **[مؤكدة، ثانوي]** Clyde & Co (مارس 2026) |
| العقوبة الجنائية | إفشاء البيانات الحساسة عمدًا: سجن حتى سنتين و/أو غرامة حتى 3 ملايين ريال | **[مؤكدة، ثانوي]** compliancehub.wiki (السابق) |
| الإجراءات | 5 أيام للرد على إخطار SDAIA، والتظلم أمام المحكمة خلال 60 يومًا | **[مؤكدة، ثانوي]** Clyde & Co (مارس 2026) |

**[افتراض]:** 48 قرارًا في سنة ونصف تقريبًا تعني تطبيقًا حقيقيًا لكنه غير كثيف. والخطر العملي على تاجر صغير هو الشكوى من رسالة تسويقية بلا موافقة، لا الغرامة القصوى.

---

## 3. الأدوار: Controller مقابل Processor

| السيناريو | الدور المرجّح | التصنيف |
|---|---|---|
| بيانات المتسوقين لدى التاجر (المحادثات والطلبات والسلات المتروكة) | **التاجر = Controller**، و**CODEX = Processor** يعالج نيابة عنه ووفق تعليماته | **[افتراض]** مبني على تعريفات PDPL كما تشرحها المصادر الثانوية. يحتاج تأكيد محامٍ |
| بيانات التاجر نفسه (حسابات المستخدمين، والفوترة، وبيانات موظفي التاجر) | **CODEX = Controller** | **[افتراض]** |
| استخدام CODEX بيانات المتسوقين لأغراضها هي (تدريب نموذج مشترك بين المستأجرين، أو benchmarks عابرة للمتاجر، أو "ملف عميل موحد" بين أكثر من متجر) | CODEX تصبح **Controller** (أو joint controller) لهذه المعالجة، وتحتاج أساسًا قانونيًا مستقلًا | أن المعالج الذي يخرج عن التعليمات يُعامل كـcontroller **[مؤكدة، ثانوي]** حسب ملخص Baker McKenzie في نتائج البحث: https://resourcehub.bakermckenzie.com/en/resources/global-data-and-cyber-handbook/emea/saudi-arabia/topics/data-processors (الصفحة رجعت 403). التطبيق على CODEX **[افتراض]** |
| مزودو LLM، وMeta (WhatsApp Cloud API)، ومزود الاستضافة | **Sub-processors** لـCODEX | **[افتراض]** |
| سلة (Salla) | منصة التاجر، وليست طرفًا في معالجتنا. لكن سياسة متجر التطبيقات تُلزم المطور بالامتثال لـPDPL، وبعدم مشاركة أو بيع البيانات بلا موافقة صريحة | **[مؤكدة]** https://partners.salla.com/legal/apps-policy (المادتان 3 و8، 2026-10-21) |

نقاط أساسية:
- PDPL يركز على الـcontroller، واللائحة التنفيذية تنقل الالتزامات إلى المعالج عن طريق العقد. **[مؤكدة، ثانوي]** (ملخص Baker McKenzie، المصدر السابق).
- الـcontroller ملزم باختيار معالج يقدم "ضمانات كافية"، وبعقد معه. **[مؤكدة، ثانوي]** https://www.cyberday.ai/requirement/pdpl-article-17-1-choosing-the-processor (2026-10-21). رقم المادة **غير مؤكد**: مصدر يقول المادة 17 من اللائحة، ونموذج عقد Lotame يذكر المادة 25 من النظام والمادة 19 من اللائحة: https://www.lotame.com/wp-content/uploads/2025/10/2025.10.01_dpa-dcua_audi-arabia-schedule.pdf (2026-10-21).
- **[اقتراح]:** **DPA سعودي** (click-through عند تثبيت التطبيق من سلة)، يحدد الأدوار، ويضع قائمة sub-processors، ويُلزمنا بإخطار التاجر بأي اختراق خلال 24 ساعة كحد أقصى، حتى يلحق التاجر مهلة الـ72 ساعة لـSDAIA ومهلة الـ3 أيام لوزارة التجارة. ويُرفق به Saudi SCC (C2P) لو كانت المعالجة خارج المملكة.

---

## 4. الالتزامات التشغيلية تحت PDPL

| الالتزام | الوضع الحالي | المسودة (أكتوبر 2026، غير نافذة) | يقع على مين؟ | التصنيف |
|---|---|---|---|---|
| إخطار SDAIA بالاختراق | خلال 72 ساعة عند احتمال الضرر | 72 ساعة "regardless of whether harm is likely"، وإخطار صاحب البيانات يبقى مشروطًا بالضرر | التاجر (Controller). CODEX تُخطر التاجر تعاقديًا | الحالي **[مؤكدة، ثانوي]** Clyde & Co (2023)، والمسودة **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| طلبات أصحاب البيانات | 30 يومًا، قابلة للتمديد | الطلب الذي لا يُرد عليه يُعتبر مرفوضًا، ويحق لصاحبه الشكوى. ونافذة الشكوى 90 يومًا | التاجر، وCODEX تحتاج أدوات تصدير وحذف | **[مؤكدة، ثانوي]** Clyde & Co وReed Smith (أكتوبر 2026). نافذة الـ90 يومًا من ملخص Al Tamimi في نتائج البحث |
| DPO | إلزامي لجهة عامة تعالج على نطاق واسع، أو لجهة أنشطتها الأساسية مراقبة منتظمة ومنهجية، أو تعالج بيانات حساسة. يجوز أن يكون متعاقدًا خارجيًا | يجب توثيق التعيين، وتسجيل بيانات الـDPO على منصة SDAIA | التاجر | **[مؤكدة، ثانوي]** https://securiti.ai/saudi-arabia-rules-for-appointing-personal-data-protection-officer (2026-10-21) وClyde & Co (أكتوبر 2026) |
| سجل أنشطة المعالجة (ROPA) | مطلوب من الـcontroller بحد أدنى من المحتوى: بيانات التواصل، والأغراض، وفئات أصحاب البيانات، والمستلمون، والنقل للخارج، ومدد الاحتفاظ. ويُحفظ طوال المعالجة و"حتى 5 سنوات" بعدها | تقليل البنود الإلزامية، والحفظ طوال المعالجة **و5 سنوات إضافية** | التاجر، وCODEX يمكن أن توفر ROPA جاهزًا لنشاط المنصة | **[مؤكدة، ثانوي]** https://securiti.ai/assessments/saudi-arabia-personal-data-protection-law-pdpl/ وملخص Al Tamimi في نتائج البحث (2026-10-21). رقم المادة **غير مؤكد** |
| DPIA | محفزات محدودة | محفزات جديدة: القُصّر، والمراقبة المنهجية أو واسعة النطاق، و"where emerging technologies are used"، والقرارات المؤتمتة المؤثرة. ولا تعريف لـ"emerging technology" | التاجر | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) و https://gowlingwlg.com/en/insights-resources/articles/2026/saudi-arabias-proposed-pdpl-amendments (2026-10-21) |
| الرد على طلبات SDAIA | غير محدد | خلال 20 يوم عمل | الكل | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| أمن المعلومات | ضوابط "مناسبة" | الجهات الخاضعة لـNCA تلتزم بمعاييرها، وغيرها يلتزم بـ"recognized cybersecurity standards" | الطرفان، والمعالج تعاقديًا | **[مؤكدة، ثانوي]** Reed Smith (أكتوبر 2026) |
| سياسة الخصوصية | موجودة | لغة "clear, simplified and comprehensible" تناسب الجمهور، وغالبًا بالعربية | التاجر وCODEX | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |

**[افتراض]:** الـAI Sales Agent الذي يحلل سلوك المتسوق ويقرر من يستلم عرضًا قد يُصنَّف "مراقبة منتظمة ومنهجية" و"قرارًا مؤتمتًا" و"تقنية ناشئة". هذا يرفع احتمال وجوب DPIA على التاجر، وعلينا لو اعتُبرنا controller في أي جزء.

### 4.1 السجل الوطني للـControllers

| البند | التفاصيل | التصنيف |
|---|---|---|
| من يسجّل الآن | الجهات العامة، والجهات التي نشاطها الأساسي معالجة البيانات الشخصية، والجهات التي تعالج بيانات حساسة | **[مؤكدة، ثانوي]** https://turtl.tamimi.com/story/the-rules-governing-the-national-register-of-controllers-within-the-kingdom-expanded (2026-10-21) |
| المسودة | يُضاف لمن يسجّل: من يعالج بيانات ناقصي الأهلية، و**كل controller ينقل بيانات شخصية خارج المملكة أو يُفصح عنها** | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| مدة الشهادة | حتى 5 سنوات | **[مؤكدة، ثانوي]** Al Tamimi (السابق) |
| الجهات الأجنبية | متاح منذ 8 أكتوبر 2026، بشرط ممثل مفوّض، وسجل تجاري ساري، وتوثيق عبر وزارة الخارجية | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| هل يسجّل الـProcessor؟ | لم أجد نصًا يلزم المعالج بالتسجيل | **غير مؤكدة** |

**[افتراض]:** لو اعتُمدت المسودة، فكل تاجر يرسل رسائل WhatsApp عبرنا "ينقل للخارج" (لأن Meta لا تخزن في السعودية، كما في القسم 5.3)، فيلزمه التسجيل، **حتى لو استضفنا كل شيء آخر في المملكة**. هذا احتكاك يخص كل مزودي WhatsApp في السوق، لا CODEX وحدها. وتأثيره التنافسي محايد غالبًا، لكنه يُلزمنا بمساعدة التاجر في التسجيل.

---

## 5. نقل البيانات خارج المملكة

### 5.1 الإطار الحالي (لائحة النقل المعدّلة، سبتمبر 2024)

| العنصر | المحتوى | التصنيف |
|---|---|---|
| الأغراض المسموحة (المادة 29 من النظام) | تنفيذ اتفاقية تكون المملكة طرفًا فيها، أو خدمة مصالح المملكة، أو **تنفيذ التزام يكون صاحب البيانات طرفًا فيه**، أو أغراض أخرى تحددها اللائحة | **[مؤكدة، ثانوي]** King & Spalding (السابق) |
| الشروط | ألا يضر النقل بالأمن الوطني، ومستوى حماية ملائم في بلد الاستقبال، وتقليل البيانات (data minimization) | **[مؤكدة، ثانوي]** المصدر السابق |
| قائمة الملاءمة | **لم تُنشر**. King & Spalding تنصح عمليًا بالاقتصار على الدول التي تعتبرها المفوضية الأوروبية ملائمة | **[مؤكدة، ثانوي]** المصدر السابق |
| الاستثناءات (بضمانة مناسبة + risk assessment) | العمليات المركزية لمجموعة متعددة الجنسيات، و**خدمة أو منفعة مباشرة لصاحب البيانات**، والبحث العلمي | **[مؤكدة، ثانوي]** المصدر السابق |
| الضمانات المناسبة | Saudi SCCs، وBinding Common Rules (داخل المجموعة الواحدة)، وشهادة اعتماد | **[مؤكدة، ثانوي]** Mayer Brown وBaker McKenzie (2024) |
| SCCs | 4 نماذج (C2P وC2C وP2C وP2P)، ممنوع تعديلها | **[مؤكدة، ثانوي]** Mayer Brown (2024) |
| Risk assessment | مطلوب عند الاعتماد على ضمانة مناسبة، أو عند نقل بيانات حساسة بشكل مستمر أو واسع | **[مؤكدة، ثانوي]** المصدر السابق |

### 5.2 هل نداء LLM API لمزود في أمريكا أو أوروبا نقل عبر الحدود؟

- **[افتراض] (قوي):** نعم، لو احتوى الـprompt على بيانات شخصية (اسم، رقم، عنوان، نص محادثة يمكن ربطه بشخص) وعولج خارج المملكة. لم أجد إرشادًا رسميًا من SDAIA خاصًا بالـGenerative AI، لذلك الحكم الرسمي **غير مؤكد**. Gowling WLG تطرح السؤال صراحة: ما معنى "تخزين" البيانات داخل المملكة لخدمة AI موزعة؟ **[مؤكدة، ثانوي]** (المصدر السابق).
- **سلسلة النقل [افتراض]:** التاجر (controller) ← CODEX (processor) ← مزود LLM (sub-processor). يعني ذلك SCC من نوع C2P بيننا وبين التاجر لو كنا خارج المملكة، وP2P بيننا وبين مزود الـLLM.
- **[افتراض]:** مزودو الـLLM الكبار غالبًا لن يوقّعوا Saudi SCCs غير معدّلة مع عميل صغير. الحل العملي تقليل البيانات الشخصية في الـprompt، أو نموذج مستضاف في المملكة.
- **غرض "تنفيذ التزام يكون صاحب البيانات طرفًا فيه" [افتراض]:** الرد على استفسار المتسوق عن طلبه قد يندرج تحته، أما الحملات التسويقية فغالبًا لا.

### 5.3 قناة WhatsApp نفسها

| البند | التفاصيل | التصنيف |
|---|---|---|
| دول التخزين المحلي (data at rest) في Cloud API | AU وID وIN وJP وSG وKR وDE وCH وGB وBR و**BH** وZA و**AE** وCA. **السعودية غير مدرجة** | **[مؤكدة]** https://developers.facebook.com/docs/whatsapp/cloud-api/reference/registration (2026-10-21) |
| البيانات أثناء المعالجة | "message content may be stored on Meta data centers internationally while being processed" | **[مؤكدة]** https://developers.facebook.com/docs/whatsapp/cloud-api/overview/local-storage (2026-10-21) |
| التخزين الافتراضي بدون local storage | في الولايات المتحدة. ومدة البيانات أثناء الاستخدام تصل إلى 60 دقيقة (Cloud API) | **[مؤكدة، ثانوي]** https://www.infobip.com/docs/whatsapp/compliance/data-privacy و https://guides.clickatell.com/release-notes/q3-jul-sep-2024/channel-updates/whatsapp-data-localization (2026-10-21) |
| اختيار الدولة | غير قابل للتغيير بعد التفعيل حسب Clickatell | **[مؤكدة، ثانوي]** المصدر السابق |

**[افتراض]:** لا يوجد مسار رسمي لتوطين محادثات WhatsApp في السعودية اليوم. أفضل المتاح هو local storage في الإمارات أو البحرين، وهو تقليل للمخاطر لا امتثال لـ"التخزين داخل المملكة". **[اقتراح]:** نسجّل أرقام التجار مع `data_localization_region = AE` بعد مراجعة قانونية (القرار غير قابل للتراجع)، ونوثق في الـDPA أن Meta sub-processor خارج المملكة.

### 5.4 خيارات تشغيل LLM داخل المنطقة (حتى 2026-10-21)

| الخيار | الوضع | ضمان المعالجة داخل المملكة؟ | التصنيف |
|---|---|---|---|
| Vertex AI على me-central2 (الدمام) | Gemini (منها 2.5 و3.x و4) وClaude ونماذج Mistral وGrok ونماذج مفتوحة (Llama وQwen وgpt-oss وغيرها) مدرجة في الدمام | **لا**: "Endpoints don't guarantee data residency or in-region ML processing". وللنماذج الشريكة: "Google serves requests from the region that you specified" | **[مؤكدة]** https://docs.cloud.google.com/vertex-ai/generative-ai/docs/learn/locations (2026-10-21) |
| OpenAI | Inference residency في الإمارات وأوروبا وأمريكا لعملاء API وEnterprise المؤهلين، مع قيود على النماذج. **لا يوجد خيار سعودي** | لا | **[مؤكدة]** https://help.openai.com/en/articles/9903489-data-residency-and-inference-residency (كما ظهرت في نتائج البحث، 2026-10-21). عدم وجود خيار سعودي **غير مؤكد** (لم أجده) |
| Anthropic API المباشر | `inference_geo` = "global" أو "us"، والـ"us" بسعر 1.1x. والـworkspace geo "us" فقط | لا | **[مؤكدة]** https://platform.claude.com/docs/en/build-with-claude/data-residency (2026-10-21) |
| Azure (OpenAI / Foundry) | Saudi Arabia East تفتح في نوفمبر 2026، والإعلان يقول "supported Microsoft cloud and AI services" بلا تسمية | غير معروف | **[مؤكدة]** إعلان Microsoft (السابق). الخدمات **غير مؤكدة** |
| AWS Bedrock | المنطقة السعودية في ديسمبر 2026. ALLaM من HUMAIN "will soon be available through Amazon Bedrock" | غير معروف | **[مؤكدة]** الشرق الأوسط (السابق) |
| HUMAIN / ALLaM | HUMAIN IQ تعلن APIs بنظام pay-as-you-go | غير معروف | **[مؤكدة، ثانوي]** https://www.humain.com/en/iq.html (2026-10-21). الجودة والسعر وموقع المعالجة **غير مؤكدة** |
| Self-host نموذج مفتوح على GPU سعودي | ممكن تقنيًا (OCI أو GCP Dammam) | نعم (نحن نتحكم) | التكلفة مرتفعة لمرحلة MVP **[افتراض]** |

### 5.5 وسائل تقليل المخاطر (Mitigations)

| الوسيلة | الفائدة | التصنيف |
|---|---|---|
| **فصل الهوية عن الـLLM:** الـLLM يرى نص الرسالة والكتالوج فقط، والمطابقة والهوية في قاعدة بياناتنا | أقوى تصميم وأرخصه | **[اقتراح]** |
| Pseudonymization قبل الـprompt (استبدال الاسم والرقم والعنوان برموز ثم إرجاعها) | تقلل الخطر، لكن البيانات المستعارة تبقى بيانات شخصية حسب تعريف اللائحة | التعريف **[مؤكدة، ثانوي]** https://www.mayerbrown.com/en/insights/publications/2023/08/saudi-data-protection-law-updates-on-implementing-regulations-and-data-transfer--what-you-need-to-know (2026-10-21). عدم كفايتها وحدها **[افتراض]** |
| Anonymization للتحليلات المجمعة | البيانات المجهّلة فعلًا تخرج غالبًا من نطاق النظام | **[افتراض]** |
| Zero data retention وعدم التدريب لدى المزود، مع DPA | يقلل المخاطر، لكنه لا يلغي كونها نقلًا | **[افتراض]** |
| توجيه الطلبات لـVertex Dammam | يقلل الخروج الفعلي، بدون ضمان تعاقدي | **[اقتراح]** نطلب تأكيدًا مكتوبًا من Google/CNTXT |
| Saudi SCC (P2P) مع مزود الـLLM | الأداة القانونية الصحيحة، وقابليتها العملية مشكوك فيها | **[افتراض]** |

---

## 6. التسويق والموافقة والأطفال

### 6.1 PDPL واللائحة

| البند | الوضع | التصنيف |
|---|---|---|
| المبدأ | موافقة مسبقة قبل إرسال مواد إعلانية أو توعوية، مع آلية إلغاء واضحة | **[مؤكدة، ثانوي]** https://turtl.tamimi.com/story/law-update-issue-367-saudi-arabia-and-competition/page/11 (2026-10-21) |
| الاستثناء الحالي (المادة 28 من اللائحة) | يُفهم أنه يعفي من الموافقة عند وجود **تفاعل سابق** بين الـcontroller وصاحب البيانات | **[مؤكدة، ثانوي]** https://iapp.org/news/a/saudi-arabia-publishes-final-personal-data-protection-law (2026-10-21) |
| شروط الموافقة | حرة بلا تضليل، ويختار المستلم أنواع الرسائل، وموثقة بشكل قابل للتحقق | **[مؤكدة، ثانوي]** https://www.cyberday.ai/requirement/pdpl-article-28-1-2-processing-data-for-advertising-or-awareness-purposes (2026-10-21) |
| المسودة (المادتان 28 و29) | التحقق من صحة الموافقة حتى مع التفاعل السابق، والاحتفاظ بإثباتها، وآلية سحب واضحة. ويمتد النطاق لكل "processing for marketing purposes"، وعند السحب تتوقف المعالجة وتُحذف البيانات الناتجة "without undue delay" | **[مؤكدة، ثانوي]** Clyde & Co وReed Smith (أكتوبر 2026) |
| نظام التجارة الإلكترونية | لا يجوز استخدام البيانات للتسويق بلا موافقة مسبقة من المستهلك | **[مؤكدة، ثانوي]** https://www.globalcompliancenews.com/2020/03/16/saudi-arabia-regulates-e-commerce/ (2026-10-21) |

**ملاحظة لـAbandoned Cart Recovery [افتراض]:** رسالة استرجاع السلة أقرب إلى "ترويجية" منها إلى "خدمية"، خاصة لو فيها خصم. وحذف البيانات الناتجة عند سحب الموافقة (المسودة) يمس الـsegments والـscores التي يبنيها الـAI. **[اقتراح]:** نبني من اليوم على أن الموافقة الصريحة مطلوبة (checkbox عند الـcheckout في سلة، أو opt-in على WhatsApp)، مع consent ledger، وحذف متسلسل (cascade) للبيانات المشتقة عند السحب.

### 6.2 لائحة CST للحد من الرسائل الاقتحامية

| البند | النص | التصنيف |
|---|---|---|
| المرجع | "Regulations for Curbing SPAM Messages & Calls"، الإصدار الثالث، أكتوبر 2022 | **[مؤكدة]** https://www.stc.com.sa/content/dam/corporatesite/common/business/pdf/RegulationsforCurbingSPAMMessagesCallspdfEN.pdf (2026-10-21) |
| تعريف الرسالة الإلكترونية | "such as e-mail, SMS, MMS, Flash SMS, or fax"، ولا ذكر لتطبيقات OTT | **[مؤكدة]** البندان 2.2 و3.1 من النص السابق |
| الموافقة | خيار موافقة صريحة، و"the consent contained in privacy policies and service contracts is not considered"، والمرسل ملزم بإثبات الموافقة | **[مؤكدة]** البند 4.6.6.1 |
| الإلغاء | في أي وقت، والتوقف خلال 24 ساعة، ورسالة تأكيد | **[مؤكدة]** البنود 4.6.6.2 إلى 4.6.6.4 |
| أوقات الإرسال | ممنوع الترويجي والتوعوي من 10:00 مساءً إلى 9:00 صباحًا، وفي رمضان من 1:00 صباحًا إلى 12:00 ظهرًا | **[مؤكدة]** البند 4.6.8 |
| Sender ID | لاحقة أو بادئة "-AD" للرسائل الترويجية عبر SMS | **[مؤكدة، ثانوي]** https://www.unifonic.com/blogs/a-short-guide-to-the-sms-compliance-landscape-in-ksa (2026-10-21) |
| هل تنطبق على WhatsApp؟ | النص لا يذكره، ومزودو الخدمة يرون أن القيد الفعلي على WhatsApp يأتي من PDPL وسياسة Meta | **غير مؤكدة**. ورأي المزودين **[مؤكدة، ثانوي]** https://routemobile.com/blog/whatsapp-business-api-in-saudi-arabia-features-limits-and-ksa-compliance-guide/ (2026-10-21) |

**[اقتراح]:** نطبق قواعد CST (الأوقات، والإثبات، والإلغاء خلال 24 ساعة) على WhatsApp كإعداد افتراضي. التكلفة منخفضة، وهي تحمي من تغير التفسير.

### 6.3 سياسة WhatsApp (Meta)

- الـopt-in يجب أن يوضح اسم النشاط الذي يوافق المستخدم على استقبال رسائله، ويجب الالتزام بالقانون المحلي. والجودة المنخفضة المستمرة تؤدي إلى تقييد الإرسال. **[مؤكدة]** https://developers.facebook.com/docs/whatsapp/overview/getting-opt-in (2026-10-21)

### 6.4 بيانات الأطفال

| البند | التفاصيل | التصنيف |
|---|---|---|
| القاعدة | موافقة الولي مع التحقق من الولاية، وألا تضر المعالجة بمصلحة القاصر | **[مؤكدة، ثانوي]** https://childreninthedigitalworld.twobirds.com/home/saudi-arabia (2026-10-21) |
| السن | PDPL لا يحدد سنًا. Bird & Bird تشير إلى 18 من مرسوم آخر، ومصادر تجارية تقول 13 بلا سند | 18 **[مؤكدة، ثانوي]**، و13 **غير مؤكدة** |
| المسودة | بيانات القُصّر تصبح محفزًا لـDPIA وللتسجيل في السجل الوطني | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |

**[اقتراح]:** لا نستهدف القُصّر، والـAI Agent لا يجمع العمر، والتاجر يتعهد في الـDPA بعدم توجيه حملات لأرقام يعرف أنها لقُصّر.

---

## 7. نظام التجارة الإلكترونية (وزارة التجارة)

| الالتزام | التفاصيل | يقع على مين؟ | التصنيف |
|---|---|---|---|
| النطاق | مقدمو الخدمة داخل المملكة، ومن خارجها لو يخدمون مستهلكين فيها | التاجر أساسًا | **[مؤكدة، ثانوي]** globalcompliancenews (السابق) |
| إخطار الاختراق | إبلاغ **المستهلك والوزارة** خلال 3 أيام من العلم، بنطاق الاختراق وآثاره والإجراءات المتخذة. والإبلاغ لا يعفي من المسؤولية | التاجر، وCODEX تساعده بإخطار سريع | **[مؤكدة]** صفحة الوزارة https://mc.gov.sa/en/Communityparticipation/Pages/01.aspx (كما ظهرت في نتائج البحث، 2026-10-21)، و**[مؤكدة، ثانوي]** https://mondaq.co.uk/saudiarabia/new-technology/936424/call-to-action-the-new-executive-regulations-of-the-saudi-e-commerce-law (2026-10-21) |
| الاحتفاظ | لا تُحفظ البيانات إلا للغرض اللازم لتنفيذ الاتفاق | التاجر، وCODEX تحتاج retention قابل للضبط | **[مؤكدة، ثانوي]** globalcompliancenews (السابق) |
| العقوبات | إنذار، أو غرامة حتى مليون ريال، أو إيقاف النشاط | التاجر | **[مؤكدة، ثانوي]** (ملخص شركة امتثال في نتائج البحث). الرقم يحتاج تأكيدًا من النص |

**[افتراض]:** الـAI Agent يتكلم باسم التاجر، فأي وعد خاطئ عن السعر أو سياسة الإرجاع مخالفة على التاجر، ومخاطرة سمعة وتعاقد علينا. هذا يدعم Rule 9.

---

## 8. NCA وCST: هل تلزمنا ضوابط الأمن السيبراني والسحابة؟

| الإطار | النطاق | هل يلزم CODEX؟ | التصنيف |
|---|---|---|---|
| CCC-2:2024 | "any government agency... as well as all private sector entities owning, operating or hosting Critical National Infrastructures"، ومزودو السحابة الذين يخدمونها. والباقون "strongly encouraged" | **لا**، طالما لا نخدم جهات حكومية أو CNI | **[مؤكدة]** نص CCC-2:2024 (السابق) |
| ECC-2:2024 | الجهات الحكومية والـCNI | **لا** مباشرة | **[مؤكدة، ثانوي]** https://www.trio.so/blog/nca-compliance (2026-10-21) |
| NCNICC-1:2025 | القطاع الخاص غير الحساس: الفئة A (65 ضابطًا) والفئة B (26 ضابطًا). اعتُمدت في 28 ديسمبر 2025 حسب أغلب المصادر، ولا يوجد موعد إلزام منشور. وقراءة لنص الوثيقة تقول إنها تُلزم الجهات **التي تُخطرها الهيئة** فقط | **لا** على كيان أجنبي. و**ربما** على كيان سعودي لو أُخطر. وعملاؤنا سيطلبون ضمانات أمنية تعاقدية | النطاق والفئات **[مؤكدة، ثانوي]** https://cms.law/en/omn/legal-updates/kingdom-of-saudi-arabia-issues-new-cybersecurity-controls-for-the-private-sector و https://digitalpolicyalert.org/change/17805-cybersecurity-regulation-in-non-cni-private-sector-entities-cybersecurity-controls-ncnicc-12025 (2026-10-21). "الإخطار" وغياب الموعد **[مؤكدة، ثانوي]** https://waqisec.com/en/guides/ncnicc/ (2026-10-21). غرامة 25 مليون المتداولة **غير مؤكدة** |
| CST Cloud Computing Services Provisioning Regulations | تسجيل مزودي السحابة في 4 فئات (Qualification وA وB وC) | **غالبًا لا**: نحن SaaS فوق مزود سحابة | الإطار **[مؤكدة]** https://my.gov.sa/en/services/25775 (2026-10-21). عدم انطباقه علينا **[افتراض]** |

**[اقتراح]:** لا نسعى لشهادة NCA في MVP. نبني على ضوابط ISO 27001 أو SOC 2 كمرجع داخلي، لأن المسودة تشير إلى "recognized cybersecurity standards".

---

## 9. خيارات توطين البيانات (Data Residency) حتى 2026-10-21

### 9.1 المزودون

| المزود | المنطقة السعودية | الحالة | ملاحظات الوصول | التصنيف |
|---|---|---|---|---|
| Google Cloud | Dammam (me-central2) | **شغّالة** منذ نوفمبر 2023، ومرخصة CST Class C | الشراء عبر CNTXT إلزامي للعميل السعودي، وInvoiced Billing للأجنبي. وفيها Vertex AI | **[مؤكدة]** https://docs.cloud.google.com/docs/dammam-region-access (2026-10-21) |
| Oracle (OCI) | Jeddah وRiyadh | **شغّالتان**: جدة منذ 2020، والرياض يستضيفها center3. ومنطقة NEOM مخطط لها | Oracle تعلن تسعيرًا موحدًا في كل المناطق (ادعاء مزود). ولا يوجد OpenAI أو Anthropic مُدار على OCI جدة | الحالة **[مؤكدة، ثانوي]** https://gulfnews.com/business/markets/oracle-boosts-saudi-arabias-ai-economy-with-new-public-cloud-region-1.103754361 و https://www.arabnews.com/node/2563436/amp (2026-10-21). التسعير الموحد **[مؤكدة كادعاء مزود]**. خدمات الـLLM **غير مؤكدة** |
| Microsoft Azure | Saudi Arabia East (المنطقة الشرقية، 3 AZs) | **نوفمبر 2026**، غير متاحة بعد | الخدمات عند الإطلاق غير محددة | **[مؤكدة]** إعلان Microsoft (السابق) |
| AWS | منطقة سعودية (استثمار أكثر من 5.3 مليار دولار) | **ديسمبر 2026**، غير متاحة بعد | Bedrock مذكور كمثال، وALLaM "قريبًا" | **[مؤكدة]** الشرق الأوسط (السابق) |
| Huawei Cloud | الرياض (أُطلقت سبتمبر 2023، 3 AZs، داخل منشأة center3) | شغّالة، وحاصلة على Class C | — | Class C **[مؤكدة]** https://www.huaweicloud.com/intl/en-us/securecenter/compliance/compliance-center/ksa-classc.html (2026-10-21). التفاصيل **[مؤكدة، ثانوي]** https://vision2030.ai/sectors/technology/saudi-arabia-cloud-regions/ (2026-10-21) |
| stc cloud | مراكز Tier IV في الرياض وجدة | تعلن حصولها على Class C وISO 27017 وCSA STAR | الأسعار غير منشورة في ما وجدته. والتسويق موجه للمؤسسات | **[مؤكدة كادعاء مزود]** https://cloud.stc.com.sa/ (2026-10-21). الأسعار وبرامج الشركات الناشئة **غير مؤكدة** |
| center3 (تابعة لـstc) | مراكز بيانات محايدة، وتستضيف Oracle Riyadh وHuawei وOracle Alloy | بنية تحتية، وليست سحابة عامة لـSaaS صغير | — | **[مؤكدة]** تقرير stc السنوي 2025: https://www.stc.com/content/dam/groupsites/en/pdf/stc2025-annual-report-en.pdf (كما ظهر في نتائج البحث، 2026-10-21) |
| Alibaba Cloud (SCCC) | الرياض (مشروع 2022 برأس مال 894 مليون ريال) | الوضع الحالي **غير مؤكد** | — | **[مؤكدة، ثانوي]** businesswire 2022. الحالة الحالية **غير مؤكدة** |

### 9.2 التكلفة مقارنة بأوروبا (أمثلة، أسعار طرف ثالث)

| البند | Dammam (me-central2) | Belgium (europe-west1) | Frankfurt (europe-west3) | Doha (me-central1) | الفرق | التصنيف |
|---|---|---|---|---|---|---|
| Compute e2-standard-2 (بالساعة، on-demand) | 0.1072$ | 0.0737$ | 0.0863$ | 0.0814$ | الدمام أغلى من بلجيكا بنحو 45%، ومن فرانكفورت بنحو 24% | **[مؤكدة، ثانوي]** https://gcloud-compute.com/e2-standard-2.html (2026-10-21) |
| Cloud SQL db-standard-2 (شهريًا) | حوالي 158$ | حوالي 99$ | حوالي 118$ | — | +34% إلى +60% | **[مؤكدة، ثانوي]** https://www.bytebase.com/dbcost/cloudsql/instance/db-standard-2.md (2026-10-21) |
| pd-ssd (لكل GB شهريًا) | 0.272$ | — | 0.204$ | — | حوالي +33% | **[مؤكدة، ثانوي]** https://gcloud-compute.com/pd-ssd.html (2026-10-21) |

**[افتراض]:** لـMVP صغير (قاعدة بيانات مُدارة صغيرة، و2-3 خوادم تطبيق، وRedis، وObject Storage)، الفرق بين الدمام وأوروبا تقريبًا 80-200 دولار شهريًا. مبلغ صغير مقارنة بمخاطر المسودة، لكن **عائق الوصول (Invoiced Billing أو CNTXT) أكبر من عائق السعر**. أما OCI فقد تكون أرخص إن صح التسعير الموحد، لكن نظامها البيئي (managed Postgres وRedis وLLM) أضعف لفريق صغير.

---

## 10. تأسيس الشركة والضرائب

### 10.1 البيع من مصر أو الخارج مقابل كيان سعودي

| البند | من الخارج (مصر أو غيرها) | كيان سعودي (ترخيص MISA + سجل تجاري) | التصنيف |
|---|---|---|---|
| ملكية أجنبية 100% | — | ممكنة في أغلب القطاعات، ومنها التقنية | **[مؤكدة، ثانوي]** https://www.middleeastbriefing.com/news/types-of-business-licenses-in-saudi-arabia-2025/ (2026-10-21) |
| التكلفة والوقت | منخفضة | تقديرات مزودي خدمات التأسيس: 40-60 ألف ريال، و8-16 أسبوعًا | **[مؤكدة كادعاء مزود]** https://expandway.sa/misa-license-fees-saudi-arabia/ (2026-10-21). الأرقام الدقيقة **غير مؤكدة** |
| تسجيل VAT | إلزامي من أول توريد لعميل **غير مسجل** في VAT، خلال 30 يومًا، بلا حد أدنى. والبيع B2B لعميل مسجل = reverse charge على العميل | إلزامي عند تجاوز 375 ألف ريال سنويًا | غير المقيم **[مؤكدة، ثانوي]** Fonoa (السابق) و https://lookuptax.com/docs/country/saudi-arabia-vat-guidelines-indirect-tax (2026-10-21). حد 375 ألف **[مؤكدة، ثانوي]** https://simplyvat.com/vat-saudi-arabia/ (2026-10-21) |
| الممثل الضريبي | ZATCA: "All Non-Resident taxable persons must have one Tax Representative"، ويتحمل معه المسؤولية بالتضامن. ويُقبل أعضاء SOCPA ومكاتب المحاماة دون شروط إضافية | لا يلزم | **[مؤكدة]** https://zatca.gov.sa/en/RulesRegulations/VAT/Pages/tax-agentsrepresentatives.aspx (2026-10-21). ويتعارض مع Fonoa وVATCalc (القسم 12) |
| الفوترة الإلكترونية (Fatoora) | **خارج النطاق** لغير المقيم حتى لو كان مسجلًا في VAT. والإقامة تُحدد بالوجود الدائم (سجل تجاري أو مكتب) | إلزامية بالموجات. الموجة 24: أكثر من 375 ألف ريال (2022-2024)، والربط بحلول 30 يونيو 2026. الموجة 25: أكثر من 187,500 ريال في أي سنة من 2022 إلى 2025، والربط بحلول 1 فبراير 2027. وZATCA تُخطر قبل 6 أشهر على الأقل | غير المقيم **[مؤكدة، ثانوي]** من مصدرين: Wafeq (السابق) و https://mercans.com/glossary/zatca-e-invoicing-fatoora/ (2026-10-21). الموجات **[مؤكدة، ثانوي]** https://www.vatupdate.com/2026/07/27/zatca-announces-wave-25-of-e-invoicing-threshold-halved-to-sar-187500-integration-deadline-1-february-2027/ و https://kpmg.com/sa/en/insights/tax-insights/tax-flash-announcement-of-24-wave-for-e-invoicing-phase-2-implementation.html (2026-10-21) |
| البيع عبر Salla App Store | الدفع للتطبيقات المدفوعة يتم عبر سلة "وتخضع للعمولات والنسب المعتمدة"، وتُصرف المستحقات بعد نحو 15 يومًا. والسياسة **لا تذكر** VAT أو الفواتير أو نسبة العمولة | نفس الشيء | **[مؤكدة]** https://partners.salla.com/legal/apps-policy (المادة 5، 2026-10-21). من يتحمل VAT عن المطور **غير مؤكد** |
| السجل الوطني لـSDAIA | ممكن للأجنبي، ويتطلب ممثلًا مفوّضًا وسجلًا تجاريًا وتوثيق MOFA | مباشر | **[مؤكدة، ثانوي]** Clyde & Co (أكتوبر 2026) |
| Google Cloud Dammam | Invoiced Billing | عبر CNTXT إلزاميًا | **[مؤكدة]** وثيقة Google (السابقة) |
| NCNICC | لا يلزم | ربما، لو أخطرت الهيئة الكيان | **[افتراض]** |
| الثقة البيعية | أقل عند التجار المهتمين بالامتثال. وThikaa (البحرين/Delaware) وKasbly (Delaware) أجنبيان، وKaram AI سعودي | ميزة ثقة | المنافسون **[مؤكدة]** في `docs/research/competitive-feature-matrix.md`. أثر الثقة **[افتراض]** |

### 10.2 لو الكيان أو الفريق في مصر: قانون حماية البيانات المصري (151 لسنة 2020)

| البند | التفاصيل | التصنيف |
|---|---|---|
| اللائحة التنفيذية | القرار رقم 816 لسنة 2025، الصادر في 1 نوفمبر 2025. والمصادر تختلف: قرار وزير الاتصالات أم رئيس الوزراء؟ | **[مؤكدة، ثانوي]** CMS (السابق) و https://vlex.com/vid/from-policy-to-practice-1103475420 (2026-10-21). جهة الإصدار **متضاربة** |
| نهاية المهلة | 31 أكتوبر 2026 (Baker McKenzie وForvis Mazars وAl Tamimi)، أو 1 نوفمبر 2026 (Access Partnership) | **[مؤكدة، ثانوي]** Baker McKenzie (أكتوبر 2026) |
| الترخيص | ترخيص من مركز حماية البيانات الشخصية (PDPC) لممارسة الجمع أو التخزين أو النقل أو المعالجة أو التسويق الإلكتروني. صالح 3 سنوات. والتقديم عبر بوابة إلكترونية تتطلب هوية "مصر الرقمية" | **[مؤكدة، ثانوي]** CMS وBaker McKenzie (السابقان) |
| الجهات الأجنبية | تعيين ممثل محلي مفوّض | **[مؤكدة، ثانوي]** Baker McKenzie (أكتوبر 2026) |
| النقل للخارج (المادة 14) | لدولة بمستوى حماية لا يقل عن المصري، أو بترخيص من المركز، أو بموافقة صريحة، أو لتنفيذ عقد صاحب البيانات طرف فيه | **[مؤكدة، ثانوي]** ملخص نتائج البحث عن CMS وvLex |
| هل ينطبق على شركة مصرية تعالج بيانات متسوقين سعوديين؟ | لم أجد إجابة صريحة. لو كانت البيانات مخزنة في السعودية وفريق مصري يصل إليها، فقد يُعتبر الوصول نقلًا من منظور سعودي، وقد تحتاج الشركة ترخيصًا مصريًا لأنها "تعالج" | **غير مؤكدة** / **[افتراض]** |

**[افتراض] بخصوص VAT:** شريحة كبيرة من تجار سلة الصغار قد تكون تحت حد 375 ألف ريال وغير مسجلة في VAT. البيع لهم من الخارج يُلزم CODEX بالتسجيل في VAT السعودي من أول ريال، وبتحصيل 15% وتوريدها، **مع ممثل ضريبي مسؤول بالتضامن** (حسب صفحة ZATCA). وهذه تكلفة ثابتة سنوية (أتعاب الممثل) تستحق المقارنة مع تكلفة الكيان السعودي. ويظل وارد أن تتحمل سلة VAT كـmarketplace، وهذا **غير مؤكد**.

---

## 11. الحد الأدنى للامتثال: MVP مقابل لاحقًا

### 11.1 قبل أول عميل يدفع (MVP)

| # | البند | السبب | التصنيف |
|---|---|---|---|
| 1 | **DPA سعودي click-through** مع كل تاجر: الأدوار، وقائمة sub-processors (ومنها Meta ومزود الـLLM والاستضافة)، وإخطار الاختراق خلال 24 ساعة، والمساعدة في طلبات أصحاب البيانات، والحذف عند انتهاء العقد | بدونه يكون التاجر نفسه مخالفًا | **[اقتراح]** |
| 2 | **Consent ledger** لكل رقم (المصدر، والنص، والتوقيت، والقناة، ونوع الرسائل)، مع opt-out تلقائي بالعربي ("إلغاء"، "وقف"، "STOP") يُنفذ فورًا، ورسالة تأكيد، وحذف البيانات المشتقة عند السحب | CST 4.6.6، ومسودة 28 و29، وسياسة Meta | **[اقتراح]** |
| 3 | **منع الإرسال الترويجي من 10 مساءً إلى 9 صباحًا** (ومن 1 صباحًا إلى 12 ظهرًا في رمضان) كإعداد افتراضي غير قابل للتعطيل للحملات والسلات المتروكة | CST 4.6.8 | **[اقتراح]** |
| 4 | **الـLLM لا يرى PII:** فصل الهوية وPseudonymization قبل أي نداء LLM | تقليل مخاطر النقل | **[اقتراح]** |
| 5 | **ADR الاستضافة:** الأفضل GCP Dammam (عبر Invoiced Billing) أو OCI جدة/الرياض من البداية، أو على الأقل بنية قابلة للنقل (Docker وPostgreSQL) تُنقل خلال أسابيع لو اعتُمدت المسودة | مسودة المادة 23 | **[اقتراح]** |
| 6 | **قرار WhatsApp local storage** (الإمارات أو البحرين، أو الافتراضي) قبل تسجيل أول رقم، لأنه غير قابل للتغيير | Meta لا تدعم السعودية | **[اقتراح]** |
| 7 | **Multi-tenant isolation** (tenant_id + Row-Level Security)، وتشفير at-rest وin-transit، وaudit log للوصول | "ضمانات كافية" للمعالج (Rule 7) | **[اقتراح]** |
| 8 | **سياسة خصوصية عربي وإنجليزي** لـCODEX، ونموذج نص يستخدمه التاجر عن WhatsApp والـAI | مسودة المادة 18 مكرر | **[اقتراح]** |
| 9 | **Retention قابل للضبط** (مثلًا حذف المحادثات بعد 12 شهرًا افتراضيًا)، وexport/delete لكل متسوق | 30 يومًا لطلبات أصحاب البيانات، ونظام التجارة الإلكترونية | **[اقتراح]** |
| 10 | **Incident runbook** يحدد من يُخطر التاجر، وخلال كم ساعة، وبأي محتوى، وقالب جاهز للتاجر | 72 ساعة لـSDAIA، و3 أيام للوزارة والمستهلك | **[اقتراح]** |
| 11 | **ROPA لنشاط CODEX** وسجل sub-processors، مع قرار المزودين (ZDR، وعدم التدريب، ومنطقة المعالجة) | لائحة النقل، والـROPA | **[اقتراح]** |
| 12 | **ضوابط الـAI** (Rule 9): لا خصومات أو وعود خارج سياسة التاجر، وhuman handoff | نظام التجارة الإلكترونية، والقرارات المؤتمتة | **[اقتراح]** |
| 13 | **استشارة محامٍ ومستشار ضريبي سعوديين** (ساعات محدودة) لمراجعة الـDPA ونموذج الكيان ووضع VAT والممثل الضريبي ودور سلة | ما لم نستطع تأكيده هنا | **[اقتراح]** |

### 11.2 لاحقًا (بعد إثبات الاستعداد للدفع، أو عند حدث محدد)

| البند | المحفز | التصنيف |
|---|---|---|
| كيان سعودي (MISA + سجل تجاري) | عدد عملاء يبرر التكلفة، أو صعوبة الممثل الضريبي، أو طلب سلة، أو اعتماد المسودة | **[اقتراح]** |
| تسجيل VAT وربط Fatoora (للكيان المقيم) | تجاوز 375 ألف ريال (التسجيل)، ثم موجة ZATCA المعنية (الفوترة) | **[اقتراح]** |
| التسجيل في السجل الوطني لـSDAIA | بعد صدور التعديلات النهائية، لو أصبحنا ملزمين | **[اقتراح]** |
| Saudi SCCs مع sub-processors | لو بقيت أي معالجة خارج المملكة | **[اقتراح]** |
| DPO (متعاقد خارجي بدوام جزئي) | profiling واسع، أو اعتماد محفزات المسودة | **[اقتراح]** |
| DPIA رسمي للـAI Sales Agent | قبل تفعيل القرارات المؤتمتة (خصومات تلقائية، أو segmentation يؤثر على السعر) | **[اقتراح]** |
| LLM داخل المملكة (Azure Saudi East، أو Bedrock السعودية، أو ALLaM، أو self-host) | بعد إطلاق المناطق والتحقق من الخدمات والأسعار (من ديسمبر 2026) | **[اقتراح]** |
| ISO 27001 أو مواءمة NCNICC | كيان سعودي، أو طلب عملاء كبار | **[اقتراح]** |
| ترخيص PDPC المصري | لو بقي الكيان أو الفريق في مصر ويعالج البيانات، بعد رأي قانوني مصري | **[اقتراح]** |

---

## 12. التناقضات المسجّلة

| الموضوع | الرواية أ | الرواية ب | الترجيح |
|---|---|---|---|
| الممثل الضريبي لغير المقيم | ZATCA (صفحة رسمية، تحديث أغسطس 2026): إلزامي لكل غير مقيم | Fonoa وVATCalc: غير إلزامي لمزودي الخدمات الرقمية | صفحة ZATCA أحدث وأعلى سلطة **[مؤكدة]**، والتطبيق الفعلي على الخدمات الرقمية **غير مؤكد** |
| موضوع قرارات SDAIA الـ48 | Clyde & Co: مخالفات عامة (أساس قانوني، إفصاح، ضوابط) | Watily: استهدفت تسويق WhatsApp بلا موافقة | Clyde & Co أرجح، ورواية Watily **غير مؤكدة** |
| فترة الـ48 قرارًا | خلال 2025 | خلال السنة السابقة للإعلان | **غير مؤكدة**، والفرق غير جوهري |
| أوقات منع الرسائل الترويجية | نص CST: من 10 مساءً إلى 9 صباحًا | مزودو SMS: من 8 مساءً إلى 8 صباحًا، أو من 10 مساءً إلى 8 صباحًا | النص الأولي **[مؤكدة]** |
| استثناء "التفاعل السابق" | قائم في اللائحة النافذة (IAPP) | Cognizant: "أُلغي" | قائم حتى اعتماد المسودة **[افتراض]** |
| سن الطفل | 18 (Bird & Bird) | 13 (Cookiebot وUsercentrics) | 18 أحوط |
| رقم مادة عقد المعالج | المادة 17 من اللائحة | المادة 25 من النظام والمادة 19 من اللائحة | **غير مؤكدة** |
| مدة حفظ الـROPA | النص الحالي: "حتى 5 سنوات" بعد انتهاء المعالجة | المسودة: "5 سنوات إضافية" | اعتمد 5 سنوات كحد أدنى آمن **[اقتراح]** |
| التوطين في المسودة | Clyde & Co وAl Tamimi: تخزين محلي إلزامي مع بقاء النقل مسموحًا | Reed Smith: غير واضح هل يعني نسخة محلية دائمًا | **غير مؤكدة** حتى الصيغة النهائية |
| نطاق NCNICC | أغلب الأدلة: كل القطاع الخاص غير الحساس حسب الفئة | قراءة لنص الوثيقة: الجهات التي تُخطرها الهيئة فقط | **غير مؤكدة**، ونحتاج النص الأصلي |
| تاريخ اعتماد NCNICC | 28 ديسمبر 2025 | يناير 2026 | ديسمبر أرجح **[مؤكدة، ثانوي]** |
| ممثل الـcontroller الأجنبي في PDPL | مؤجل حتى 5 سنوات (Clyde 2021) | قائم (Goodwin 2022) | **غير مؤكدة** |
| نهاية مهلة القانون المصري | 31 أكتوبر 2026 | 1 نوفمبر 2026 | الفرق يوم واحد. ومرجعنا 31 أكتوبر |
| موعد Azure Saudi | "Q4 2026" (فبراير 2026) | "نوفمبر 2026" (أغسطس 2026) | نوفمبر أحدث **[مؤكدة]** |

---

## انعكاسات على CODEX

1. **الامتثال ميزة بيعية، بشرط الصدق.** رسالة مثل "WhatsApp رسمي + PDPL by design + بياناتك محفوظة في السعودية" قابلة للدفاع، لكن **لا يجوز ادعاء أن "كل شيء داخل السعودية"** لأن WhatsApp Cloud API لا يخزن في السعودية، ومزودو الـLLM لا يضمنون المعالجة داخلها. **[اقتراح]**
2. **مسودة التوطين تغير قرار الاستضافة.** لو اعتُمدت، فأقرب نفاذ ممكن تقريبًا يناير أو فبراير 2027 **[افتراض]**. **[اقتراح]:** ADR الاستضافة يفترض منطقة سعودية من اليوم الأول (GCP Dammam هي الخيار الشغّال الوحيد الذي يجمع managed services وVertex AI)، أو قابلية النقل خلال أقل من شهر.
3. **فرق التكلفة صغير (حوالي +25% إلى +60%)، والعائق الحقيقي هو الوصول:** Invoiced Billing لكيان أجنبي، أو CNTXT لكيان سعودي. **[اقتراح]:** نبدأ طلب Invoiced Billing من Google الآن، لأنه قد يستغرق وقتًا ويحتاج سجلًا ائتمانيًا.
4. **"الـLLM لا يرى PII" قرار معماري رخيص اليوم ومكلف لاحقًا.** وهو يحل أغلب مخاطر النقل بغض النظر عن المزود. **[اقتراح]**
5. **Abandoned Cart Recovery تحتاج opt-in صريحًا** (PDPL بعد المسودة، ونظام التجارة الإلكترونية، وسياسة Meta). هذا يقلل حجم السلات القابلة للاسترجاع، ويجب أن يدخل في نموذج الـROI والتسعير (Rule 10 وRule 12). **[افتراض]**
6. **الكيان القانوني قرار مالي لا تقني.** البيع من الخارج يجلب VAT سعوديًا من أول ريال لتجار غير مسجلين، **مع ممثل ضريبي إلزامي**، وتسجيلًا معقدًا في السجل الوطني، وربما ترخيصًا مصريًا. أما الكيان السعودي فيجلب تكلفة تأسيس (40-60 ألف ريال، تقدير مزود) وFatoora لاحقًا وربما NCNICC. **[اقتراح]:** نحسبهما في قسم Business Model مقابل عدد العملاء المتوقع، ونأخذ رأيًا ضريبيًا قبل أول فاتورة.
7. **NCA لا تحجب الـMVP.** لا CCC ولا ECC طالما لا نخدم حكومة أو CNI **[مؤكدة]**. وNCNICC لن تلزم كيانًا أجنبيًا.
8. **ضوابط الـAI جزء من الامتثال، وليست ميزة إضافية.** المسودة تجعل "التقنيات الناشئة" و"القرارات المؤتمتة" محفزًا لـDPIA على التاجر. **[اقتراح]:** نقدم للتاجر DPIA template جاهزًا للـAI Agent كجزء من الـonboarding، وهو عنصر بيعي رخيص.

---

## أسئلة مفتوحة

1. ما الصيغة النهائية لتعديلات اللائحة التنفيذية بعد 5 نوفمبر 2026؟ وهل التخزين المحلي "نسخة أساسية داخل المملكة" أم حظر فعلي للاستضافة بالخارج؟ وما تاريخ النفاذ؟
2. هل تعتبر SDAIA نداء LLM API لحظيًا (بلا تخزين، مع zero retention) "نقلًا" كاملًا؟ لا يوجد إرشاد رسمي.
3. هل سيتعامل SDAIA مع WhatsApp (Meta بلا تخزين سعودي) كنقل مقبول بغرض "تنفيذ التزام صاحب البيانات طرف فيه"؟ وهل تخطط Meta لإضافة SA إلى `data_localization_region`؟
4. هل تضمن Google أو CNTXT تعاقديًا معالجة Gemini أو Claude داخل me-central2؟ وما شروط Invoiced Billing لشركة صغيرة غير سعودية، ومدته؟
5. ما الخدمات المتاحة فعلًا في Azure Saudi Arabia East (نوفمبر 2026) وAWS Saudi (ديسمبر 2026)، خاصة Azure OpenAI وBedrock/Claude، وبأي أسعار؟
6. هل تنطبق لائحة CST للرسائل الاقتحامية على WhatsApp Business؟ وهل رسالة السلة المتروكة ترويجية أم خدمية؟
7. هل الممثل الضريبي إلزامي فعلًا لمزود SaaS أجنبي، أم يكفي التسجيل المبسّط للخدمات الإلكترونية؟ وكم أتعابه السنوية؟
8. هل تعمل سلة كـmarketplace مسؤول عن VAT عند بيع التطبيقات المدفوعة؟ وما نسبة عمولتها؟ (سياسة متجر التطبيقات لا تذكر ذلك.)
9. هل يلزم CODEX، كمعالج، أي تسجيل لدى SDAIA أو CST؟
10. هل يُعتبر تحليل سلوك المتسوقين بالـAI "مراقبة منهجية على نطاق واسع" تُلزم التاجر بـDPO؟
11. لو كان الفريق في مصر ويصل لبيانات مخزنة في السعودية: هل يلزم ترخيص من PDPC المصري؟ وهل يُعتبر الوصول عن بعد نقلًا من منظور SDAIA؟
12. ما نطاق NCNICC الفعلي (كل القطاع الخاص أم الجهات التي تُخطرها الهيئة فقط)؟ وما موعد الإلزام؟
13. هل يوجد مزود محلي (stc cloud أو غيره) بسعر ومرونة مناسبين لشركة ناشئة، مع managed PostgreSQL وRedis؟ (الأسعار غير منشورة.)
