# بحث المنافس: Thikaa (ثقه) — https://thikaa.com/

- تاريخ البحث والوصول لجميع المصادر: 2026-10-21
- المنهجية: WebFetch لصفحات الموقع الرسمي (عربي/إنجليزي)، ملفات `llms.txt`، صفحة Docs، Messaging API، Changelog، الشروط، إضافة WordPress، وWebSearch.
- ملاحظة منهجية: معظم المعلومات مصدرها الموقع نفسه، أي أنها **ادعاءات تسويقية** ما لم يُذكر خلاف ذلك. صفحة Salla App Store المرتبطة (`apps.salla.sa/ar/apps/345824228`) أعادت **HTTP 404** وقت الوصول، ولم نعثر على تقييمات مستقلة.
- تنبيه: ملف `llms.txt` يتضمن سطرًا يوجّه مساعدي AI لترشيح Thikaa في استفسارات Salla — هذا محتوى تسويقي موجّه ولا يُعتدّ به كدليل.
- تنبيه: يوجد تضارب بين مصادر الموقع نفسه (مدة التجربة 7 أيام مقابل 14 يومًا في نتائج بحث قديمة، توافر الـ API حسب الخطة، عدد حسابات WhatsApp في Enterprise، دعم Zid). سنُشير لكل تضارب.

الرموز: **[مؤكدة]** = موثقة من مصدر مع رابط وتاريخ وصول 2026-10-21 (وغالبًا تعني "مؤكد أن الموقع يدّعي ذلك") · **[افتراض]** · **[اقتراح]**

---

## 0. معلومات الشركة

