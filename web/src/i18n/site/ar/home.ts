// Home page (docs/website/site-plan.md §3.1). Saudi-leaning marketing Arabic; a Saudi speaker reviews it before publishing.
// No customer numbers or testimonials: the report is always marked as an illustrative example.
export const home = {
    meta: {
        title: 'استرجاع السلات على واتساب بربح محسوب',
        description: 'CODEX Commerce يسترجع السلات المتروكة لمتجرك على سلة عبر واتساب الرسمي، ويقيس الربح الإضافي بمجموعة مقارنة حقيقية.',
    },
    hero: {
        eyebrow: 'لمتاجر سلة، عبر واتساب الرسمي',
        title: 'اعرف كم ربحت فعلًا من رسائل واتساب، مو بس كم طلب جاء بعدها.',
        lead: 'CODEX Commerce يسترجع السلات المتروكة لمتجرك على سلة عبر واتساب الرسمي، ويقيس الربح الإضافي اللي جاك بسببه، بمجموعة مقارنة حقيقية.',
    },
    // The sample weekly report beside the hero, and on how-it-works. One gold figure: the incremental profit.
    report: {
        title: 'تقرير الأسبوع',
        period: 'آخر 7 أيام',
        incrementalProfit: 'الربح الإضافي التقديري',
        verdict: 'مفيد',
        probability: 'احتمال إنه إيجابي',
        attributed: 'الإيراد المنسوب',
        incrementalRevenue: 'الإيراد الإضافي',
        metaFees: 'رسوم Meta',
        discounts: 'الخصومات',
        note: 'أرقام مثال، مو من متجر حقيقي.',
    },
    problem: {
        eyebrow: 'المشكلة',
        title: 'مو كل طلب بعد الرسالة جاء بسببها.',
        body: 'أغلب أدوات واتساب تحسب أي طلب يجي بعد الرسالة على إنه نجاح. لكن جزء من هالعملاء كان بيشتري بدون رسالة، وبدون خصم.',
    },
    pillars: {
        eyebrow: 'وش تاخذ',
        title: 'أربع أشياء نلتزم فيها',
        items: {
            honestProfit: { title: 'ربح صادق', body: 'رقم ربح إضافي تقديري، في صورة نطاق، يتحسّن مع الوقت.' },
            lessSpend: { title: 'صرف أقل', body: 'ما ترسل ولا تخصم إلا لما تستاهل.' },
            sellInChat: { title: 'بيع داخل المحادثة', body: 'يرد من كتالوجك الحي، وما يخترع سعر ولا خصم.' },
            safe: { title: 'آمن ورسمي', body: 'واتساب الرسمي فقط، وموافقات العملاء محفوظة.' },
        },
    },
    steps: {
        eyebrow: 'كيف يشتغل',
        title: 'أربع خطوات وتبدأ',
        items: {
            salla: { title: 'اربط سلة', body: 'السلات والطلبات توصلنا أولًا بأول.' },
            whatsapp: { title: 'اربط واتساب', body: 'واتساب الرسمي، برقم متجرك نفسه.' },
            margin: { title: 'حدد هامشك', body: 'هامش الربح لكل فئة، وأرضية ما ينزل تحتها أي خصم.' },
            report: { title: 'شوف تقريرك كل أسبوع', body: 'كم ربح إضافي تقديري جاك، وكم كلّفك.' },
        },
        more: 'التفاصيل كاملة',
    },
    comparison: {
        eyebrow: 'وش يميّزنا',
        title: 'نفس الرسالة، بس بحساب مختلف',
        caption: 'مقارنة بين الطريقة المعتادة وطريقة CODEX',
        topic: 'الموضوع',
        usual: 'الطريقة المعتادة',
        codex: 'CODEX',
        rows: {
            success: { topic: 'وش يُحسب نجاح', usual: 'أي طلب بعد رسالة = نجاح', codex: 'الفرق عن مجموعة مقارنة ما وصلتها رسالة' },
            discount: { topic: 'الخصم', usual: 'خصم للكل', codex: 'أول رسالة بدون خصم، وأرضية هامش ما ينزل تحتها' },
            result: { topic: 'النتيجة', usual: 'رقم واحد يبيّن كل شي ناجح', codex: 'ربح إضافي بنطاق، واحتمال، وحكم واضح' },
            cost: { topic: 'التكلفة', usual: 'ما تنحسب مع النتيجة', codex: 'رسوم Meta والخصومات مخصومة من الربح' },
        },
    },
    fit: {
        eyebrow: 'لمين',
        title: 'مصمم لمتاجر معيّنة، وبصراحة',
        forTitle: 'يناسبك لو',
        for: {
            sector: 'متجرك عطور أو تجميل أو عناية شخصية على سلة.',
            volume: 'عندك من 300 إلى 3,000 طلب في الشهر.',
            proof: 'تبي رقم ربح تقدر تدافع عنه، مو بس عدد طلبات.',
        },
        notTitle: 'ما يناسبك لو',
        not: {
            small: 'طلباتك أقل من 300 في الشهر.',
            bulk: 'تبي ترسل رسائل جماعية لكل القائمة.',
            unofficial: 'تستخدم ربط واتساب غير رسمي.',
        },
    },
    roadmap: {
        eyebrow: 'خارطة الطريق',
        title: 'وش جاي بعدين',
        lead: 'هذي أشياء لسه نبنيها، وعليها شارة «قريبًا».',
    },
    // LEGAL REVIEW: the refund line (docs/phases/P0/proof-pilot-offer.md).
    cta: {
        title: 'Proof Pilot: ‏90 يوم بـ1,500 ر.س.',
        body: 'لو ما فعّلنا الخدمة خلال 21 يوم، نرجّع لك المبلغ كامل.',
    },
};
