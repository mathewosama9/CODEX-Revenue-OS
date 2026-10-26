import type { pilot as source } from '../ar/pilot';
import type { Widen } from '../widen';

export const pilot: Widen<typeof source> = {
    meta: {
        title: 'Proof Pilot',
        description: '90 days from activation for SAR 1,500: cart recovery on the official WhatsApp, a comparison group, and a weekly incremental-profit report.',
    },
    hero: {
        eyebrow: 'Proof Pilot',
        title: 'Try it for 90 days and see what you actually earned.',
        lead: 'We run abandoned-cart recovery on the official WhatsApp with you, measure the incremental profit against a real comparison group, and send you a report every week.',
    },
    price: {
        label: 'Pilot price',
        amount: 'SAR 1,500',
        period: '90 days from activation',
        upfront: 'Paid once, upfront.',
        vat: 'Prices exclude VAT.',
        cta: 'Request a pilot',
        ctaNote: 'We talk first and check your store is a fit, before any payment.',
    },
    included: {
        title: 'What is included',
        items: [
            'Cart recovery messages from your own store number on the official WhatsApp.',
            'A 10% comparison group of your eligible customers, so we know the real effect.',
            'A weekly report with four figures: attributed revenue, incremental revenue (a range), costs, and incremental profit (a range).',
            'Setup with you, step by step: Salla, the WhatsApp account, templates and margin categories.',
        ],
    },
    meta_fees: {
        title: 'Meta fees',
        body: 'WhatsApp message fees are charged by Meta directly to your card, with no markup from us. Before we start, we give you an honest estimate of the monthly cost based on your cart volume.',
    },
    honesty: {
        title: 'To be clear',
        body: 'The result is a range, not an exact figure. It can come out "inconclusive" in the first 90 days if cart volume is low, and we will tell you so plainly.',
    },
    // LEGAL REVIEW: guarantee, refund and VAT wording; no payment is taken before the lawyer approves billing (proof-pilot-offer.md).
    guarantee: {
        title: 'Our guarantee',
        items: [
            'If we have not activated the service within 21 days of payment, for any reason on our side, we refund you in full.',
            'If a weekly report is late, or sending stops for more than 3 days because of a fault on our side, we extend the pilot by the same period.',
        ],
    },
    conditions: {
        title: 'Who we accept',
        lead: 'We take a limited number of stores at this stage, and we want the result to be useful to you:',
        items: [
            'A perfume, beauty or personal-care store on Salla.',
            '400 or more abandoned carts a month with a phone number.',
            'Agreement to the 10% comparison group.',
            '30 minutes a week to review the report with us.',
        ],
    },
    after: {
        title: 'After 90 days',
        body: 'Continue on a monthly plan that fits your store, or stop with no commitment. Plan prices: contact us.',
        cta: 'Contact us',
    },
    cta: {
        title: 'Is your store a fit? Let us check together.',
        body: 'Send us your store name and monthly orders, and we will reply with the next steps.',
        button: 'Request a pilot',
    },
};
