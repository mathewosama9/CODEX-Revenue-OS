# STATUS — CODEX Revenue OS

> بيتحدّث كل خميس ([12](docs/blueprint/12-first-90-days.md) §12.19). آخر تحديث: 2026-10-27.

## 1. المرحلة والـSprint
**P0a — أسبوع 1 (D1)**، من 2026-10-25 لـ2026-10-29. الشغل كله في [W1-checklist.md](docs/phases/P0/W1-checklist.md)، ولحد 2026-10-27 مفيش بند فيه اتقفل.

> **الكود سابق الخطة:** P1 وP2 وP3 اتبنوا قبل Gate 0 (القسم 6). المسار الحرج دلوقتي **برّه الكود**: Meta BV، والمستشار القانوني، و5 تجار يدفعوا Proof Pilot. مفيش features جديدة لحد ما P0 يتحرك.

## 2. Gate 0 (هدفه 2026-12-10)
0 / 10 معايير اتحققت. المعايير العشرة في [07](docs/blueprint/07-master-development-phases.md) و[08](docs/blueprint/08-phase-dependencies.md) §8.4.

## 3. الـScorecard
| المؤشر | الهدف (Gate 0) | الفعلي |
|---|---|---|
| مقابلات في الـICP | ≥20 (هدف 25) | 0 |
| LOIs | ≥8 | 0 |
| Proof Pilots مدفوعة | ≥5 | 0 |
| Cart Truth Audits | ≥5 متاجر | 0 |
| بيفضّلوا "ربح صادق" | ≥60% | — |
| قبلوا holdout 10% | ≥5 | 0 |

## 4. المسارات المنصّاتية
| المسار | الحالة |
|---|---|
| Meta Business Verification (الكيان المصري) | لسه متقدّمش |
| Salla Partners + demo store + private app | لسه |
| GCP Dammam (Invoiced Billing) | لسه |
| المستشار القانوني | لسه |

## 5. أكبر 3 مخاطر دلوقتي
1. تأخر Meta BV بيأخّر P1 كله (المسار الحرج).
2. التحصيل من الكيان المصري محتاج رأي قانوني (VAT وممثل ضريبي) قبل W4.
3. مصدر الموافقة التسويقية (consent) ممكن يقلّل السلات المؤهلة.

## 6. البناء
**P1 Backend — ✅ خلص** على branch `feat/S1-foundation` (2026-10-25). `composer ci:check` أخضر: **269 test**، وPHPStan، والـformatting، والـRTL.

| Epic | الحالة |
|---|---|
| E01 Workspace + أدوار Owner/Agent | ✅ |
| E02 Salla Adapter (install، webhooks، token refresh بـlock، reconciliation، backfill) | ✅ (محتاج spike حي) |
| E03 WhatsApp (Embedded Signup، MM API، webhooks، opt-out، الجودة) | ✅ (محتاج spike حي) |
| E04 Consent + opt-out عربي + quiet hours ورمضان | ✅ |
| E05 Recovery workflow + سياسة الخصم + الموافقات | ✅ |
| E06 Holdout + exposures + cost ledger | ✅ |
| E07 Incremental Profit Report (نطاق + احتمال + حكم + SRM + snapshots) | ✅ |
| E08 الهامش لكل فئة | ✅ |
| E12a Handoff Inbox lite | ✅ |
| E13a Usage metering | ✅ |
| E14 Admin داخلي (Filament + 2FA + صحة المتاجر + kill switch) | ✅ |
| E15 Security (audit append-only، تشفير، redaction، retention، طلبات البيانات) | ✅ |
| **الواجهة (Next.js)** | ✅ (شوف تحت) |

دليل الاختبار: [docs/phases/P1/testing-guide.md](docs/phases/P1/testing-guide.md).

**P2 الواجهة (Next.js) — ✅ خلصت** على branch `feat/P2-web` (2026-10-25)، في فولدر `web/` ([ADR-002](docs/adr/ADR-002-frontend-nextjs.md)). الـ8 شاشات: الدخول (+2FA ونسيت كلمة المرور)، الإعداد، إعدادات الاسترجاع، موافقات الخصم، تقرير الربح، المحادثات، الاستهلاك، طلبات البيانات. عربي RTL وإنجليزي LTR، وNavy وLight. `composer ci:check` أخضر، و**10 اختبارات E2E** (Playwright على الـAPI الحقيقي). دليل الاختبار: [docs/phases/P2-frontend/testing-guide.md](docs/phases/P2-frontend/testing-guide.md).

**مراجعة P1 وP2 — ✅ اتقفلت** (4 sessions، كل البنود في [قائمة المشاكل](docs/reviews/2026-10-25-p1-p2-issues.md)). اتعمل فيها: شاشة الإعدادات، وموافقات العملاء (CSV)، والصدق في القياس والفلوس، وربط واتساب والـsession، والتشغيل. وInertia اتمسح، وLaravel بقى API بس. الحالة: 248 test أخضر و20 اختبار E2E.

