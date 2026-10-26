// Owner: see docs/website/site-plan.md. Saudi-leaning marketing Arabic; a Saudi speaker reviews it before publishing.
// No form with a backend yet (Rule 12): WhatsApp and email only, from NEXT_PUBLIC_CONTACT_*.
export const contact = {
    meta: {
        title: 'تواصل معنا',
        description: 'اطلب Proof Pilot أو اسألنا أي سؤال على واتساب أو الإيميل.',
    },
    hero: {
        eyebrow: 'تواصل معنا',
        title: 'خلنا نشوف إذا متجرك مناسب.',
        lead: 'كلّمنا على واتساب أو أرسل إيميل. نرد عليك بنفسنا، مو بوت.',
    },
    channels: {
        title: 'طرق التواصل',
        whatsapp: 'راسلنا على واتساب',
        email: 'أرسل لنا إيميل',
        whatsappPending: 'رقم واتساب للتواصل يُضاف قريبًا.',
        emailPending: 'إيميل التواصل يُضاف قريبًا.',
        replyTime: 'نحاول نرد خلال يوم عمل واحد، وأحيانًا أسرع.',
    },
    needs: {
        title: 'وش نحتاج نعرف',
        lead: 'عشان نرد عليك بشي مفيد من أول رسالة، أرسل لنا:',
        items: [
            'اسم المتجر',
            'عدد الطلبات تقريبًا في الشهر',
            'القطاع (عطور، تجميل، عناية شخصية، أو غيره)',
            'رابط متجرك على سلة',
        ],
    },
    message: 'السلام عليكم، أبغى أعرف أكثر عن الـProof Pilot.',
    emailSubject: 'طلب Proof Pilot',
};
