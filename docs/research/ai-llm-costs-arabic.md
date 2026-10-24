# اقتصاديات وجودة الـAI لوكيل مبيعات عربي (AI Economics & Quality for an Arabic Sales Agent)

- **تاريخ الاطلاع:** 2026-10-21
- **النطاق:** أسعار LLM APIs للإنتاج، وفهم اللهجات الخليجية والمصرية، وEmbeddings للبحث في المنتجات بالعربي، وتكلفة المحادثة وتدفق استرجاع السلة (Recovery flow)، والاستضافة داخل السعودية، والتحكم في الهلوسة في الأسعار والمخزون، وتقييم الوكلاء العرب، والحد الأدنى من معمارية الـAI، ثم تقدير AI COGS الشهري لكل Tenant.
- **ملف مكمّل لـ:** `docs/research/competitive-feature-matrix.md` و`docs/blueprint/09-technical-architecture.md`

## المنهجية
1. الأسعار من صفحات التسعير الرسمية مباشرة (Anthropic وOpenAI وGoogle وMeta) يوم 2026-10-21. وراجعت الأرقام المهمة مع مصدر ثانٍ (مدونات أو مجمّعات أسعار) لما كان ده ممكنًا.
2. أدلة اللهجات من أوراق محكّمة أو preprints على arXiv/ACL Anthology، ومن الـleaderboards المعروفة (HELM Arabic، وBALSAM، وOALL).
3. الاستضافة الإقليمية من إعلانات AWS وMicrosoft وGoogle الرسمية وتقارير صحفية.
4. حسابات التكلفة **نموذج تقديري** (Back-of-envelope) بافتراضات مكتوبة صراحةً. مفيش أي قياس فعلي على بيانات عملاء.

## القيود (Limitations)
- WebFetch بيلخّص الصفحات بنموذج صغير، فممكن تحصل أخطاء نقل بسيطة. أسعار Anthropic اتقرت من نص الصفحة الكامل. أسعار OpenAI وGoogle اتقرت من ملخص للصفحة الرسمية، **فلازم تتراجع يدويًا قبل أي قرار تسعير**.
- معظم أسماء النماذج الحالية (GPT-6 Luna/Sol، وGemini 3.8 Flash، وClaude Haiku 5.5) طلعت في آخر 1–3 شهور، والأسعار الترويجية بتتغير بسرعة.
- مفيش ولا benchmark منشور بيقيس **Claude 5.5** على اللهجات الخليجية أو المصرية في سياق مبيعات. الأدلة المتاحة عن نماذج أقدم أو منافسين.
- معدل Tokens العربي (Tokenizer fertility) متقاسش على نماذج 2026 بالتحديد. **لازم نقيسه بنفسنا** بـ`count_tokens` على محادثات حقيقية.
- مقدرتش أفتح صفحة HUMAIN الرسمية (HTTP 403) ولا PDF دليل OpenAI للوكلاء (binary)، فاعتمدت على مصادر ثانوية في النقطتين دول، ومعلَّمين كده.

---

## 1. أسعار الـLLMs المناسبة لوكيل محادثة إنتاجي

### 1.1 Anthropic Claude (أسعار الـAPI المباشرة، USD لكل 1M token)

| النموذج | Input | 5m cache write | 1h cache write | Cache read | Output | Batch (in/out) | المصدر |
|---|---|---|---|---|---|---|---|
| Claude Haiku 5.5 (prompts ≤100K) | $0.10 | $0.125 | $0.20 | $0.01 | $0.50 | $0.05 / $0.25 [مؤكدة من مصدر ثانوي] | [مؤكدة] https://platform.claude.com/docs/en/about-claude/pricing (2026-10-21) |
| Claude Haiku 5.5 (prompts >100K) | $0.50 | $0.625 | $1.00 | $0.05 | $2.50 | — | [مؤكدة] نفس المصدر (2026-10-21) |
| Claude Sonnet 5.5 | $2.00 | $2.50 | $4.00 | $0.10 (0.05x) | $10.00 | — | [مؤكدة] نفس المصدر (2026-10-21) |
| Claude Sonnet 5 | $2.00 | $2.50 | $4.00 | $0.20 | $10.00 | — | [مؤكدة] نفس المصدر (2026-10-21) |
| Claude Opus 5.5 | $4.00 | $5.00 | $8.00 | $0.20 (0.05x) | $20.00 | $2 / $10 | [مؤكدة] نفس المصدر (2026-10-21) |
| Claude Haiku 4.5 (الجيل السابق) | $1.00 | $1.25 | $2.00 | $0.10 | $5.00 | — | [مؤكدة] نفس المصدر (2026-10-21) |

ملاحظات مؤثرة على التكلفة:
- الـBatch API بيدي **خصم 50%** على الـinput والـoutput. ده مناسب للمهام اللي مش محتاجة رد فوري، زي توليد رسائل الاسترجاع أو تحليلات الليل. [مؤكدة] https://platform.claude.com/docs/en/about-claude/pricing (2026-10-21)
- خصومات الـcaching بتتجمع مع خصم الـBatch ومع مضاعِف الـdata residency. [مؤكدة] نفس المصدر (2026-10-21)
- الـtokenizer في Claude 4.7 وما بعده بيطلّع **حوالي 30% tokens زيادة لنفس النص**. يعني سعر الـtoken الأرخص مش معناه بالضرورة تكلفة أقل بنفس النسبة. [مؤكدة] نفس المصدر (2026-10-21)
- `inference_geo: "us"` بيضيف مضاعِف 1.1x. **ومفيش خيار geo للسعودية أو الخليج** في الـAPI المباشر. [مؤكدة] نفس المصدر (2026-10-21)
- إطلاق Haiku 5.5 كان في 2026-10-07، وسعره عُشر سعر Haiku 4.5 للـprompts اللي ≤100K. [مؤكدة من مصدر ثانوي] https://letsdatascience.com/news/anthropic-releases-claude-haiku-55-with-lower-prices-and-eff-f698c663 و https://computingforgeeks.com/claude-haiku-5-5-released-features-benchmarks/ (2026-10-21)
- **تضارب مسجّل:** جدول مرجعي داخلي في أداة claude-api (cached 2026-10-06) كاتب Cache read لـSonnet 5.5 = $0.20، وصفحة التسعير الرسمية بتقول $0.10 (0.05x). **المعتمد هو الصفحة الرسمية.**

