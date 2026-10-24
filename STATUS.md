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
| **الواجهة (Next.js)** | ⏳ بعد مراجعة P1 ([ADR-002](docs/adr/ADR-002-frontend-nextjs.md)) |

دليل الاختبار: [docs/phases/P1/testing-guide.md](docs/phases/P1/testing-guide.md).

## 7. قرارات مستنية المؤسس
- مراجعة P1 ودمج الـbranches في `main`.
- بيانات Salla Partners وMeta (في `.env`) عشان الـspikes الحية.
- ADR-006: منطقة تخزين واتساب (AE ولا BH)، **ومينفعش تتغير بعدين**.
- ADR-005: الاستضافة.
- مراجعة نصوص قوالب الواتساب العربي من متحدث سعودي.
- مواعيد رمضان 1448، ومدد الـretention (بعد الرأي القانوني).

## 8. الصرف
P0: 0 من 25–50 ألف ريال.
