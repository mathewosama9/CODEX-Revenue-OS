# STATUS — CODEX Revenue OS

> بيتحدّث كل خميس ([12](docs/blueprint/12-first-90-days.md) §12.19). آخر تحديث: 2026-10-25.

## 1. المرحلة والـSprint
**P0a — أسبوع 1 (D1)**، من 2026-10-25 لـ2026-10-29. الشغل كله في [W1-checklist.md](docs/phases/P0/W1-checklist.md).

## 2. Gate 0 (هدفه 2026-12-10)
0 / 10 معايير اتحققت.

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
**Sprint S0 (Setup)** — ✅ خلص، على branch `feat/S0-setup` (اتعمله push):
- Laravel 13 + Inertia 3 + React 19 + Teams، وPostgreSQL 17 + pgvector (Docker).
- طبقة العزل بين التجار (`app/Shared/Tenancy`) + isolation suite + arch tests لحدود الـModules.
- ثيم البراند (Navy/Light)، وخطوط Plex Arabic وRubik، وRTL افتراضي، وفحص RTL في الـCI.
- مكوّنات البراند: زرار Revenue، وKpiCard، وDeltaBadge، وRangeEstimate.

**Sprint S1 (Foundation)** — شغال على branch `feat/S1-foundation`:
- ✅ الأدوار Owner وAgent بس، بمصفوفة الصلاحيات (09 §9.13) (E01).
- ✅ `audit_events` append-only ومحمي من الداتابيز نفسها (trigger)، ومن غير PII (E15).
- ✅ Consent ledger append-only + `ConsentGate` + كشف كلمات الإيقاف بالعربي + quiet hours بما فيها رمضان (E04).
- ✅ استقبال webhooks سلة: توقيع HMAC، وكل event بيتسجل مرة واحدة، والمحتوى متشفّر (E02).
- `composer ci:check` أخضر (184 test).
- ⏳ **مستني بيانات سلة** عشان باقي E02: OAuth (Easy Mode)، وrefresh الـtoken بـlock، والـreconciliation polling.

## 7. قرارات مستنية المؤسس
- اعتماد الـBlueprint رسميًا: المؤسس قال "ابدأ" يوم 2026-10-25، ولسه محتاج توثيق في Gate issue.
- بيانات Salla Partners: الـClient ID والـSecret والـWebhook secret للـdemo store.
- ملفات اللوجو (SVG) من البراند.
- مواعيد رمضان 1448 في `config/codex.php` تقديرية، ومحتاجة تأكيد من تقويم أم القرى.

## 8. الصرف
P0: 0 من 25–50 ألف ريال.
