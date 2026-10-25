# ADR-007: الـAI orchestration = Agent واحد + 5 tools ورا `AiGateway`

- **الحالة:** Accepted (المؤسس، 2026-10-25)
- **صاحب القرار:** المؤسس
- **المرجع:** [09](../blueprint/09-technical-architecture.md) §9.11 و§9.12، و[14](../blueprint/14-key-decisions.md) D-T08 وD-T09، وقصص E09/E10/E11 في [06](../blueprint/06-mvp-vs-future-matrix.md).
- **بيعدّل على الـBlueprint في نقطة واحدة:** التصعيد لـSonnet اتشال (قرار المؤسس، شوف "البدائل").

## القرار باختصار

| البند | القرار |
|---|---|
| الشكل | **Agent واحد** بـ5 tools محدودة بالاسم. مفيش multi-agent ولا graph runtime |
| الباب الوحيد للـLLM | `App\Modules\AiGateway\AiGateway`، ومفروض بـarch tests |
| الـSDK | **Laravel AI SDK الرسمي** (`laravel/ai` ^1.3، في `composer.json`) |
| الموديل | **Claude Haiku 5.5 بس** (`claude-haiku-5-5`). مفيش تصعيد لموديل أغلى |
| الـEmbeddings | **Voyage AI `voyage-4`**، 1024 بُعد (Anthropic معندهاش embeddings endpoint، والـSDK فيه provider لـVoyage) |
| السقف اليومي | **$5 لكل تاجر** افتراضيًا، والتاجر يقدر **ينزّله بس** |
| الوضع | **Assisted بس** متفعّل. Scoped Autonomous متبني ومقفول |
| الأمان | Validator حتمي للأرقام والروابط، retry واحد، وبعدها handoff. قواعد handoff بالكود قبل الموديل |

## 1. الشكل: Agent واحد و5 tools

الـtools متعرّفة في `app/Modules/SalesAgent/Toolbox.php` (`Toolbox::ALLOWED`)، وكل واحدة في `app/Modules/SalesAgent/Tools/`:

| Tool | بيعمل إيه | ملاحظات |
|---|---|---|
| `search_products` | بحث hybrid في كتالوج التاجر | بيرجّع **IDs وأسماء وفئة بس**، من غير أسعار. المنتجات المخفية والممسوحة مبتظهرش |
| `get_product` | السعر والمخزون والـvariants لمنتج واحد | **المصدر الوحيد لأي رقم** يظهر للعميل. المخزون بالرقم بيظهر بس لو ≤5. `was_price_sar` بيظهر بس وقت تخفيض حقيقي. لو السعر أقدم من 15 دقيقة بيرمي `PriceTooOld` |
| `get_policy` | نص الشحن أو الاسترجاع أو الـFAQ اللي التاجر كتبه | لو التاجر مكتبش حاجة: `found=false` والـrun بيتحوّل لإنسان (`policy_missing`) |
| `get_cart_link` | `checkout_url` للسلة المتروكة، أو رابط صفحة منتج | **مفيش إنشاء cart ولا payment link**. مفيش رابط لمنتج خلصان |
| `handoff` | تحويل لإنسان | متاح دايمًا (شرط Meta). أي نداء ليه بيخلّي الـrun يخلص handoff مهما كان النص |

**قواعد مفروضة بالكود، مش بالـprompt (Rule 9):**
- `Toolbox::forRun()` بيرمي exception لو القائمة اختلفت عن `ALLOWED`.
- `tests/Arch/ArchitectureTest.php`: القائمة لازم تبقى بالظبط الـ5 دول، ومفيش ملف tool اسمه فيه `discount|coupon|voucher|payment|refund|order|خصم|كوبون`. والـtools ممنوع تستخدم `Recovery` أو `Messaging` أو `CommerceAdapter`/`CommerceRecorder`/`Salla` أو `Http` أو `DB`. يعني tool ميقدرش يبعت رسالة ولا يكتب في سلة ولا يغيّر طلب.
- الـtenant والعميل (`subjectHash`) بييجوا من سياق الـjob على السيرفر (`RunContext`)، **مش من arguments الموديل**. الموديل ميقدرش يطلب بيانات متجر تاني حتى تحت prompt injection.
- لو tool وقع، الموديل بيشوف `{"error":"unavailable"}` بس (من غير نص الخطأ)، وأرقامه مبتتحسبش، والـrun بيتحوّل لإنسان (`tool_failed`).