### 1.2 OpenAI (Standard tier، USD لكل 1M token، context ≤272K)

| النموذج | Input | Cached input | Output | المصدر |
|---|---|---|---|---|
| gpt-6-luna | $0.10 | $0.01 | $0.50 | [مؤكدة] https://developers.openai.com/api/docs/pricing (2026-10-21)، ومتأكد منه كمان من مصدر ثانوي https://www.requesty.ai/blog/gpt-6-sol-luna-pricing-release-api |
| gpt-6-sol | $2.00 | $0.20 | $10.00 | [مؤكدة] نفس المصدرين (2026-10-21) |
| gpt-6.1-sol | $2.00 | $0.10 | $10.00 | [مؤكدة] الصفحة الرسمية (2026-10-21) |
| gpt-5.4-mini | $0.75 | $0.075 | $4.50 | [مؤكدة] الصفحة الرسمية (2026-10-21) |
| gpt-5.4-nano | $0.20 | $0.02 | $1.25 | [مؤكدة] الصفحة الرسمية (2026-10-21) |
| gpt-5-mini | $0.25 | $0.025 | $2.00 | [مؤكدة] الصفحة الرسمية (2026-10-21) |

- الـBatch والـFlex بحوالي نص السعر لمعظم النماذج. والـRegional processing (data residency) عليه زيادة 10% للنماذج اللي طلعت من 2026-03-05 وبعدها. [مؤكدة] https://developers.openai.com/api/docs/pricing (2026-10-21)
- **تضارب مسجّل:** مجمّع أسعار كاتب gpt-5-mini بـ$0.125/$1.00، والصفحة الرسمية بتقول $0.25/$2.00. **المعتمد هو الرسمي.**
- إطلاق GPT-6 Sol/Luna كان في 2026-09-22. [مؤكدة من مصدر ثانوي فقط] https://www.requesty.ai/blog/gpt-6-sol-luna-pricing-release-api (2026-10-21)

### 1.3 Google Gemini (Gemini API، paid tier، USD لكل 1M token)

| النموذج | Input | Output | Context caching | تخزين الكاش/ساعة | المصدر |
|---|---|---|---|---|---|
| Gemini 3.8 Flash | $0.75 لحد 2026-12-31، ثم $1.50 | $3.75، ثم $7.50 | $0.075، ثم $0.15 | $0.50، ثم $1.00 | [مؤكدة] https://ai.google.dev/gemini-api/docs/pricing (2026-10-21) |
| Gemini 3.5 Flash-Lite | $0.30 | $2.50 | $0.03 | $1.00 | [مؤكدة] نفس المصدر (2026-10-21) |
| Gemini 3.1 Flash-Lite | $0.25 | $1.50 | $0.025 | $1.00 | [مؤكدة] نفس المصدر (2026-10-21) |
| Gemini 3.1 Pro Preview (≤200K) | $2.00 | $12.00 | $0.20 | $4.50 | [مؤكدة] نفس المصدر (2026-10-21) |

- **تحذير:** سعر Gemini 3.8 Flash الحالي ترويجي، وهيتضاعف في 2027-01-01. أي نموذج تكلفة مبني عليه لازم يحسب السعر بعد 2027. [مؤكدة] نفس المصدر، ومتأكد من مصدر ثانٍ https://docs.litellm.ai/blog/gemini_3_8_flash (2026-10-21)
- **تضارب مسجّل:** مدونة ثانوية كاتبة إن سعر cached input بعد 2027 هيفضل $0.075، والصفحة الرسمية بتقول $0.15. **المعتمد هو الرسمي.**
- الـBatch بخصم 50%. [مؤكدة] https://ai.google.dev/gemini-api/docs/pricing (2026-10-21)

### 1.4 النماذج العربية (Jais / ALLaM / Falcon-Arabic): هل فيه API تجاري جاهز؟

| النموذج | الإتاحة كـAPI | السعر | الحالة |
|---|---|---|---|
| **ALLaM (HUMAIN)** | تطبيق HUMAIN Chat للمستهلكين شغال بـALLaM 34B. الـdeveloper API كان "coming soon" لحد 2026-08-31. Microsoft Foundry اتعلن عنه كنيّة بس (2026-08-26) | **غير منشور** | [مؤكدة من مصدر ثانوي] https://vision2030.ai/analysis/humain-allam-microsoft-foundry-enterprise-ai/ (2026-10-21) |
| ALLaM على Amazon Bedrock | AWS بتقول إنه "will soon be available"، من غير تاريخ | غير منشور | [مؤكدة] https://www.aboutamazon.com/news/aws/aws-cloud-region-saudi-arabia (2026-10-21) |
| ALLaM-7B-Instruct-preview | Open weights على Hugging Face | مجاني (self-host) | [مؤكدة] https://huggingface.co/humain-ai/ALLaM-7B-Instruct-preview (2026-10-21) |
| ALLaM على Azure (تقرير أقدم) | تقرير قديم بيقول إن ALLaM 7B بقى GA على Azure | — | [مؤكدة من مصدر ثانوي] https://www.middleeastainews.com/p/saudi-allam-llm-available-on-azure. **تضارب:** تحليل سبتمبر 2026 بيقول إن ALLaM مش ظاهر في قائمة Foundry العامة. الحالة الحالية **غير مؤكدة** |
| **Jais 2 (Inception/G42)** | Open-weight بحجم 70B، اتعلن في 2025-12-09 | **غير منشور** كـAPI | [مؤكدة] https://mbzuai.ac.ae/news/inception-cerebras-and-mbzuai-release-jais-2-the-next-generation-of-the-worlds-leading-arabic-open-weight-llm/ (2026-10-21) |
| Jais عبر Core42 Compass | صفحة أسعار Compass مفيهاش Jais، وبتوجّه للتواصل مع المبيعات | غير منشور | [مؤكدة] https://www.core42.ai/compass/documentation/compass-model-pricing (2026-10-21). أسعار Jais عند المجمّعات متضاربة وغالبًا قديمة (**غير مؤكدة**) |
| **Falcon-H1-Arabic (TII)** | Open weights بأحجام 3B و7B و34B، وcontext لحد 256K، اتنشر في 2026-01-05. فيه web chat demo بس **ومفيش API مستضاف معلن** | مجاني (self-host)، والترخيص **غير مؤكد** | [مؤكدة] https://huggingface.co/blog/tiiuae/falcon-h1-arabic (2026-10-21) |

