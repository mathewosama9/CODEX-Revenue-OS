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
**Sprint S0 (Setup)** على branch `feat/S0-setup`، وخلص من غير commit:
- ✅ Laravel 13 + Inertia 3 + React 19 + Teams، وPostgreSQL 17 + pgvector (Docker).
- ✅ طبقة العزل بين التجار (`app/Shared/Tenancy`) + isolation suite + arch tests لحدود الـModules.
- ✅ ثيم البراند (Navy/Light)، وخطوط Plex Arabic وRubik، وRTL افتراضي، وكل الواجهة logical properties مع فحص في الـCI.
- ✅ مكوّنات البراند: زرار Revenue، وKpiCard، وDeltaBadge، وRangeEstimate.
- ✅ CI على PostgreSQL. `composer ci:check` أخضر (118 test).
- ⏭️ S1: أدوار Owner/Agent، وSalla Adapter، و`audit_events`، وschema الـconsent.

## 7. قرارات مستنية المؤسس
- اعتماد الـBlueprint رسميًا: المؤسس قال "ابدأ" يوم 2026-10-25، ولسه محتاج توثيق في Gate issue.
- Commit وpush لشغل S0.

## 8. الصرف
P0: 0 من 25–50 ألف ريال.