## 2. `AiGateway` الباب الوحيد

`AiGateway::converse()` بيعمل بالترتيب:
1. **السقف اليومي:** لو صرف النهارده (من نص الليل بتوقيت الرياض) ≥ السقف، بيسجّل نداء `cap_reached` ويرجع من غير ما يكلّم الموديل.
2. **Pseudonymization** للتاريخ كله قبل النداء (التفاصيل في [ADR-008](ADR-008-pii-and-llm.md)).
3. نداء الـSDK بحدود: `max_steps=6`، و`max_output_tokens=600`، و`timeout=20s` (`config/codex.php` → `ai`). الـinstructions وتعريفات الـtools عليها `#[CacheInstructions]` و`#[CacheToolDefinitions]`.
4. **أي exception من المزوّد = status** (`provider_unavailable`) مش exception، فاللي بينادي بيعمل handoff آمن دايمًا.
5. **Metering:** صف في `ai_calls` (tokens input/output/cache، التكلفة بالـmicro-USD، latency، عدد الـtools، عدد القيم اللي اتخبّت)، وزيادة `UsageMeter` (`AI_CALLS` و`AI_COST_USD_MICROS`)، وقيد في الـcost ledger (`recordLlm`، `CostKind::Llm`).
6. الـvalidator بيقرا كلام الموديل **قبل** ما الأسماء ترجع، وبعدين `restore()`.

**موديل من غير سعر = exception**: `costUsdMicros()` بيرفض أي موديل مش في `pricing_usd_per_mtok`، عشان محدش يغيّر الموديل ويطلع النداء "ببلاش" في التقارير.

**الفرض بالـtests** (`tests/Arch/ArchitectureTest.php`):

| الـtest | بيمنع إيه |
|---|---|
| `Laravel\Ai` → `toOnlyBeUsedIn('App\Modules\AiGateway')` | أي module تاني يستخدم الـSDK مباشرة |
| `Anthropic`، `OpenAI`، `Prism`، `Gemini` → `not->toBeUsed()` | أي LLM client تاني جنب الـgateway |
| بحث في `app/` (برّه `AiGateway`) عن `anthropic.com`، `openai.com`، `voyageai.com`، ... | HTTP مباشر لمزوّد AI |

الـmodules التانية بتكلّم الـgateway بـ`AgentTool` (interface عندنا) و`AiRequest`/`AiReply`، مش بأنواع الـSDK.

## 3. اختيار الموديل والتكلفة

### الأسعار

