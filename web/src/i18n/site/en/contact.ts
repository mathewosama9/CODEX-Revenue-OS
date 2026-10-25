import type { contact as source } from '../ar/contact';
import type { Widen } from '../widen';

export const contact: Widen<typeof source> = {
    meta: {
        title: 'Contact',
        description: 'Request a Proof Pilot or ask us anything on WhatsApp or by email.',
    },
    hero: {
        eyebrow: 'Contact',
        title: 'Let us see whether your store is a fit.',
        lead: 'Message us on WhatsApp or send an email. A person replies, not a bot.',
    },
    channels: {
        title: 'How to reach us',
        whatsapp: 'Message us on WhatsApp',
        email: 'Send us an email',
        whatsappPending: 'Our WhatsApp contact number is coming soon.',
        emailPending: 'Our contact email is coming soon.',
        replyTime: 'We try to reply within one business day, often sooner.',
    },
    needs: {
        title: 'What we need to know',
        lead: 'So our first reply is useful, send us:',
        items: [
            'Your store name',
            'Roughly how many orders a month',
            'Your sector (perfume, beauty, personal care, or other)',
            'A link to your Salla store',
        ],
    },
    message: 'Hello, I would like to know more about the Proof Pilot.',
    emailSubject: 'Proof Pilot request',
};