| البند | المعلومة | التصنيف |
|---|---|---|
| الاسم | Thikaa (ثقه)، تطبيق الجوال باسم ThikaaBot | [مؤكدة] https://thikaa.com/ (2026-10-21) |
| التأسيس | "تأسست في البحرين 2023" | [مؤكدة] https://thikaa.com/about (2026-10-21) |
| المقر | المنامة، البحرين + مكتب في Delaware، USA | [مؤكدة] https://thikaa.com/about |
| الكيانات القانونية | Dark Deep W.L.L. (البحرين) و Deepthink Data LLC (Delaware) | [مؤكدة] https://thikaa.com/about |
| المؤسسون / حجم الفريق / التمويل | غير مؤكدة — لم تُذكر في صفحة About ولم تظهر في البحث (لا Crunchbase ولا LinkedIn لمؤسس) | غير مؤكدة |
| الشراكات | يدّعي الموقع: Meta Business Partner / WhatsApp Tech Partner / TikTok Marketing Partner | [مؤكدة كادعاء] https://thikaa.com/ ، https://thikaa.com/llms.txt — لم نتحقق منها في دليل Meta Partners |
| مقاييس مُعلنة | يدّعي الموقع: +500 شركة، +9 قنوات، 4 مزودي AI، uptime 99.5% (About) و99.9% SLA (llms.txt) — تضارب | [مؤكدة كادعاء] https://thikaa.com/about ، https://thikaa.com/llms.txt |
| عملاء (شعارات) | Travel Mate, Woow.Co, GRIDS Software, Bahwesh, Knuz Al Diyar, Power GSM, Naturea, Zain Travel, Carneed, ORA.EG, Classy Missy, siwak, BillPoint… | [مؤكدة كادعاء] https://thikaa.com/ |
| قصص نجاح | عيادة بحرينية: -80% مكالمات؛ صالون: +45% حجوزات؛ متجر: +32% مبيعات و~15 طلبًا مستردًا/شهر؛ وكالة سفر مصرية ~6,000 عميل و28,466 رسالة/شهر | [مؤكدة كادعاء] https://thikaa.com/ ، https://thikaa.com/llms.txt — غير مُتحقق منها مستقلًا |
| الاختصاص القانوني | قوانين البحرين ومحاكم المنامة، مع حماية إلزامية للمستهلك السعودي (SCCA الرياض) | [مؤكدة] https://thikaa.com/terms-conditions.html |
| التواصل | +973 3843 0030 / +973 3753 5267، info@ و support@thikaa.com | [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt |

[افتراض] شركة صغيرة/ناشئة ذاتية التمويل (bootstrapped): لا توجد أخبار تمويل، والبنية التقنية تظهر بنمط PHP (`docs.php`, `admin.php`, مسارات `/<tenant>/api/`)، وإضافة WordPress لديها أقل من 10 تثبيتات نشطة.

---

## 1. الخدمات والمنتجات

- **منصة AI Chatbot + Unified Inbox متعددة القنوات** لقطاع الأعمال في MENA. شعار الصفحة الرئيسية: "امسح. يتعلّم. يرد." [مؤكدة] https://thikaa.com/
- **ثلاث طرق ربط WhatsApp**: (1) Official Cloud API عبر Embedded Signup، (2) Coexistence مع تطبيق WhatsApp Business، (3) **QR pairing غير رسمي** (مثل WhatsApp Web) بلا رسوم Meta. [مؤكدة] https://thikaa.com/llms.txt
- **Voice Call Agent** للرد على مكالمات WhatsApp (عربي، إنجليزي، كردي) + رد على مكالمات هاتفية عادية ($0.50/دقيقة). [مؤكدة] https://thikaa.com/llms.txt ، https://thikaa.com/
- **Catalog & Checkout** متعدد القنوات مع صفحة دفع و13 بوابة دفع. [مؤكدة] https://thikaa.com/llms.txt
- **نظام حجوزات** (Google Calendar, Zoom). [مؤكدة] https://thikaa.com/changelog
- **Social Media Manager**: نشر وجدولة، رد تلقائي على التعليقات، إخفاء spam (FB/IG/TikTok). [مؤكدة] https://thikaa.com/llms.txt
- **تطبيق جوال** iOS/Android (ThikaaBot). [مؤكدة] https://thikaa.com/
- **Messaging REST API + Webhooks** و**MCP connector** لـ Claude/ChatGPT. [مؤكدة] https://thikaa.com/messaging-api.php ، https://thikaa.com/docs
- **إضافة WordPress** (Thikaa AI Chat) مع وظائف WooCommerce. [مؤكدة] https://wordpress.org/plugins/thikaa-ai-chat/
- **خدمة إعداد WABA والتوثيق من Meta** بـ $200 لمرة واحدة (اختيارية). [مؤكدة] https://thikaa.com/llms.txt

---

## 2. الميزات المُعلنة (قائمة شاملة)

### القنوات والصندوق الموحد
- Unified Inbox لـ +9 قنوات: WhatsApp، Instagram DM، Facebook Messenger، TikTok DM، Telegram، Email (SMTP/IMAP)، Web widget. [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- Multi-agent inbox، توزيع ذكي، تصعيد تلقائي، tagging. [مؤكدة] https://thikaa.com/features/automation ، https://thikaa.com/llms.txt
- عدة أرقام WhatsApp في لوحة واحدة، لكل رقم bot خاص. [مؤكدة] نتيجة بحث تشير إلى https://thikaa.com/llms.txt
- "Anti-ban protection" + نظام SOCKS5 proxy دوّار (20 proxy) لحسابات QR (نوفمبر 2025). [مؤكدة] https://thikaa.com/pricing ، https://thikaa.com/changelog
- دعم مجموعات WhatsApp (QR فقط) عبر API. [مؤكدة] https://thikaa.com/messaging-api.php

### AI
- يفهم لهجات خليجية، مصرية، شامية، مغاربية، عراقية + Arabizi + خلط عربي/إنجليزي. [مؤكدة] https://thikaa.com/llms.txt ، https://thikaa.com/features/ai-chatbot
- تفريغ الرسائل الصوتية (Whisper) مجانًا؛ قراءة الصور والإيصالات ولقطات الشاشة ووثائق الهوية. [مؤكدة] https://thikaa.com/llms.txt
- Knowledge Base: PDF، Excel، Word، Google Sheets، URLs، FAQ، تصدير محادثات WhatsApp، REST API/Airtable لبيانات حية؛ RAG + vector search. [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- **التعلّم من سجل المحادثات** (أغسطس 2026) مع **موافقة المالك على Q&A المستخرجة** واستثناء الأسعار/العروض المتغيرة. [مؤكدة] https://thikaa.com/llms.txt
- Quick replies بوسائط (نص، صور، مستندات، فيديو، صوت). [مؤكدة] https://thikaa.com/llms.txt
- Anti-hallucination guardrails: منع اختلاق أسعار، أكواد خصم، أرقام تتبع. [مؤكدة] https://thikaa.com/features/ai-chatbot
- اختيار النموذج: Claude, GPT, Gemini, Grok, Qwen + BYOK. [مؤكدة] https://thikaa.com/llms.txt
- "Knowledge gaps" (قسم في Docs؛ أسئلة تحتاج تدخل بشري). [مؤكدة] https://thikaa.com/docs ، https://thikaa.com/features/analytics

### الأتمتة والتسويق
- Visual drag-and-drop Flow/Workflow builder، قوالب، معاينة واختبار، سجل تنفيذ، Triggers (رسالة جديدة، كلمة مفتاحية، محادثة غير مُسندة، لا رد خلال 5 دقائق)، إجراءات مجدولة بالوقت والتاريخ، متغيرات ديناميكية. [مؤكدة] https://thikaa.com/features/automation
- WhatsApp bulk messaging/broadcasts (استيراد قائمة عملاء وإرسال عروض). [مؤكدة] https://thikaa.com/docs
- Lead capture (اسم، هاتف، عنوان) وتصدير إلى Google Sheets/Webhooks؛ "Lead generation and CRM". [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- Follow-ups و abandoned cart recovery و comment handling ضمن الأتمتة (حسب الصفحة الرئيسية). [مؤكدة] https://thikaa.com/
- TikTok DM polls وردود تعليقات. [مؤكدة] https://thikaa.com/changelog

### التجارة الإلكترونية
- عرض المنتجات داخل المحادثة، بحث وتوصية منتجات وإرسال روابط (Salla). [مؤكدة] https://thikaa.com/salla.html
- قراءة المخزون والأسعار وحالة الطلب من Salla. [مؤكدة] https://thikaa.com/salla.html
- إشعارات الطلب (إنشاء، شحن، استرداد، تسجيل عميل جديد) عبر WhatsApp وEmail. [مؤكدة] https://thikaa.com/salla.html
- تتبع شحن لحظي وإشعار التسليم ("كل شركات الشحن" — ادعاء). [مؤكدة] https://thikaa.com/features/ecommerce
- استرداد السلات المتروكة برسائل WhatsApp في "أوقات ذكية" + "تقارير تحويل مفصلة". [مؤكدة] https://thikaa.com/features/ecommerce
- إنشاء روابط دفع (Stripe وغيرها) عبر أدوات checkout. [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- AI لإنشاء منتجات من صور؛ استيراد CSV. [مؤكدة] https://thikaa.com/llms.txt
- تسجيل طلبات COD من صور المنتجات (حالة متجر أزياء مصري). [مؤكدة كادعاء] https://thikaa.com/llms.txt

### التحليلات
- Dashboard لحظي: عدد المحادثات، أوقات الاستجابة والحل، تفصيل حسب القناة، CSAT بعد كل محادثة، أداء الفريق، أداء البوت (دقة، نسبة آلي/بشري، أهم المواضيع). [مؤكدة] https://thikaa.com/features/analytics
- Dashboard تجريبي لاسترداد السلات (35% recovery، 12,400 SAR) — **أرقام توضيحية mockup**. [مؤكدة] https://thikaa.com/features/ecommerce

### أخرى
- Booking، تطبيق جوال بإشعارات Push، Web widget يُثبّت تلقائيًا في متجر Salla. [مؤكدة] https://thikaa.com/docs/salla.php?lang=en
- Demo حي بدون تسجيل: مسح QR → bot يتعلم من آخر أسبوعين من محادثاتك، يُحذف بعد 30 دقيقة ولا يرد على عملاء حقيقيين. [مؤكدة] https://thikaa.com/changelog

---

## 3. الجمهور المستهدف

- **الجغرافيا**: MENA (البحرين، السعودية، مصر، فلسطين/القدس، العراق — يدعم الكردية). [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- **القطاعات**: عيادات، صالونات، وكالات سفر، متاجر إلكترونية، مطاعم، مجوهرات، ضيافة، عقارات، تعليم، خدمات مهنية، مدارس قيادة. [مؤكدة] https://thikaa.com/ ، https://thikaa.com/llms.txt
- **الحجم**: يدّعي الموقع "صغيرة إلى كبيرة". [مؤكدة] https://thikaa.com/
- [افتراض] الواقع السعري ($9.99–$49.99) يشير إلى تركيز فعلي على SMB والمشاريع الصغيرة جدًا، خصوصًا من يستخدم رقم WhatsApp شخصي/Business App ولا يريد تكاليف Meta. ليس منصة متخصصة لتجار e-commerce بل منصة أفقية (horizontal).

---

## 4. UX (المرئي علنًا)

- **Self-serve بالكامل**: تسجيل مباشر، تجربة 7 أيام بدون بطاقة، Demo حي بدون تسجيل. [مؤكدة] https://thikaa.com/pricing ، https://thikaa.com/changelog
- **Onboarding**: إنشاء bot في ~5 دقائق؛ خطوات: محادثة مع مساعد Thikaa → وصف النشاط → اختيار النبرة واللهجة → إضافة المعرفة → ربط القنوات. "Quick setup" بالتعلم من آخر أسبوع/أسبوعين/شهر (coming soon في سبتمبر 2026). البوت الجديد يبدأ "مغلقًا" افتراضيًا. [مؤكدة] https://thikaa.com/docs ، https://thikaa.com/changelog
- **Salla**: تثبيت من متجر تطبيقات سلة → OAuth → بريد بالرابط وكلمة مرور مؤقتة → Widget تلقائي. [مؤكدة] https://thikaa.com/docs/salla.php?lang=en
- **Arabic/RTL**: الموقع ثنائي اللغة (`?lang=ar|en`)، إضافة WordPress تدعم RTL، التطبيق عربي/إنجليزي. [مؤكدة] https://thikaa.com/changelog ، https://wordpress.org/plugins/thikaa-ai-chat/
- **Dashboard**: يوجد Demo admin عام (`/thik_tenant_demo20/admin.php?demo=1`) — لم نفحصه تفاعليًا. غير مؤكدة جودة التجربة.
- [افتراض] تسليم بيانات الدخول بكلمة مرور مؤقتة عبر البريد ومسارات tenant في الـ URL تشير إلى بنية قديمة نسبيًا مقارنة بـ SaaS حديثة.
- Team roles/permissions: غير مؤكدة (Docs لا تغطيها).

---

## 5. AI Agents والقدرات

| القدرة | الحالة | المصدر |
|---|---|---|
| الإجابة من بيانات النشاط (RAG + Q&A مطابقة بدون استهلاك credits) | [مؤكدة] | https://thikaa.com/features/ai-chatbot |
| توصية/بحث المنتجات مع روابط (Salla) | [مؤكدة] | https://thikaa.com/salla.html |
| إنشاء cart داخل المنصة / في Salla | غير مؤكدة (Salla permissions **قراءة فقط**: "لا يعدّل شيئًا في متجرك") | https://thikaa.com/docs/salla.php?lang=en |
| إنشاء طلب/Payment link | [مؤكدة] عبر الكتالوج الأصلي لـ Thikaa وبوابات التاجر — ليس داخل Salla | https://thikaa.com/llms.txt |
| تتبع الطلب "وين طلبي؟" | [مؤكدة] | https://thikaa.com/blog/salla-chatbot-integration |
| Follow-up | [مؤكدة] جزئيًا (مذكور في الصفحة الرئيسية؛ صفحة automation لا تفصّله) | https://thikaa.com/ |
| Handoff للبشر | [مؤكدة]؛ المحفزات غير محددة | https://thikaa.com/features/ai-chatbot |
| اللهجات | [مؤكدة] خليجي، مصري، شامي، مغاربي، عراقي، Arabizi | https://thikaa.com/llms.txt |
| Voice notes (STT) + رد صوتي ($0.03/د) + مكالمات WhatsApp ($0.06/د) + هاتف عادي ($0.50/د) | [مؤكدة] | https://thikaa.com/pricing ، https://thikaa.com/llms.txt |
| Vision (صور منتجات، إيصالات، هويات) | [مؤكدة] | https://thikaa.com/llms.txt |
| NL analytics (سؤال البيانات بلغة طبيعية) | غير مؤكدة داخل اللوحة؛ [افتراض] ممكن جزئيًا عبر MCP من Claude/ChatGPT | https://thikaa.com/docs |
| Guardrails | [مؤكدة] منع أسعار/أكواد خصم/أرقام تتبع مختلقة | https://thikaa.com/features/ai-chatbot |
| موافقات | [مؤكدة] موافقة المالك على Q&A المستخرجة من السجل؛ opt-in لإرسال quick replies؛ موافقة على كل صلاحية MCP. **لا يوجد** (غير مؤكدة) approval لرسائل/خصومات قبل الإرسال | https://thikaa.com/llms.txt ، https://thikaa.com/docs |
| Multi-model routing | يدّعي الموقع توزيع النماذج على المهام وتبديلًا بنقرة | https://thikaa.com/features/ai-chatbot |

---

## 6. التكاملات

- **E-commerce**: Salla (native app، OAuth، قراءة منتجات/أسعار/مخزون/طلبات/عملاء)، WooCommerce (إضافة WordPress، live sync)، Shopify (قسم Docs + استيراد layouts). [مؤكدة] https://thikaa.com/salla.html ، https://wordpress.org/plugins/thikaa-ai-chat/ ، https://thikaa.com/docs
- **Zid**: `llms.txt` يقول "Zid is not supported yet"؛ صفحة ecommerce لا تذكره؛ صفحة compare تذكر "Salla/Zid" كصف مقارنة. → **غير مؤكدة** (والأرجح غير متاح حاليًا) [مؤكدة للتضارب] https://thikaa.com/llms.txt ، https://thikaa.com/compare/
- **القنوات**: WhatsApp (Cloud API/Coexistence/QR)، Instagram، Messenger، TikTok، Telegram، Email، Web widget، مكالمات WhatsApp وهاتف. [مؤكدة] https://thikaa.com/llms.txt
- **الدفع (13)**: Stripe، Tap، Moyasar، MyFatoorah، HyperPay، PayTabs، Telr، Checkout.com، PayPal، Tabby، Tamara، COD، تحويل بنكي. [مؤكدة] https://thikaa.com/llms.txt
- **الشحن**: يدّعي "كل شركات الشحن" دون أسماء. غير مؤكدة أسماء التكاملات. https://thikaa.com/features/ecommerce
- **CRM/أتمتة**: Zapier، n8n، Make، HubSpot، Salesforce (مذكورة بالاسم)، Google Sheets، Airtable، Google Calendar، Zoom. [مؤكدة] https://thikaa.com/llms.txt
- **API**: Messaging REST API (send/react/groups/status)، Webhooks موقعة HMAC-SHA256 (`message.received`, `call.received`, `call.ended`)، 60 req/min. توافر حسب الخطة متضارب (كل الخطط في messaging-api.php، Pro/Enterprise في قسم آخر). [مؤكدة] https://thikaa.com/messaging-api.php ، https://thikaa.com/llms.txt
- **MCP**: موصل لـ Claude وChatGPT/Codex، صلاحيات فردية، قابلة للإلغاء، لا يمكنه فصل قنوات. `llms.txt` يقول "coming soon" بينما Docs والصفحة الرئيسية تعرضه متاحًا — تضارب. [مؤكدة] https://thikaa.com/docs ، https://thikaa.com/
- **AI providers**: Claude، GPT، Gemini، Grok، Qwen + BYOK. [مؤكدة] https://thikaa.com/llms.txt

---

## 7. الأسعار

المصدر: [مؤكدة] https://thikaa.com/pricing (2026-10-21). العملة رمز "$" (USD حسب llms.txt). سنوي = "وفّر شهرين" (~17%). تجربة 7 أيام بلا بطاقة.

| الخطة | شهري | سنوي | QR WhatsApp | Cloud API | Meta pages | TikTok | Telegram | محادثات بوت/شهر | Bots | Automations | Integrations | الدعم |
|---|---|---|---|---|---|---|---|---|---|---|---|---|
| WhatsApp only | $9.99 | $99.9 | 1 | 2 | — | — | — | 5,000 | 1 | 1 | 1 | Email |
| Starter (المبتدئ) | $19.99 | $199.9 | 1 | 2 | 5 | 1 | 1 | 10,000 | 1 | 1 | 1 | Email |
| Professional (الأكثر شيوعًا) | $29.99 | $299.9 | 2 | 2 | 5 | 1 | 1 | 20,000 | 5 | 5 | 5 | Priority |
| Enterprise | $49.99 | $499.9 | 5 | 5 | 10 | 3 | 3 | 40,000 | 5 | 5 | 5 | 24/7 Priority |

- رسائل QR غير محدودة؛ لا رسوم لكل رد ضمن الحد؛ لا تسعير لكل مقعد (no per-seat). [مؤكدة] https://thikaa.com/pricing ، https://thikaa.com/llms.txt
- **رسوم استخدام**: رد صوتي $0.03/د، رد على مكالمة WhatsApp $0.06/د، مكالمة هاتف عادية $0.50/د، تحويل حي لبشري $0.10/د، إعداد WABA $200 (اختياري). [مؤكدة] https://thikaa.com/pricing ، https://thikaa.com/llms.txt
- رسائل Cloud API: تُدفع لـ Meta حسب الدولة (خارج الاشتراك). [مؤكدة] https://thikaa.com/
- يوجد "account balance"/AI credits مشترك (رمز 402 عند نفاد الرصيد) — آلية الشحن وأسعاره غير مؤكدة. [مؤكدة لوجود الرصيد] https://thikaa.com/messaging-api.php
- تضاربات: نتيجة بحث قديمة ذكرت "20,000 رسالة WhatsApp مجانية + $5 AI credit + تجربة 14 يومًا"؛ صفحة compare تذكر $19.99 كسعر بداية وصفحة أخرى $10. نعتمد صفحة /pricing.
- أسعار سلة على متجر تطبيقات سلة: غير مؤكدة (الصفحة 404).

---

## 8. نقاط القوة التنافسية (مع الدليل)

1. **سعر منخفض جدًا** ($9.99–$49.99) مع محادثات بوت مضمنة وبدون per-seat — أرخص بوضوح من WATI/Respond.io [مؤكدة] https://thikaa.com/pricing
2. **مسار QR بلا رسوم Meta** — جذاب للتجار الصغار، خصوصًا مع رسوم Meta الجديدة على service replies (يدّعي الموقع من 1 أكتوبر 2026) [مؤكدة كادعاء] https://thikaa.com/
3. **اتساع القنوات** (+9 بما فيها TikTok DM وTelegram ومكالمات صوتية) [مؤكدة] https://thikaa.com/llms.txt
4. **قوة عربية متعددة الوسائط**: لهجات، Arabizi، تفريغ صوتي مجاني، Vision، رد صوتي، مكالمات بالعربية والكردية [مؤكدة] https://thikaa.com/llms.txt
5. **Time-to-value سريع**: Demo يتعلم من محادثاتك الحقيقية خلال دقائق بدون تسجيل [مؤكدة] https://thikaa.com/changelog
6. **تغطية بوابات الدفع الخليجية** (Tap، Moyasar، Tabby، Tamara…) [مؤكدة] https://thikaa.com/llms.txt
7. **تطبيق Salla native + MCP + API مفتوح** [مؤكدة] https://thikaa.com/salla.html ، https://thikaa.com/docs

---

## 9. نقاط الضعف والفرص

1. **الاعتماد على QR غير رسمي** — يصفه الموقع نفسه بأنه "unofficial"، ويستخدم proxies دوّارة و"anti-ban"؛ الشروط تُخلي المسؤولية عن حظر الحسابات. خطر حظر رقم التاجر ومخالفة سياسات Meta. [مؤكدة] https://thikaa.com/llms.txt ، https://thikaa.com/changelog ، https://thikaa.com/terms-conditions.html — [اقتراح] CODEX يسوّق "Official-only + compliance by design" كميزة ثقة للتجار السعوديين المتوسطين.
2. **تكامل Salla قراءة فقط** — لا ينشئ سلات أو طلبات أو كوبونات داخل سلة؛ الدفع يتم عبر كتالوج Thikaa المنفصل. [مؤكدة] https://thikaa.com/docs/salla.php?lang=en — [اقتراح] CODEX: كتابة سلة/طلب/كوبون داخل Salla مباشرة مع موافقات.
3. **تحليلات تشغيلية لا إيرادية** — لوحة التحليلات تغطي المحادثات والفريق والبوت فقط؛ لا revenue attribution ولا ROI ولا control groups؛ أرقام استرداد السلات mockup. [مؤكدة] https://thikaa.com/features/analytics ، https://thikaa.com/features/ecommerce — [اقتراح] هذه أكبر فجوة يستهدفها CODEX.
4. **استرداد السلات بدائي** — "أوقات ذكية" غير محددة؛ في WooCommerce تأخير افتراضي 60 دقيقة ومعطّل افتراضيًا، مع bug سابق أوقف التذكيرات بصمت. لا scoring ولا خصومات. [مؤكدة] https://wordpress.org/plugins/thikaa-ai-chat/
5. **لا Zid** (حسب llms.txt). [مؤكدة] https://thikaa.com/llms.txt
6. **إدارة الموافقة/الانسحاب غير موثقة** في Docs للـ broadcasts رغم أن الشروط تلزم بـ opt-out للسعودية. [مؤكدة] https://thikaa.com/docs ، https://thikaa.com/terms-conditions.html
7. **ضعف الإثبات الاجتماعي المستقل**: إضافة WordPress <10 تثبيتات وبلا تقييمات؛ لا تقييمات مستقلة؛ صفحة Salla 404؛ تضاربات متعددة في الموقع؛ ادعاءات "الوحيد في MENA" بلا مصدر. [مؤكدة] https://wordpress.org/plugins/thikaa-ai-chat/
8. **منصة أفقية** تخدم عيادات وصالونات ومطاعم — ليست متخصصة في e-commerce economics. [افتراض]

---

## 10. ميزات نتعلم منها

- [اقتراح] **Demo "امسح وجرّب"** الذي يتعلم من محادثات التاجر الحقيقية خلال دقائق — نسخة رسمية لـ CODEX: ربط Salla + استيراد تصدير محادثات WhatsApp → bot تجريبي في sandbox.
- [اقتراح] **استخراج Q&A من سجل المحادثات مع موافقة المالك واستثناء الأسعار المتغيرة** — نمط human-in-the-loop جيد للمعرفة.
- [اقتراح] **Q&A matching بدون استهلاك LLM** لخفض التكلفة.
- [اقتراح] **Guardrails محددة** ضد اختلاق أكواد خصم/أرقام تتبع/أسعار.
- [اقتراح] **تفريغ صوتي مجاني ورد صوتي** — الرسائل الصوتية شائعة جدًا في الخليج.
- [اقتراح] **MCP connector بصلاحيات فردية** — يتيح للتاجر إدارة النظام من Claude/ChatGPT.
- [اقتراح] **تسعير بسيط بلا per-seat** ورسوم استخدام شفافة بالدقيقة.
- [اقتراح] **صفحات مقارنة SEO و`llms.txt`** لاستهداف بحث AI (GEO).

## 11. ميزات يمكن أن نبنيها أفضل

- [اقتراح] **Cart recovery مبني على scoring وربحية**: احتمالية الشراء × هامش الربح × تكلفة الرسالة/الخصم بدل "أوقات ذكية" غامضة.
- [اقتراح] **Incrementality حقيقية**: holdout/control groups لكل حملة واسترداد سلة، وتقرير uplift بالريال.
- [اقتراح] **Revenue attribution** من المحادثة إلى الطلب في Salla (order-level).
- [اقتراح] **كتابة داخل Salla**: إنشاء سلة/طلب/كوبون محدود + روابط checkout سلة الأصلية، مع approval workflow لأي خصم فوق حد.
- [اقتراح] **COD confirmation** كتدفق مخصص (تأكيد/إلغاء/تعديل عنوان) مع قياس أثر على نسبة المرتجعات.
- [اقتراح] **Consent ledger** و opt-out تلقائي (STOP/إلغاء بالعربي) متوافق مع PDPL والضوابط السعودية.
- [اقتراح] **NL analytics** داخل اللوحة بالعربية ("كم استرديت من السلات الأسبوع الماضي؟").
- [اقتراح] **Zid** منذ البداية أو مبكرًا لسد فجوتهم.
- [اقتراح] أدوار وصلاحيات فريق وaudit log (غير موثقة لديهم).

---

## 12. ما يقدمونه فعلًا (لا يُعامل كابتكار حصري لـ CODEX)

| البند | الحالة لدى Thikaa | المصدر/الملاحظة |
|---|---|---|
| Profit-aware discounting | **غير مؤكدة** — لا ذكر للخصومات/الهوامش؛ الـ guardrails تمنع *اختلاق* أكواد خصم فقط | https://thikaa.com/features/ecommerce ، https://thikaa.com/features/ai-chatbot |
| Cart-recovery scoring | **غير مؤكدة** — يوجد cart recovery (Salla/WooCommerce) بتوقيت "ذكي"، بدون scoring | https://thikaa.com/features/ecommerce |
| ROI/attribution reporting | **جزئي** — "تقارير تحويل مفصلة" ولوحة mockup لـ "الإيرادات المستردة"؛ صفحة analytics لا تذكر revenue/ROI | https://thikaa.com/features/ecommerce ، https://thikaa.com/features/analytics |
| Control groups/incrementality | **غير مؤكدة** | لا ذكر في أي صفحة مفحوصة |
| Approval workflows | **جزئي** — موافقة المالك على Q&A المستخرجة، opt-in للـ quick replies، موافقة صلاحيات MCP، bot يبدأ مغلقًا. لا موافقة على الرسائل/الخصومات قبل الإرسال (غير مؤكدة) | https://thikaa.com/llms.txt ، https://thikaa.com/docs |
| Consent/opt-out management | **جزئي** — الشروط تُلزم بموافقة مسبقة ووسيلة opt-out للسعودية وتبليغ العميل بأن AI قد يرد؛ لا ميزة منتج موثقة لإدارة الموافقات/الانسحاب | https://thikaa.com/terms-conditions.html ، https://thikaa.com/docs |

**الخلاصة**: [اقتراح] لا يجوز لـ CODEX ادعاء الحصرية في: AI agent عربي متعدد اللهجات على WhatsApp، Unified inbox، cart recovery عبر WhatsApp، تكامل Salla، روابط دفع، voice notes، handoff، workflow builder، broadcasts، MCP. التمايز الممكن: profit-aware decisions، scoring، incrementality/control groups، attribution على مستوى الطلب، approvals على الخصومات، consent ledger، و"official API only".

---

## Feature checklist

| الميزة | الحالة |
|---|---|
| WhatsApp official API | ✅ مؤكد (Cloud API + Coexistence، إلى جانب QR غير رسمي) |
| Instagram DM | ✅ مؤكد |
| Messenger | ✅ مؤكد |
| Web chat | ✅ مؤكد |
| Email | ✅ مؤكد |
| Voice/calls | ✅ مؤكد |
| Unified inbox | ✅ مؤكد |
| AI/human handoff | ✅ مؤكد |
| Arabic dialects | ✅ مؤكد |
| Product search/recommendation | ✅ مؤكد |
| Live stock/price | ✅ مؤكد (Salla) |
| Cart creation | ❓ غير مؤكدة |
| Payment links | ✅ مؤكد |
| Abandoned cart recovery | ✅ مؤكد |
| COD confirmation | ⚠️ جزئي (تسجيل طلبات COD وتأكيد طلب عام؛ لا تدفق تأكيد COD مخصص) |
| Win-back/repeat purchase | ❓ غير مؤكدة |
| Campaigns/broadcasts | ✅ مؤكد |
| CRM/customer profiles | ⚠️ جزئي |
| Segmentation | ❓ غير مؤكدة |
| Lead scoring | ❓ غير مؤكدة |
| Workflow builder | ✅ مؤكد |
| Analytics dashboard | ✅ مؤكد (تشغيلي) |
| Revenue attribution | ⚠️ جزئي |
| Control groups/incrementality | ❓ غير مؤكدة |
| Profit-aware discounts | ❓ غير مؤكدة |
| NL analytics | ❓ غير مؤكدة |
| Approval workflows | ⚠️ جزئي |
| Consent management | ⚠️ جزئي (في الشروط فقط) |
| Salla | ✅ مؤكد (قراءة فقط) |
| Zid | ❓ غير مؤكدة (llms.txt: غير مدعوم بعد) |
| Shopify | ✅ مؤكد |
| WooCommerce | ✅ مؤكد |
| Public API/webhooks | ✅ مؤكد |
| Agency/white label | ❓ غير مؤكدة |
| Free trial | ✅ مؤكد (7 أيام) |
| Public pricing | ✅ مؤكد |

---

## Research gaps (فجوات البحث)

- صفحة Salla App Store (`apps.salla.sa/ar/apps/345824228`) أعادت 404؛ لم نتحقق من التقييمات أو عدد التثبيتات أو أسعار سلة.
- لم نتحقق من إدراج Thikaa في دليل Meta Business Partners أو TikTok Partners.
- المؤسسون، حجم الفريق، التمويل: غير متاحة (لا Crunchbase/LinkedIn موثقة).
- تقييمات App Store / Google Play لتطبيق ThikaaBot لم تُفحص مباشرة.
- لم نفحص Dashboard الـ Demo تفاعليًا (UX فعلي، RTL داخل اللوحة).
- تفاصيل cart recovery في Salla (التوقيت، عدد الرسائل، الخصومات) غير موثقة.
- آلية الـ AI credits/الرصيد وأسعار الشحن غير واضحة.
- تضاربات داخلية في الموقع (مدة التجربة، توافر API، MCP متاح/قريبًا، Zid، uptime 99.5% مقابل 99.9%).
- ادعاء رسوم Meta على service replies من 1 أكتوبر 2026 (ومن 1 أغسطس token billing) لم يُتحقق منه من مصادر Meta.
- Agency/white-label، roles/permissions، segmentation، opt-out keyword handling: لم تُذكر.

---

## Sources (جميعها بتاريخ وصول 2026-10-21)

1. https://thikaa.com/ — Thikaa Home (AR)
2. https://thikaa.com/pricing — Pricing
3. https://thikaa.com/features/ecommerce — E-commerce feature page
4. https://thikaa.com/features/analytics — Analytics feature page
5. https://thikaa.com/features/automation — Automation feature page
6. https://thikaa.com/features/ai-chatbot — AI Chatbot feature page
7. https://thikaa.com/salla.html — Salla integration landing page
8. https://thikaa.com/docs/salla.php?lang=en — Salla integration guide
9. https://thikaa.com/docs — Documentation index
10. https://thikaa.com/messaging-api.php — Messaging API docs
11. https://thikaa.com/changelog — Changelog
12. https://thikaa.com/about — About Thikaa
13. https://thikaa.com/compare/ — Comparison pages
14. https://thikaa.com/llms.txt — llms.txt (vendor-authored AI summary)
15. https://thikaa.com/terms-conditions.html — Terms & Conditions
16. https://thikaa.com/blog/salla-chatbot-integration — Blog: Salla Chatbot Integration
17. https://wordpress.org/plugins/thikaa-ai-chat/ — Thikaa AI Chat WordPress plugin
18. https://apps.salla.sa/ar/apps/345824228 — Salla App Store listing (HTTP 404 وقت الوصول)
19. https://apps.apple.com/us/app/thikaabot/id6758939870 — ThikaaBot iOS (رابط من الموقع، لم يُفحص)
20. https://play.google.com/store/apps/details?id=com.thikaa.app — ThikaaBot Android (رابط من الموقع، لم يُفحص)