**الخلاصة:** النهارده (2026-10-21) مفيش نموذج عربي متخصص متاح كـ**managed API بسعر منشور** ومناسب لـSaaS ناشئ. [مؤكدة بناءً على الجدول أعلاه]

---

## 2. أدلة فهم اللهجات الخليجية والمصرية

| الدليل | النتيجة الأساسية | التصنيف |
|---|---|---|
| ArabCulture-Dialogue (MBZUAI، أبريل 2026)، يغطي 13 دولة منها السعودية والإمارات ومصر | النماذج المغلقة **مفيهاش تقريبًا أي فجوة بين الفصحى واللهجة في الفهم**: GPT-5 جاب 0.943 فصحى و0.948 لهجة، وGemini-2.5-Pro جاب 0.942 و0.939. النماذج المفتوحة 7–9B بتنزل على اللهجة (SILMA مثلًا من 0.783 لـ0.716) | [مؤكدة] https://arxiv.org/html/2605.00119v1 (2026-10-21) |
| نفس الورقة، في **توليد** نص باللهجة المطلوبة (Dialect steering) | أحسن دقة صارمة للهجة كانت **0.505** (Gemini-2.5-Pro)، وGPT-5 جاب 0.454، وALLaM-7B جاب 0.36، وJais-7B جاب 0.02. معنى كده إن **ضبط لهجة الرد لسه ضعيف عند كل النماذج** | [مؤكدة] نفس المصدر (2026-10-21) |
| نفس الورقة | Claude **ماتقيّمش** | [مؤكدة] نفس المصدر (2026-10-21) |
| AraDiCE (COLING 2025) | النماذج أداؤها أحسن على **الخليجية** مقارنة بالمصرية والشامية. والنماذج العربية (Jais وAceGPT) غلبت Llama 3 وMistral، وده جيل قديم | [مؤكدة] https://aclanthology.org/2025.coling-main.283.pdf (2026-10-21) |
| DialectalArabicMMLU (LREC 2026)، بيغطي اللهجات السورية والمصرية والإماراتية والسعودية والمغربية | فجوات كبيرة بين اللهجات في 19 نموذج مفتوح من 1B لـ13B. ومفيش نماذج frontier مغلقة في التقييم | [مؤكدة] https://aclanthology.org/2026.lrec-1.251/ (2026-10-21) |
| Falcon-H1-Arabic (تقرير الشركة نفسها) | AraDiCE (مصري وخليجي وشامي): حوالي 53 للـ34B، وOALL حوالي 75% | [مؤكدة، ادعاء ذاتي من TII] https://huggingface.co/blog/tiiuae/falcon-h1-arabic (2026-10-21) |
| Claude Multilingual (MMLU مترجم للفصحى) | العربي بيوصل 97.2% من الإنجليزي في Sonnet 4.5، و92.5% في Haiku 4.5. **مفيش أرقام منشورة لـ5.x ولا للهجات** | [مؤكدة] https://platform.claude.com/docs/en/build-with-claude/multilingual-support (2026-10-21) |
| HELM Arabic / HELM Arabic Enterprise (Stanford CRFM مع Arabic.AI) | Arabic.AI LLM-X في المركز الأول (0.826 في Enterprise)، وQwen3-235B أحسن نموذج مفتوح (0.786). بس الشريك اللي عمل التقييم هو نفسه صاحب النموذج الأول، **وده تضارب مصالح** | [مؤكدة من مصدر ثانوي] https://www.middleeastainews.com/p/stanford-helm-arabic-enterprise-bench (2026-10-21) |

**التحليل:**
- [افتراض] **الفهم** (Understanding) للهجة السعودية والمصرية في رسائل واتساب القصيرة غالبًا مش هو عنق الزجاجة مع النماذج الكبيرة المغلقة. العقبة الحقيقية في **الرد بلهجة طبيعية ومتسقة مع "صوت" البراند**، وفي الكلمات المحلية في الكتالوج (زي "دهن عود" و"مخمرية" وأسماء مقاسات محلية).
- [افتراض] النماذج الرخيصة (Haiku 5.5 وGPT-6 Luna وFlash-Lite) **مش متقاسة** على اللهجات. لازم تتقارن بـeval داخلي قبل ما نختار الافتراضي.
- [اقتراح] نخلي لهجة الرد إعداد على مستوى الـTenant (سعودي أو مصري أو فصحى مبسطة)، ومعاه 10–20 مثال few-shot من محادثات التاجر الحقيقية، وكلهم جوه الـcached prefix.

---

## 3. Embeddings للبحث في المنتجات بالعربي

| الخيار | السعر | دليل الجودة بالعربي | التصنيف |
|---|---|---|---|
| OpenAI text-embedding-3-small / -large | $0.02 / $0.13 لكل 1M token | مفيش دليل عربي حديث موثوق لقيته | [مؤكدة للسعر] https://developers.openai.com/api/docs/pricing (2026-10-21). الجودة **غير مؤكدة** |
| Gemini Embedding 2 (`gemini-embedding-2`) | $0.20 لكل 1M token نص، وBatch بـ$0.10 | **غير مؤكدة** للعربي | [مؤكدة للسعر] https://ai.google.dev/gemini-api/docs/pricing (2026-10-21) |
| BGE-M3 (open-source) | self-host | أعلى نتيجة إجمالية (70.99) في دراسة RAG عربية منهجية، وبعده multilingual-e5-large (70.31). وbge-reranker-v2-m3 حسّن الـfaithfulness | [مؤكدة] https://arxiv.org/abs/2506.06339 (2026-10-21). الرقم 70.99 من ملخص بحث، والملخص الرسمي بيأكد الترتيب |
| EmbeddingGemma 300M | self-host | كسب إجمالًا في اختبار مدونة على 203 استعلام عربي/إنجليزي، وBGE-M3 كان أحسن في عربي→عربي (دليل ضعيف، مش محكّم) | [مؤكدة من مصدر ثانوي] https://via6ai.com/blog/best-embedding-model-multilingual-rag-english-arabic-test (2026-10-21) |
| Cohere Embed | صفحة الأسعار مفيهاش سعر لكل token دلوقتي، فيها بس Model Vault بالساعة ($3–5 للـinstance) | — | [مؤكدة] https://cohere.com/pricing (2026-10-21) |