| البند | القيمة | التصنيف |
|---|---|---|
| Haiku 5.5 input | $0.10 لكل 1M token | [مؤكدة: https://platform.claude.com/docs/en/about-claude/pricing، 2026-10-25] |
| Haiku 5.5 output | $0.50 لكل 1M token | [مؤكدة: نفس المصدر، 2026-10-25] |
| السعر ده للـprompts لحد 100K token (فوقها $0.50/$2.50) | ملناش prompt قريب من كده | [مؤكدة: مرجع claude-api skill، 2026-10-25] |
| Cache read = 0.1x ($0.01)، cache write = 1.25x ($0.125) | المكتوب في `config/codex.php` | **[افتراض]** يتأكد من صفحة الأسعار قبل الـgo-live |
| أقل prefix بيتعمله cache على Haiku 5.5 | 512 token تقريبًا، وإلا الـcache مبيشتغلش في صمت | [افتراض]: المرجع بيقول "اتأكد من docs الـcaching". الـprompt + الـtools عندنا ~1,000 token [افتراض]، فالمفروض يتعمله cache |
| Voyage `voyage-4` | $0.06 لكل 1M token (في الـconfig) | **[افتراض]** يتأكد من https://docs.voyageai.com/docs/pricing |

### الحساب مقابل هدف ≤ $0.03 للمحادثة (exit criterion)

كل الأرقام دي **[افتراض]** لحد أول تشغيل live للـgolden set (`ai:eval --live` بيسجّل التكلفة الفعلية في `eval_runs.cost_usd_micros`).

| السيناريو | الحساب | التكلفة |
|---|---|---|
| رد عادي (run واحد): 3 steps (بحث، سعر، رد)، ~2,000 input token لكل step، ~100 output | 6,000 × $0.10/M + 300 × $0.50/M | ~$0.00075 |
| محادثة عادية: 8 رسايل من العميل | 8 × $0.00075 | **~$0.006** (5 مرات تحت الهدف) |
| أسوأ حالة لـrun واحد: محاولتين × 6 steps، ~4,000 input و600 output لكل step | 48,000 × $0.10/M + 7,200 × $0.50/M | ~$0.0084 |
| أسوأ حالة لمحادثة: 8 runs كلهم أسوأ حالة | 8 × $0.0084 | **~$0.067 (فوق الهدف)** |

**الخلاصة النقدية:** المتوسط المتوقع تحت الهدف بهامش كبير، بس **السقف النظري مش تحت الهدف**. اللي بيحمينا: الـhandoff الحتمي بيشيل أغلب الحالات الصعبة قبل أي نداء، والسقف اليومي بيقفل الخسارة. ومحتاجين نقيس من `ai_calls` بعد أول تشغيل live قبل ما نعلن إن الـexit criterion اتحقق.

**مخاطرة تكلفة مش محسوبة [افتراض]:** Haiku 5.5 فيه adaptive thinking شغال افتراضيًا (effort افتراضي `medium`)، وتوكنز التفكير بتتحسب output. مش متأكدين الـSDK بيبعت إيه في الإعداد ده. الـ`max_output_tokens=600` بيحدّ الإجمالي لكل step، بس لازم نتأكد في أول تشغيل live إن الردود مبتتقطعش وإن التكلفة زي المتوقع.

### السقف اليومي $5

- الافتراضي في `config/codex.php` (`default_daily_cap_usd = 5.00`)، وبيتخزن لكل تاجر في `agent_settings.daily_cap_usd_micros`.
- `AgentControls::updateLimits()`: التاجر يقدر ينزّل بين $0.10 والافتراضي، **ومينفعش يعلّي فوقه**.
- لما السقف يخلص: handoff بسبب `cap_reached`، **عمر الرد ما بيطلع من غير validator**.
- $5 ≈ ~800 محادثة عادية في اليوم، أو ~75 في أسوأ حالة [افتراض]. ده أكتر من حجم أي pilot متوقع.
- **ملاحظة:** الفحص قبل كل نداء، فممكن النداء الأخير يعدّي السقف بمبلغ صغير (نداء واحد).

## 4. الـValidator، والـretry، والـhandoff

`app/Modules/AiGateway/ResponseValidator.php` حتمي، مفيهوش أي حكم من موديل:

| الفحص | التفاصيل |
|---|---|
| كل رقم في الرد | لازم يساوي قيمة رجعت من tool في نفس الـrun، بعد توحيد الأرقام العربية/الهندية (`١٩٩` = `199`) وفواصل الآلاف والعلامة العشرية |
| الفلوس | القيم اللي اسمها `*_halalas` بتتقبل بالهللة وبالريال الاتنين |
| أرقام بالحروف | رقم مكتوب بالكلام جنب عملة أو `%` ("مية وتسعة وتسعين ريال") بيتمنع، لأنه مينفعش يتراجع |
| **Link guard** | أي رابط في الرد لازم يكون رجع من tool بالظبط. مفيش روابط مخترعة، ولا لمنتج مخفي أو خلصان، ولا phishing |
| tokens الـpseudonymization (`[PHONE_A]`) | مبتتعدّش أرقام |

**التسلسل في `SalesAgent::ask()`:**
1. محاولة أولى. لو الـvalidator منع: `validator_blocks++`، والـprompt بيتزوّد عليه `SystemPrompt::retryNote()` بالأرقام اللي اتمنعت.
2. **محاولة تانية واحدة بس.** لو اتمنعت تاني: handoff بسبب `validator_failed`.
3. لو الرد عدّى الـvalidator: **Discount-mention guard** (`HandoffRules::mentionsDiscount`) بيفحص الرد نفسه. أي رد بيتكلم عن خصم أو كوبون أو عرض بيتحوّل لإنسان (`discount_request`)، حتى لو الموديل اتخدع بنص مزروع في وصف منتج (حالة `hail` في الـgolden set).

## 5. قواعد الـhandoff الحتمية (قبل الموديل)

`app/Modules/SalesAgent/HandoffRules.php` بيشتغل على رسالة العميل **قبل أي نداء للموديل**، بعد شيل علامات الترقيم العربي و`ArabicText::normalize` وشيل البادئات (و، ب، ال، لل، ف):

| الترتيب | السبب | أمثلة من القائمة |
|---|---|---|
| 1 | `asked_human` | موظف، انسان، خدمة العملاء، ابي اكلم، عايز اكلم، real person |
| 2 | `complaint` | شكوى، متأخر، مكسور، ما وصل، وصلني غلط، refund |
| 3 | `discount_request` | خصم، كوبون، تخفيض، عروض، ارخص، آخر سعر، تنزل لي السعر، discount |
| 4 | `high_cart_value` | سلة العميل المفتوحة ≥ حد التاجر (افتراضي 1,500 ر.س [اقتراح]) |

أسباب تانية بتيجي من الـagent أو الحمايات: `agent_handoff`، `validator_failed`، `tool_failed` (ومنه السعر القديم)، `policy_missing`، `cap_reached`، `provider_unavailable`، `empty_reply`. كلها بتتسجّل على الـrun وعلى المحادثة (`ai_` + السبب) وبتظهر في الـinbox.

**[افتراض]** القوايم بداية. الغلط في اتجاه handoff زيادة بيكلّف رد إنسان، والغلط في الاتجاه التاني بيكلّف ثقة. محتاجة مراجعة من متحدث سعودي (في `STATUS.md`).

**الـagent بيسكت تمامًا (skip، مش handoff) لو:** المحادثة متاخد عليها takeover، أو فيه رسالة أحدث من العميل، أو مفيش رقم، أو العميل عمل opt-out أو **مفيش consent موثّق** (`ConsentGate::mayMessage`). النقطة الأخيرة محتاجة قرار منتج (في `STATUS.md`).

## 6. مستويات الاستقلالية

| الوضع | الحالة | الشروط |
|---|---|---|
| `off` | الافتراضي لأي تاجر جديد | — |
| `assisted` | **الوحيد اللي التاجر يقدر يشغّله لوحده** | كل رد = مسودة (`reply_drafts`). الإرسال بعد موافقة عضو بس، جوه نافذة الـ24 ساعة، وبعد فحص الـconsent تاني |
| `scoped_autonomous` | **متبني ومقفول** | التلاتة مع بعض: flag من أدمن CODEX (`autonomy_allowed_by_admin`)، و≥28 يوم Assisted، وموافقة الـOwner المكتوبة بالنص «أوافق على الردود التلقائية» (audited) |

- الـkill switch (`POST /ai/stop`) متاح للـAgent والـOwner.
- معدل التعديل في Assisted بيتقاس (`AgentControls::draftQuality()`)، وده مدخل لقرار الاستقلالية (US-E11-06).
- بالليل في Scoped Autonomous: أي handoff بيبعت النص الثابت `ai_night_handoff` («وصلت رسالتك، وبيرد عليك أحد من فريقنا الصباح إن شاء الله.») جوه النافذة بس. في Assisted مفيش ردود AI بالليل لأن مفيش إرسال من غير موافقة.

### الرجوع التلقائي لـAssisted (US-E10-07)

`BlockRateMonitor`: لو الـvalidator منع في **أكتر من 2%** من الـruns اللي كلّمت الموديل في **آخر 7 أيام**، والـruns ≥ 50، التاجر اللي في Scoped Autonomous بيرجع Assisted تلقائيًا، ويتسجّل `ai.auto_downgraded` في الـaudit و`auto_downgraded_at`. حد الـ50 run **[اقتراح]** عشان منع واحد ميبقاش trend. الـ2% من [13](../blueprint/13-risks-mitigation.md) [اقتراح].

## 7. الـGolden set: offline مقابل live

التفاصيل في [golden-set-guide.md](../phases/P3-ai-agent/golden-set-guide.md).

| | Offline (`php artisan ai:eval`) | Live (`php artisan ai:eval --live`) |
|---|---|---|
| الموديل | سكريبت ثابت لكل حالة (`offline:`)، ولحالات الـhandoff الحتمي مفيش موديل خالص | Haiku 5.5 الحقيقي |
| بيثبت إيه | الحمايات: القواعد، الـvalidator، الـretry، الـtools، الـguards | جودة الموديل نفسه. **ده اللي الـexit criteria بتقيسه** |
| التكلفة | صفر، من غير key | فلوس حقيقية، **محتاج موافقة المؤسس** |
| فين | CI (`tests/Feature/Golden/GoldenSetTest.php`) | يدوي |

أي حالة `price: true` فشلت = **blocking**، والأمر بيرجع failure. الحالة دلوقتي: **80 حالة** (70 سعودي، 10 مصري)، منهم 13 live-only. الوصول لـ≥300 محتاج labeling بشري.

## 8. البدائل اللي اترفضت

| البديل | ليه اترفض |
|---|---|
| **Python / LangGraph** كخدمة منفصلة | Rule 6: خدمة ونشر ولغة تانية لفريق صغير. الـSDK الرسمي فيه tools وagent loop وfake للـtests [مؤكدة: https://laravel.com/docs/13.x/ai-sdk، 2026-10-21]. Python يرجع بس في P4+ بـADR منفصل [اقتراح] |
| **Multi-agent** | مشكلة واحدة بسيطة (رد على سؤال بيع بـ5 tools). Anthropic بتنصح بالأبسط الأول [مؤكدة: https://www.anthropic.com/engineering/building-effective-agents، 2026-10-21]. كل agent زيادة = prompts وتكلفة وأماكن هلوسة زيادة |
| **تصعيد لـSonnet 5.5** (اللي كان في الـBlueprint §9.11.5 وD-T08) | **قرار المؤسس 2026-10-25: Haiku بس.** Sonnet 5.5 أغلى 20 مرة ($2/$10) [مؤكدة: نفس صفحة الأسعار، 2026-10-21]. وبدل "retry على موديل أقوى" عندنا retry على نفس الموديل بملاحظة الأرقام اللي اتمنعت، وبعدها handoff. الدقة جاية من الـvalidator والـtools مش من ذكاء الموديل |
| Prism (community، 0.x) | مش رسمي، والـSDK الرسمي بقى موجود (D-T09) |
| Embeddings من مزوّد الـLLM | Anthropic معندهاش embeddings endpoint، فـVoyage (والـSDK بيدعمه كـprovider) |

## 9. التبعات

| التبعة | التعامل |
|---|---|
| مزوّد واحد (Anthropic) | مفيش failover لمزوّد تاني دلوقتي. أي وقوع = handoff (`provider_unavailable`). تبديل المزوّد = تغيير في `AiGateway` + config بس |
| Haiku ممكن يغلط أكتر في اللهجة أو الترشيح | الأرقام محمية بالـvalidator. الترشيح والفهم بيتقاسوا في الـgolden set live، ومعدل تعديل المسودات في Assisted |
| Handoff كتير | ممكن نعدّي هدف ≤40% handoff. ده تمن الأمان، وبيتقاس من `agent_runs.handoff_reason` |
| كل حاجة متختبرة بـfake | **مفيش key مدفوع اتستخدم لحد دلوقتي.** الـtests بتشغّل agent loop بتاع الـSDK الحقيقي بـ`GatewayAgent::fake()`، و`CODEX_AI_PROVIDER=demo` بيدي ردود جاهزة محليًا (مرفوض في production) |
| تغيير الـprompt | `SystemPrompt::VERSION` لازم يتغيّر، والـgolden set لازم يعدّي |

## 10. نقاط مفتوحة

1. **أول تشغيل live** للـgolden set (موافقة على keys Anthropic وVoyage). التكلفة صغيرة: 80 حالة × ~$0.001 ≈ أقل من 10 سنت [افتراض].
2. تأكيد أسعار الـcache (0.1x/1.25x) وسعر Voyage، وإعداد الـthinking/effort اللي الـSDK بيبعته لـHaiku 5.5.
3. **سلة:** حقول المنتج اتأكدت من https://docs.salla.dev (2026-10-25)، بس **مفيش endpoint موثّق لمنتج واحد**، فالـBlueprint اللي قال "`GET` مباشر لو قديم" مش متاح. الحداثة جاية من الـwebhooks + sync كامل للكتالوج كل 10 دقايق (`commerce:sync-catalog`)، والـagent **بيرفض أي سعر أقدم من 15 دقيقة** وبيحوّل لإنسان. لو الـsync اتأخر، كل أسئلة الأسعار هتتحوّل handoff.
4. قوايم كلمات الـhandoff ونص الـhandoff الليلي محتاجين مراجعة متحدث سعودي.
5. ZDR والنقل برّه المملكة: في [ADR-008](ADR-008-pii-and-llm.md).
