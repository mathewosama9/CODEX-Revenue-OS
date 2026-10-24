# P0 — أسبوع 1 (D1): الأحد 2026-10-25 → الخميس 2026-10-29

> الحالة: شغال. المرجع: [12-first-90-days.md](../../blueprint/12-first-90-days.md) §12.2 و§12.6.
> **Rule 1: صفر كود الأسبوع ده.**
> المطلوب في آخر الأسبوع: قايمة الـ60 متجر، وسكريبت المقابلة، وحالة الطلبات الأربعة مكتوبة في [STATUS.md](../../../STATUS.md).

## 1. Meta Business Verification (أطول مسار، ابدأ بيه)

- [ ] افتح أو جهّز **Meta Business Portfolio** باسم الشركة المصرية، على business.facebook.com.
- [ ] جهّز المستندات: السجل التجاري، والبطاقة الضريبية، وعنوان ورقم تليفون مطابقين للسجل بالظبط، ودومين `codexai.systems` بإيميل عليه.
- [ ] تحقق من الدومين (Domain verification) في الـBusiness settings.
- [ ] قدّم Business Verification.
- [ ] اعمل Meta App من نوع Business على developers.facebook.com وضيف منتج WhatsApp. **متقدّمش App Review لسه** (بييجي في W4 بعد الـspike).
- [ ] اسأل Meta Support بالكتابة: «لو سجّلنا شركة سعودية بعدين، ينفع ننقل الـBV والـTech Provider status ليها؟». القرار ده صعب يتغيّر بعدين (D2 في [08](../../blueprint/08-phase-dependencies.md)).

> المدة: من 1 لـ14 يوم عمل [غير مؤكدة]. خطط على 2–4 أسابيع للمسار كله مع App Review.

## 2. Salla Partners

- [ ] سجّل في partners.salla.com.
- [ ] اعمل **demo store** (الـOTP الافتراضي `1111` [مؤكدة]).
- [ ] اعمل **Private app**: إعدادات بس، من غير كود.
- [ ] ابعت الأسئلة المكتوبة اللي في [questions-salla-lawyer.md](questions-salla-lawyer.md) §1 لفريق الشركاء. تقدر تبعتها في W2، بس كل ما بدري أحسن.

## 3. GCP Dammam

- [ ] اطلب **Invoiced Billing** للشركة المصرية، لأن الدفع بالكارت مش متاح في Dammam [مؤكدة]. اللينك: https://docs.cloud.google.com/docs/dammam-region-access
- [ ] اسأل في الطلب: هل pgvector على Cloud SQL، وMemorystore، وSecret Manager متاحين في `me-central2`؟ وقد إيه مدة الموافقة؟
- [ ] لو اتأخر: **Frankfurt مؤقت** بتصميم portable (ADR-005). **ممنوع AWS UAE/Bahrain.**

## 4. المستشار القانوني

- [ ] رشّح 2–3 مستشارين سعوديين خبرتهم PDPL والتجارة الإلكترونية والضرايب.
- [ ] ابعتلهم الأسئلة اللي في [questions-salla-lawyer.md](questions-salla-lawyer.md) §2 واطلب عرض سعر. الميزانية 15–30 ألف ريال [افتراض].
- [ ] التعاقد مع واحد منهم في W2.

## 5. Discovery

- [ ] قايمة **60 متجر** في الـICP على [stores-list-template.md](stores-list-template.md).
- [ ] راجع [interview-script.md](interview-script.md) وجرّبه مرة مع حد تعرفه.
- [ ] ابعت **أول 10 طلبات مقابلة** بالرسالة اللي في السكريبت (القسم 0).

## مراجعة الخميس (30 دقيقة)

حدّث [STATUS.md](../../../STATUS.md) بالإجابات دي:
- إيه اللي اتقدّم من الطلبات الأربعة (Meta، سلة، GCP، المستشار)؟
- كام متجر في القايمة؟ كام طلب مقابلة اتبعت؟ كام رد؟
- إيه اللي وقّفنا؟