**الاستنتاج للبحث في المنتجات:**
- [افتراض] تكلفة الـembeddings **مهملة**. كتالوج فيه 2,000 SKU × 200 token يعني حوالي 0.4M token، يعني حوالي **$0.008** بـtext-embedding-3-small أو $0.08 بـGemini Embedding 2 لكل إعادة فهرسة كاملة.
- [اقتراح] البحث في المنتجات لازم يكون **Hybrid**: Postgres full-text/trigram على الاسم والـSKU والماركة، ومعاه pgvector للمعنى، مع normalization عربي (توحيد أشكال الألف، والتاء المربوطة والهاء، والياء والألف المقصورة، وشيل التشكيل والتطويل). السبب إن أسماء العطور والماركات كلمات نادرة والـembeddings وحدها بتغلط فيها.
- [اقتراح] نبدأ بـAPI embedding رخيص (بنفس مزود الـLLM لو ينفع) بدل self-host BGE-M3، لأن الـGPU أو CPU الدائم تكلفة ثابتة مش مبررة في الـMVP (Rule 5 وRule 6). نرجع لـBGE-M3 لو eval البحث طلّع فرق واضح.

---

## 4. تقدير الـTokens والتكلفة لكل محادثة ولكل Recovery flow

### 4.1 الافتراضات (كلها [افتراض] لحد ما تتقاس)
| البند | القيمة المفترضة | ملاحظة |
|---|---|---|
| Static prefix (system prompt، وسياسات، وتعريفات tools، وصوت البراند، وFAQ التاجر، وأمثلة اللهجة) | 5,000 token | متكاش (cached) لكل Tenant |
| عدد رسائل العميل في المحادثة | 8 | محادثة بيع على واتساب: سؤال، ثم توصية، ثم سعر أو شحن، ثم رابط دفع |
| عدد Tool calls | 5 | بحث منتجات، وتفاصيل منتج أو مخزون ×2، وشحن، ورابط سلة |
| حجم نتيجة الـtool | حوالي 700 token | 3–5 منتجات بالاسم والسعر والمخزون والمتغيرات، بـJSON مختصر |
| رسالة العميل | حوالي 40 token | جملة عامية قصيرة. البحث بيقول ~2.4 token لكل كلمة عربية مقابل 1.5–1.6 للإنجليزي ([مؤكدة] https://arxiv.org/pdf/2602.05374، tokenizers مش محددة) |
| Output لكل استدعاء (مع thinking أو tool call) | حوالي 300 token | effort = low. الـthinking بيتحاسب كـoutput |
| عدد استدعاءات الـAPI | 13 | 8 ردود + 5 جولات tools |
| **إجمالي الـtokens للمحادثة** | Cache read حوالي 100K، وCache write أو input جديد حوالي 15K، وOutput حوالي 4K | الـhistory بيتبعت تاني مع كل استدعاء. والـauto-caching بيحوّل معظمه لـcache reads |

### 4.2 تكلفة المحادثة الواحدة (USD، بالـcaching)
الطريقة: (100K × سعر cache read) + (15K × سعر cache write أو input) + (4K × سعر output). [افتراض للحساب، والأسعار [مؤكدة] من القسم 1]

| النموذج | تكلفة المحادثة | بدون caching (115K input) | ملاحظة |
|---|---|---|---|
| Claude Haiku 5.5 | **حوالي $0.005** | حوالي $0.014 | |
| GPT-6 Luna | حوالي $0.005 | حوالي $0.014 | سعر cache write هنا افتراض (= input) |
| Gemini 3.1 Flash-Lite | حوالي $0.012 | حوالي $0.035 | الـexplicit caching عليه تكلفة تخزين بالساعة |
| Gemini 3.8 Flash | حوالي $0.034، ثم حوالي $0.067 من 2027 | حوالي $0.10 | |
| GPT-5.4 mini | حوالي $0.037 | حوالي $0.10 | |
| Claude Sonnet 5.5 | **حوالي $0.088** | حوالي $0.27 | |
| GPT-6 Sol | حوالي $0.09 | حوالي $0.27 | |
| Claude Opus 5.5 | حوالي $0.175 | حوالي $0.54 | مش مبرر لوكيل محادثة [اقتراح] |

- [افتراض] الحساسية: لو الـoutput اتضاعف بسبب thinking أطول، Sonnet 5.5 يوصل حوالي $0.13. ولو الـhistory اتضاعف (محادثة 16 رسالة)، التكلفة تقريبًا ×2.
- [افتراض] الـcaching بيوفر حوالي **60–70%**، بشرط إن الـprefix يفضل ثابت بالبايت (مفيش timestamp ولا ترتيب tools متغير). الحد الأدنى للـcache في Claude 5.x هو 512 token (من وثائق أداة claude-api الداخلية، والمطلوب يتأكد من الصفحة الرسمية).

### 4.3 تكلفة Recovery flow (سلة متروكة)
**قيد منصة حاسم:** خارج نافذة الـ24 ساعة (Customer Service Window) مش مسموح إلا بالـ**Templates**. يعني الـAI مش بيكتب رسالة الاسترجاع بحرية، بيختار template معتمد ويملأ متغيراته. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing (2026-10-21)

| المرحلة | الـTokens (افتراض) | Haiku 5.5 | Sonnet 5.5 |
|---|---|---|---|
| اختيار template وملء المتغيرات (prefix 2K cached + بيانات السلة 500 + output 150) | حوالي 2.65K | حوالي $0.00015 | حوالي $0.0027 |
| لو العميل رد (نفترض 20% [افتراض]) بتبدأ محادثة قصيرة بنص حجم محادثة البيع | — | 0.2 × $0.0025 | 0.2 × $0.044 |
| **إجمالي لكل Recovery flow** | | **حوالي $0.0007** | **حوالي $0.012** |
| مع Batch API لمرحلة التوليد (خصم 50%) | | أقل | أقل |

### 4.4 مقارنة إلزامية: رسوم WhatsApp أكبر بكتير من تكلفة الـLLM
- من **2026-10-01** رسائل الـ**Service** (يعني ردود الوكيل جوه نافذة الـ24 ساعة) **بقت بفلوس** بعد أول 1,000 رسالة مجانية في الشهر لكل رقم. ورسائل الـUtility جوه النافذة كمان بقت بفلوس. [مؤكدة] https://developers.facebook.com/documentation/business-messaging/whatsapp/pricing (2026-10-21). وده بيحسم نقطة كانت مكتوبة "لم يُتحقق" في `docs/research/thikaa.md`.
- أسعار السعودية بعد 2026-10-01: Marketing حوالي $0.0576 (SAR 0.2159)، وService/Utility/Authentication حوالي $0.0107 (SAR 0.0401) للرسالة، من غير VAT. [مؤكدة من مصدر ثانوي فقط، BSP] https://chatmaxima.com/whatsapp-api-pricing/saudi-arabia/ (2026-10-21). صفحة Meta بتأكد إن سعر Marketing السعودي زاد في 2026-04-01 وفي 2026-10-01، بس من غير ما تذكر الأرقام.
- [افتراض/حساب] محادثة بيع فيها 8 ردود من الوكيل بتكلف حوالي **$0.086 رسوم Meta**، مقابل حوالي **$0.005 بـHaiku 5.5**. ورسالة Recovery الواحدة (Marketing template) بتكلف حوالي **$0.058**، مقابل حوالي **$0.0007 بـHaiku**. **يعني رسوم المنصة بتساوي من 10x لـ80x من تكلفة الـLLM الرخيص.**
- **Meta Business Agent:** فئة جديدة من 2026-07-01، بتتحاسب **$2.00 لكل 1M token** بسعر عالمي واحد، يعني وكيل AI من Meta نفسها جوه واتساب. [مؤكدة] نفس صفحة Meta (2026-10-21). **تضارب داخلي في الصفحة:** مكتوب إنها "4–5 سنت لكل رسالة نموذجية"، وده معناه 20–25K token للرسالة، وده رقم غريب. [افتراض] ده **تهديد استراتيجي**، لأن Meta ممكن تحوّل "وكيل المبيعات على واتساب" لسلعة.

---

## 5. الاستضافة داخل السعودية (In-region hosting)

| الخيار | الحالة في 2026-10-21 | ضمان المعالجة داخل المملكة | التصنيف والمصدر |
|---|---|---|---|
| Anthropic API المباشر | Global افتراضيًا، والخيار الجغرافي الوحيد الموثّق هو `us` (×1.1) | لا | [مؤكدة] https://platform.claude.com/docs/en/about-claude/pricing (2026-10-21) |
| Google Vertex AI، منطقة الدمام (me-central2) | Claude (Haiku 5.5 وSonnet 5.5 وOpus 5.5 وغيرهم) وGemini وGemini Embedding موجودين في جدول منطقة الشرق الأوسط اللي فيه الدمام | الصفحة بتقول إن الـendpoints "don't guarantee data residency or in-region ML processing"، وصفحة data residency **مش ذاكرة الدمام** ضمن مناطق التزام المعالجة. المناطق المذكورة لنماذج الشركاء: US وEU وبلجيكا وهولندا وسنغافورة وتايوان | [مؤكدة] https://docs.cloud.google.com/vertex-ai/generative-ai/docs/learn/locations و https://docs.cloud.google.com/vertex-ai/generative-ai/docs/learn/data-residency (2026-10-21). التوفر الفعلي لكل نموذج في الدمام **غير مؤكد** (الجدول ماظهرش بعلامات التوفر) |
| AWS Saudi Region | **مش متاح لسه**، ومخطط إطلاقه في ديسمبر 2026. وALLaM جاي على Bedrock "قريبًا". وAWS وHUMAIN عندهم AI Zone في الرياض بقدرة لحد 50MW بحلول 2028 | — | [مؤكدة] https://www.aboutamazon.com/news/aws/aws-cloud-region-saudi-arabia (2026-10-21) |
| AWS UAE (me-central-1) وBahrain (me-south-1) كمناطق "قريبة" | **ضربات drones في مارس 2026** طلّعت منطقتين من 3 (AZs) في الإمارات من الخدمة. AWS نصحت العملاء ينقلوا شغلهم لمناطق تانية. وفي سبتمبر 2026 أعلنت إنها مش هتقدر تسترجع بيانات كانت متخزنة في mec1-az2 بس، ولا بيانات Bahrain | — | [مؤكدة] https://www.theregister.com/2026/03/02/amazon_outages_middle_east/ و https://www.techzine.eu/news/infrastructure/144322/aws-cant-recover-all-data-from-data-centers-hit-in-middle-east/ (2026-10-21) |
| Microsoft Azure Saudi Arabia East | مخطط في **نوفمبر 2026**. توفر Azure OpenAI أو Claude فيها من أول يوم **غير مؤكد** | — | [مؤكدة للموعد] https://english.aawsat.com/technology/5313290-microsoft-make-its-saudi-arabia-datacenter-region-available-november (2026-10-21) |
| Oracle OCI Riyadh (Generative AI) | ادعاء ثانوي بالتوفر، والصفحة الرسمية اللي فتحتها مأكدتهوش | — | **غير مؤكدة** |
| HUMAIN ALLaM 34B | HUMAIN بتقول إنه مستضاف ومُشغَّل في السعودية، بس متاح كتطبيق Chat، والـAPI لسه "coming soon" | نعم (ادعاء الشركة)، بس من غير API | [مؤكدة من مصدر ثانوي] https://vision2030.ai/analysis/humain-allam-microsoft-foundry-enterprise-ai/ (2026-10-21) |
| Self-host لنماذج مفتوحة (ALLaM-7B، وFalcon-H1-Arabic، وJais 2) على GPU سعودي | ممكن تقنيًا | نعم | [اقتراح] **مرفوض للـMVP**: تكلفة GPU ثابتة، وتشغيل معقد، وجودة الحوار باللهجة أقل من النماذج المغلقة (القسم 2) |

**الإطار التنظيمي:**
- لائحة نقل البيانات الشخصية خارج المملكة (PDPL) اتعدلت في سبتمبر 2024. النقل مسموح من غير قرار ملاءمة (adequacy) بضمانات مناسبة: SCCs معتمدة من SDAIA، أو BCRs، أو شهادات. ولازم risk assessment في حالات معينة، زي النقل المستمر أو واسع النطاق أو البيانات الحساسة. [مؤكدة من مصدر ثانوي، مكتب محاماة] https://www.clydeco.com/en/insights/2025/03/update-on-saudi-arabia-risk-assessment-guidelines و https://securiti.ai/regulation-on-personal-data-transfer-outside-the-kingdom (2026-10-21)
- إذا كانت قائمة الدول الملائمة اتنشرت ولا لأ: **غير مؤكدة**.
- [افتراض] رسائل العملاء (أسماء، وأرقام، وعناوين، وطلبات) بيانات شخصية، ومعالجتها بـLLM API خارج المملكة محتاجة SCCs وrisk assessment وتحديث سياسة الخصوصية. **محتاج رأي قانوني**، والمعلومة دي مش بديل عنه.

---

## 6. التحكم في الهلوسة في الأسعار والمخزون

| التقنية | التفصيل | التصنيف |
|---|---|---|
| السماح بـ"لا أعرف" | التصريح للنموذج إنه يعترف بعدم المعرفة "can drastically reduce false information" | [مؤكدة] https://platform.claude.com/docs/en/test-and-evaluate/strengthen-guardrails/reduce-hallucinations (2026-10-21) |
| تقييد المعرفة الخارجية | "only use information from provided documents" | [مؤكدة] نفس المصدر |
| التحقق بالاقتباس أو الـcitation | كل claim لازم يتربط بمصدر، ولو ملوش مصدر يتشال | [مؤكدة] نفس المصدر |
| الهلوسة أقل في الخصائص المنظَّمة | في كشف الهلوسة في product listings: الـprecision والـrecall أحسن بكتير على الخصائص المنظَّمة مقارنة بغير المنظَّمة | [مؤكدة] https://aclanthology.org/2024.ecnlp-1.4 (2026-10-21) |
| **Tool calling للكتالوج الحي** | السعر والمخزون **مايتكتبوش أبدًا من ذاكرة النموذج ولا من الـprompt**. لازم ييجوا من `get_product(id)` اللي بيقرا من Salla أو من نسخة متزامنة عليها TTL قصير | [اقتراح] |
| **Validator بعد التوليد** (حتمي، بالكود) | كل رقم أو سعر أو كمية أو نسبة خصم في الرد لازم يطابق قيمة رجعت من tool في نفس المحادثة. لو مش مطابق، الرد يتمنع ويتعاد توليده أو يتحول لإنسان | [اقتراح] |
| **Placeholders بدل الأرقام** | النموذج يكتب `{{price:SKU123}}` والكود يعوّض بالقيمة من الكتالوج، وبالشكل ده النموذج مستحيل يخترع سعر | [اقتراح] من أقوى الضمانات وأرخصها |
| Structured / strict tools | `strict: true` بيضمن إن مدخلات الـtool مطابقة للـschema | [مؤكدة] وثائق أداة claude-api (Tool Use quick reference)، والمطلوب يتأكد من التوثيق العام |
| الخصومات والمبالغ | الـAI **يقترح** بس. محرك سياسات بالكود (حد أقصى للخصم، وهامش، وموافقة التاجر) هو اللي ينفّذ (Rule 9) | [اقتراح] |
| إحصائيات معدلات الهلوسة المنتشرة (6.8% و18% لـ35%) | من مدونات ومش متتبعة لمصدر أولي | **غير مؤكدة** |

---

## 7. تقييم الوكلاء العرب (Evaluation)

| المكوّن | الوصف | التصنيف |
|---|---|---|
| BALSAM (SDAIA وKSAA) | 78 مهمة و52K مثال، والـtest set مخفي (blind) لتقليل التلوث. والورقة بتقول إن LLM-as-a-Judge "highly correlates with human judgments"، بعكس BLEU وROUGE وBERTScore | [مؤكدة] https://arxiv.org/html/2507.22603v1 (2026-10-21) |
| HELM Arabic / Enterprise | مفيد كمؤشر عام، بس معظمه فصحى ومش مبيعات، وفيه تضارب مصالح (القسم 2) | [مؤكدة من مصدر ثانوي] |
| AraDiCE وDialectalArabicMMLU وArabCulture-Dialogue | مؤشرات على اللهجات، بس مش على سلوك وكيل مبيعات | [مؤكدة] (القسم 2) |
| **Golden set داخلي** | 200–300 محادثة حقيقية بعد إخفاء هوية أصحابها، مقسمة بالتساوي تقريبًا بين سعودي (نجدي وحجازي) ومصري وفصحى، من أول 3–5 تجار | [اقتراح] |
| مقاييس "صفر تسامح" | دقة السعر والمخزون (exact match مع الكتالوج = 100%)، ومفيش خصم من غير سياسة، والتحويل لإنسان لما يكون مطلوب | [اقتراح] |
| مقاييس جودة | ملاءمة اللهجة (حكّام بشر من السعودية ومصر + LLM judge متعاير معاهم)، والإيجاز، والوصول لرابط الدفع | [اقتراح] |
| Red-teaming | محاولات انتزاع خصم، وPrompt injection في أسماء أو أوصاف المنتجات، وأسئلة عن منافسين، وطلب بيانات عملاء تانيين (Cross-tenant) | [اقتراح] |
| Regression | يشتغل مع كل تغيير في الـprompt أو النموذج أو الـtools، ويتسجل معاه تكلفة المحادثة (tokens) | [اقتراح] |
| Shadow mode | الوكيل يقترح الرد والموظف يوافق، وده بيدينا بيانات eval مجانية وبيقلل المخاطرة في أول تجار | [اقتراح] |

---

## 8. الحد الأدنى لمعمارية الـAI (Minimum Viable AI Architecture)

**الأدلة:**
- Anthropic: "the most successful implementations weren't using complex frameworks or specialized libraries". و"start by using LLM APIs directly". والـworkflows مناسبة للمهام المحددة، والـagents للمهام المفتوحة. [مؤكدة] https://www.anthropic.com/engineering/building-effective-agents (2026-10-21)
- OpenAI: ابدأ بـsingle agent، وانتقل لـmulti-agent بس لما الـprompt يبقى مليان شروط أو الـtools تتداخل. [مؤكدة من مصدر ثانوي فقط، ملف الـPDF الأصلي ماتقريش] https://www.maginative.com/article/how-to-build-ai-agents-a-detailed-practical-guide-from-openai/ (2026-10-21)
- LangGraph: إطار "low-level" لوكلاء stateful طويلة التشغيل (durable execution، وhuman-in-the-loop، وmemory). الصفحة بتعرض Python بس ومفيش PHP SDK. وبتنصح المبتدئين بأدوات أعلى مستوى. [مؤكدة] https://docs.langchain.com/oss/python/langgraph/overview (2026-10-21)

**التوصية [اقتراح]:**

| القرار | الاختيار للـMVP | السبب |
|---|---|---|
| عدد الوكلاء | **وكيل واحد + tools** لمحادثة البيع | المحادثة نطاقها محدود، والـtools حوالي 6–8، ومفيش تداخل |
| الاسترجاع والتحليلات | **Workflow حتمي بالكود** (Laravel Jobs/Queues) فيه استدعاء LLM واحد لكل خطوة | المسار معروف مقدمًا: trigger، ثم انتظار، ثم template، ثم متابعة |
| LangGraph أو Python service | **لا** في الـMVP | هيضيف خدمة ولغة تانية (Rule 6). وقوائم Laravel وجدول حالة في Postgres بيغطوا الـdurability والـhuman handoff |
| الـtools المقترحة | `search_products` (hybrid)، و`get_product` (سعر ومخزون حي)، و`get_shipping_info`، و`create_cart_link`، و`get_order_status`، و`handoff_to_human`، و`propose_discount` (اقتراح بس، والتنفيذ عند محرك السياسات) | أقل مجموعة بتقفل محادثة بيع |
| Model routing | نموذج رخيص افتراضي (Haiku 5.5 أو ما يكافئه بعد الـeval)، والتصعيد لـSonnet 5.5 بقاعدة واضحة (فشل validator، أو قيمة سلة عالية، أو شكوى) | [افتراض] بيوفر 75–95% مقابل استخدام Sonnet في كل حاجة (القسم 9). وده غير الكاش، لأن الكاش model-scoped |
| تجريد المزود | Interface داخلي واحد (`LlmGateway`) فيه metering لكل Tenant | يسمح بالتبديل بين Anthropic وOpenAI وGemini (أو ALLaM لما ينزل API)، ويسمح بالـfallback |
| Metering | تسجيل `usage` (input وcache read/write وoutput) لكل استدعاء ولكل Tenant | أساس للـBilling/Usage وللـpricing |

متى نعيد النظر في multi-agent أو LangGraph [اقتراح]: لما يكون عندنا أكتر من 3 مسارات مستقلة (مبيعات، ودعم ما بعد البيع، وحملات ذاتية) بـtools متداخلة، أو مهام بتستمر ساعات وفيها نقاط موافقة متعددة.

---

## 9. AI COGS الشهري لكل Tenant (تقدير)

### 9.1 افتراضات الاستخدام [افتراض]
| المستوى | محادثات AI في الشهر | Recovery flows في الشهر | ملاحظات |
|---|---|---|---|
| Light | 300 | 500 | متجر صغير |
| Medium | 1,500 | 2,000 | متجر متوسط |
| Heavy | 6,000 | 8,000 | متجر كبير أو موسم (رمضان، اليوم الوطني، Black Friday) |
| Overhead | +30% | | ملخصات التحليلات، وإعادة المحاولات، وvalidators، وeval sampling، وembeddings |

### 9.2 الاستراتيجيات
- **A اقتصادي:** Haiku 5.5 (أو GPT-6 Luna) لكل حاجة. المحادثة حوالي $0.005، والاسترجاع حوالي $0.0007.
- **B توجيه (موصى به كبداية [اقتراح]):** 80% Haiku 5.5 و20% تصعيد لـSonnet 5.5. المحادثة حوالي $0.0215، والاسترجاع حوالي $0.001.
- **C ممتاز:** Sonnet 5.5 لكل حاجة. المحادثة حوالي $0.088، والاسترجاع حوالي $0.012.

### 9.3 النتيجة (USD في الشهر لكل Tenant، شاملة 30% overhead) [افتراض/حساب]

| المستوى | A اقتصادي | B توجيه | C ممتاز |
|---|---|---|---|
| Light | **حوالي $2–3** | **حوالي $9** | **حوالي $42** |
| Medium | **حوالي $12** | **حوالي $45** | **حوالي $200** |
| Heavy | **حوالي $46** | **حوالي $180** | **حوالي $800** |

**النطاق العملي المقترح للتخطيط المالي [افتراض]:** حوالي **$5–15** للـLight، و**$15–60** للـMedium، و**$50–250** للـHeavy (استراتيجية A أو B، مع هامش ±50% لعدم اليقين في الـtokens العربية والـthinking).

### 9.4 للمقارنة: رسوم WhatsApp لنفس الحجم [افتراض/حساب بأسعار ثانوية]
| المستوى | Service messages (8 لكل محادثة، ناقص 1,000 مجانية) | Marketing templates (1 لكل recovery) | الإجمالي التقريبي |
|---|---|---|---|
| Light | 2,400 − 1,000 = 1,400 × $0.0107 ≈ $15 | 500 × $0.0576 ≈ $29 | **≈ $44** |
| Medium | 11,000 × $0.0107 ≈ $118 | 2,000 × $0.0576 ≈ $115 | **≈ $233** |
| Heavy | 47,000 × $0.0107 ≈ $503 | 8,000 × $0.0576 ≈ $461 | **≈ $964** |

**الاستنتاج:** مع استراتيجية A أو B، **رسوم Meta بتساوي من 4x لـ20x تكلفة الـLLM**. من هنا، مين اللي بيدفع رسوم Meta (حساب WABA بتاع التاجر ولا حسابنا) قرار تسعير أهم من اختيار النموذج.

---

## انعكاسات على CODEX

1. **[اقتراح] تكلفة الـLLM مش هي الخطر الأكبر على الهامش، طول ما بنستخدم نموذج رخيص مع caching وrouting.** الخطر الحقيقي في رسوم WhatsApp (Service بقت بفلوس من 2026-10-01) وفي سوء استخدام النماذج الغالية. **التسعير لازم يفصل رسوم Meta** (Pass-through، أو على WABA التاجر)، أو يحسبها صراحةً في الباقات.
2. **[اقتراح] Model-agnostic من أول يوم** عن طريق `LlmGateway` داخلي، لأن الأسعار بتتغير كل شهر أو اتنين: Haiku 5.5 نزل 2026-10-07، وGPT-6 في 2026-09-22، وGemini Flash هيتضاعف في 2027-01-01.
3. **[اقتراح] الافتراضي نموذج رخيص (Haiku 5.5 أو GPT-6 Luna)، والتصعيد لـSonnet 5.5 بقواعد.** والاختيار النهائي **بعد eval داخلي باللهجات**، مش من الـbenchmarks العامة.
4. **[اقتراح] السعر والمخزون عمرهم ما يطلعوا من النموذج:** Placeholders + Validator حتمي + tool لكتالوج حي. والخصومات تتنفذ بمحرك سياسات بالكود بس (Rule 9).
5. **[اقتراح] من غير LangGraph ومن غير multi-agent في الـMVP.** وكيل واحد + tools للمحادثة، وworkflows بالكود في Laravel للاسترجاع والتحليلات (Rule 6).
6. **[اقتراح] Data residency:** مفيش النهارده خيار LLM مُدار بضمان معالجة داخل المملكة **وبسعر منشور**. نبدأ بمزود عالمي + SCCs + risk assessment تحت PDPL (محتاج رأي قانوني)، ونصمم الـgateway بحيث نقدر نتحول لـBedrock KSA أو Azure Saudi East أو ALLaM API لما يتأكد توفرهم (الربع الأول من 2027 تقريبًا [افتراض]).
7. **[اقتراح] منستخدمش مناطق AWS في الإمارات والبحرين كـ"بديل قريب"** بعد أضرار 2026، ولازم نعمل backup لبيانات العملاء في منطقة تانية.
8. **[افتراض] تهديد تنافسي:** فئة "Meta Business Agent" ($2 لكل 1M token) معناها إن Meta ممكن تقدم وكيل مبيعات أصلي جوه واتساب. الميزة لازم تكون في **ربط سلة والهامش والسياسات والتحليلات**، مش في "بوت بيرد بالعربي".
9. **[اقتراح] Metering لكل Tenant** (tokens ورسائل Meta) من أول Sprint، لأنه أساس الـBilling وأساس اكتشاف المستأجرين اللي بيخسّرونا.
10. **[اقتراح] Eval بلهجات حقيقية** (سعودي ومصري) جزء من Exit Criteria لمرحلة الـAI Sales Agent: دقة سعر 100%، وملاءمة لهجة ≥ عتبة يحددها المؤسس، وتكلفة المحادثة ≤ $0.03.

## أسئلة مفتوحة

1. مين اللي هيدفع رسوم WhatsApp: حساب WABA بتاع التاجر (نبقى Tech Provider) ولا حساب CODEX كـBSP؟ القرار ده بيغيّر الـCOGS بـ4x لـ20x.
2. ما هو سعر رسائل Service/Utility للسعودية من **rate card Meta الرسمي** (بالـSAR)؟ الرقم الحالي من BSP بس.
3. ما هو معدل الـtokens الفعلي للعربي العامي على Claude 5.x وGPT-6 وGemini 3.x؟ (نقيسه بـcount_tokens على 100 محادثة حقيقية).
4. هل Haiku 5.5 أو GPT-6 Luna أداؤه كفاية في الرد بلهجة سعودية أو مصرية طبيعية؟ مفيش أي benchmark منشور، ومحتاجين eval داخلي.
5. إمتى بالظبط هيبقى ALLaM متاح كـAPI (HUMAIN أو Bedrock أو Foundry)؟ وبكام؟ وهل هيقدّم معالجة داخل المملكة؟
6. هل Vertex AI في الدمام بيعالج طلبات Claude وGemini فعليًا داخل المملكة؟ (التوثيق مش بيضمن، ومحتاجين تأكيد من Google Cloud كتابةً).
7. هل نشرت SDAIA قائمة الدول الملائمة لنقل البيانات؟ وهل SCCs + risk assessment كفاية لعملائنا من المؤسسات؟
8. هل هيكون فيه نموذج Claude أو OpenAI في Azure Saudi Arabia East أو AWS KSA أول ما يطلقوا (نوفمبر وديسمبر 2026)؟
9. ما هو معدل الرد على رسائل الاسترجاع في السعودية ومصر؟ (افترضنا 20%، ومحتاج بيانات من تجار حقيقيين).
10. هل الـfew-shot الخاص بكل Tenant + cached prefix حجمه 5K واقعي، ولا FAQ التجار والسياسات هيخلوه 10–20K؟ ده بيضاعف الـcache reads، بس تأثيره على التكلفة محدود لأن سعر الـcache read منخفض.