**P3 الـAI Sales Agent — 🟡 متبني** (PR #9 اتدمج في `main`، والتصليحات بعده على الـbranches اللي تحت) (2026-10-25). القرارات: [ADR-007](docs/adr/ADR-007-ai-orchestration.md) (Haiku 5.5 بس، Agent واحد + 5 tools ورا `AiGateway`) و[ADR-008](docs/adr/ADR-008-pii-and-llm.md) (PII). الـgolden set اتجرّب live على DeepSeek بس (تجربة، مش الإنتاج)، وHaiku 5.5 لسه متجربش. محليًا `CODEX_AI_PROVIDER=demo`.

| Epic | الحالة |
|---|---|
| E10 AiGateway (باب واحد بـarch tests، metering، سقف $5/يوم، pseudonymization، validator أرقام وروابط) | ✅ |
| E09 الكتالوج والمعرفة (sync كل 10 دقايق + webhooks، بحث hybrid عربي، FAQ/شحن/استرجاع) | ✅ (Voyage embeddings مقفولة لحد الـkey) |
| E11 الـagent (5 tools، handoff حتمي قبل الموديل، retry واحد، Assisted بس، Scoped Autonomous مقفول، رجوع تلقائي عند 2%) | ✅ |
| E12b في الـinbox (المسودات، takeover/hand back، السجل، الأسباب) + شاشات معلومات المتجر وإعدادات الـAI | ✅ |
| Golden set + `ai:eval` | 🟡 **80 حالة** (70 سعودي، 10 مصري)، 13 منهم live-only. الهدف ≥300 محتاج labeling بشري |

الاختبارات وقت الكتابة: **340 test في الـbackend أخضر**، و**26 اختبار E2E** في آخر تشغيل، وE2E للـagent (`web/e2e/ai-agent.spec.ts`). دليل الاختبار: [docs/phases/P3-ai-agent/testing-guide.md](docs/phases/P3-ai-agent/testing-guide.md)، ودليل الحالات: [golden-set-guide.md](docs/phases/P3-ai-agent/golden-set-guide.md).

**الـexit criteria (07 §7.4) لسه مش متقاسة:** 100% دقة سعر على ≥300 حالة (عندنا 80، offline بس)، و≤$0.03 للمحادثة (الحساب ~$0.006 في المتوسط [افتراض]، وأسوأ حالة نظرية ~$0.067)، وhandoff ≤40%، و≥4 أسابيع Assisted (بتبدأ مع أول تاجر بعد Gate 0).

**فجوة معروفة:** الـagent بيخبّي أسماء أعضاء الفريق (اللي بيمضوا ردودهم) ككلمات كاملة. سلة مبتديناش اسم العميل (أرقام بس)، فلو العميل كتب اسمه بنفسه («أنا سارة») **بيوصل للموديل**. قفلها محتاج كشف أسماء (NER) أو اسم العميل من سلة لو اتوفر (ADR-008 §1). لازم تتقفل أو تتقبل قانونيًا قبل أي تاجر حقيقي.

**مراجعة داخلية (2 reviewers):** اتصلّح: الـvalidator بقى بياخد الفلوس من حقول السعر وسياسات التاجر بس (مش من الـIDs والأسماء والروابط)، والروابط من حقل `url` بس، وأي رد فيه خصم بيتحول لموظف قبل أي إعادة، وإعادة الفحص قبل الإرسال التلقائي (الإيقاف، والـopt-out، والـtakeover، ورسالة أحدث)، وصورة بعد سؤال متجاوب مبقتش بتعيد الرد، وحذف بيانات العميل بيمسح الـruns والمسودات، والمسودات ليها retention، وساعة الـ4 أسابيع بتبدأ من جديد بعد الإيقاف، والـdemo provider محلي بس.

**بعد P3 (2026-10-26)، كله مدموج في `main` (PR #10، وREADME في PR #11):**

| الشغل | الحالة |
|---|---|
| الموقع التسويقي (11 صفحة) + «نظرة عامة» جوه التطبيق | ✅ ([الخطة](docs/website/site-plan.md))، الموقع `noindex` لحد الإطلاق |
| ربط WooCommerce + plugin «CODEX Connector» | ✅ للتجربة (السوق المستهدف لسه سلة) ([البروتوكول](docs/integrations/woocommerce-connector.md)) |
| تعدد العملات: كل عملات الوطن العربي + الدولار | ✅ [ADR-012](docs/adr/ADR-012-multi-currency.md). أسعار الصرف تقديرية وبتتحدّث كل شهر، ورسوم Meta لغير السعودية «تقديري» |
| متجر المؤسس rahaf.shop (جنيه، WooCommerce) | 🟡 مربوط محليًا: 507 منتج، والمساعد بيرد بالسعر الصح وبالمصري، والسلات والطلبات بتوصل. واتساب لسه مش مربوط |

الاختبارات: **535 backend**، و**46 E2E**، والـgolden set 85/85 offline و98/99 live على DeepSeek.

**قبل أي رسالة لعميل حقيقي على rahaf.shop:** ربط Meta، ورأي محامي (قانون حماية البيانات المصري 151/2020 + نص الموافقة)، وإيقاف plugin «العربات المهملة» علشان ميبوظش الـholdout، وإلغاء الطلب التجريبي 12933.

**فجوات تقنية معروفة (مراجعة 2026-10-27):**
- Salla وMeta مكتوبين من الـdocs ومتجربوش live (spikes P0b).
- Haiku 5.5 متجربش live، والـgolden set 85 حالة من ≥300.
- اسم العميل لو كتبه بنفسه بيوصل للموديل (القسم «فجوة معروفة» فوق).
- events بتتبعت ومحدش بيسمعها: `OrderCancelled`، `OrderRefunded`، `StoreConnected`، `MessageFailed`. **محتاج نتأكد إن تقرير الربح بيخصم المرتجعات والإلغاء** (تعريف المقياس في [15](docs/blueprint/15-independent-plan-review.md) #1).
- الفوترة metering بس: مفيش باقات ولا تحصيل (ADR-016، P3).
- فولدر `app/Modules/Identity` فاضي، والكود بتاعه في root بتاع `app/`.

## 7. قرارات مستنية المؤسس

**من P3 (الـAI Sales Agent):**
- ✅ **(أ) اتصلّح (قرار المؤسس 2026-10-25):** «إيقاف؟» و«وقف،» بقوا بيتعرفوا كـopt-out. الترقيم العربي بيتشال جوه `ArabicText::normalize` نفسها، فالـopt-out والبحث والـHandoffRules ماشيين على نفس القواعد. فيه test بيفشل على الكود القديم.
- ✅ **(ب) اتقرر (المؤسس فوّض، 2026-10-25):** الـAI بيرد على العميل اللي بدأ المحادثة جوه نافذة الـ24 ساعة، **إلا لو طلب الإيقاف**. الموافقة التسويقية مش شرط للرد، لأنها محادثة خدمة العميل بدأها [افتراض قانوني: سؤال 6 للمحامي في ADR-008]. لو المحامي رفض: `CODEX_AI_REPLY_REQUIRES_CONSENT=true` بيرجّع القاعدة الأشد من غير تغيير كود.
- **(ج) أول تشغيل live اتعمل على DeepSeek (تجربة محلية، 2026-10-25):** 81/81 بعد التصليحات، ودقة السعر والمخزون 26/26، والتكلفة حوالي $0.03. أول تشغيل كان 48/81 بسبب bug في الـrunner (الفهرس مكانش بيتبني في الـtenant المؤقت)، مش بسبب الموديل، واتصلّح. ومفيش رد واحد طلع فيه رقم غلط. **لسه مستني:** keys بتوع Anthropic وVoyage عشان التشغيل الحقيقي على Haiku 5.5، لأن ده موديل الإنتاج.
- **(د) المستشار القانوني:** أسئلة [ADR-008](docs/adr/ADR-008-pii-and-llm.md) §5 (النقل برّه المملكة، ZDR، الـDPA، موافقة العميل على AI، استخدام محادثات حقيقية في الاختبار).
- **(هـ) مراجعة من متحدث سعودي:** نص الـhandoff الليلي (`config/whatsapp_templates.php` → `ai_night_handoff`)، وقوايم كلمات الـhandoff (طلب إنسان، شكوى، خصم) في `app/Modules/SalesAgent/HandoffRules.php`.

**من قبل:**
- **المستشار القانوني:** نص تأكيد التاجر في استيراد موافقات العملاء، وهل الـCSV كفاية كدليل (التفاصيل في [قائمة المشاكل](docs/reviews/2026-10-25-p1-p2-issues.md)).
- بيانات Meta للواجهة: `NEXT_PUBLIC_META_APP_ID` و`NEXT_PUBLIC_META_CONFIG_ID` (Facebook Login for Business) عشان زرار ربط واتساب يشتغل.
- بيانات Salla Partners وMeta (في `.env`) عشان الـspikes الحية.
- ADR-006: منطقة تخزين واتساب (AE ولا BH)، **ومينفعش تتغير بعدين**.
- ADR-005: الاستضافة.
- مراجعة نصوص قوالب الواتساب العربي من متحدث سعودي.
- مواعيد رمضان 1448، ومدد الـretention (بعد الرأي القانوني).

## 8. الصرف
P0: 0 من 25–50 ألف ريال.
