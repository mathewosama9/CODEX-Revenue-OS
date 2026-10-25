import type { faq as source } from '../ar/faq';
import type { Widen } from '../widen';

export const faq: Widen<typeof source> = {
    meta: {
        title: 'FAQ',
        description: 'Straight answers about the comparison group, report accuracy, WhatsApp fees, your data and the pilot.',
    },
    hero: {
        eyebrow: 'FAQ',
        title: 'Questions we hear a lot, answered plainly.',
        lead: 'Did not find yours? Contact us and we will reply.',
    },
    items: [
        {
            q: 'How is this different from Salla’s abandoned-cart reminder?',
            a: 'Most reminder tools message carts and count any order that follows as a success. We send on the official WhatsApp only to carts worth it, never discount the first message, and measure incremental profit with a comparison group: we separate orders the message caused from orders you would have had anyway.',
        },
        {
            q: 'Why do 10% of my customers get no message? Won’t I lose out?',
            a: 'That is the comparison group. Without it there is no honest way to know what the messages actually added. Each customer is assigned once, consistently, and we compare purchases between the two groups. It is only 10% of eligible customers, and in return you learn in riyals whether messages and discounts are worth what you spend on them.',
        },
        {
            q: 'How accurate is the report? What does "inconclusive" mean?',
            a: 'The report gives a range for incremental profit (from X to Y) and the probability that the effect is positive, not a single number. "Inconclusive" means there is not yet enough data to say with confidence whether the effect is helpful or harmful. That is normal for stores with fewer carts and can happen in the first 90 days. We also tell you roughly how many more customers are needed for a conclusive result.',
        },
        {
            q: 'Who pays the WhatsApp fees?',
            a: 'Meta charges message fees directly to your card, with no markup from us. We give you an estimate of the monthly cost before you start, and it appears in the report under costs.',
        },
        {
            q: 'Do I have to change my store’s WhatsApp number?',
            a: 'We send from your store’s number on the official WhatsApp. Keeping the same number on the WhatsApp Business app too (Coexistence) is something we are still confirming for Saudi Arabia; if it is not available, we set up a dedicated number for the store with you. We decide this together during setup.',
        },
        {
            q: 'Does the sales assistant give discounts?',
            a: 'No, never. The assistant answers from your catalog and store information only, and any discount request goes to your staff. Recovery discounts are bound by a margin floor you set, and anything above your cap needs your approval. The assistant is in beta: every reply is a draft your staff approves before it reaches the customer.',
        },
        // LEGAL REVIEW: data location and PII (ADR-008 §5).
        {
            q: 'Where is my data stored?',
            a: 'Your store’s data is isolated from other stores, and sensitive data is encrypted. To be honest: WhatsApp messages pass through Meta, and AI processing happens with providers outside the Kingdom; phone numbers and emails are masked before they reach the model. Hosting the core data in Saudi Arabia is planned, and we will announce it when it is done.',
        },
        // LEGAL REVIEW: opt-out and re-consent.
        {
            q: 'What happens if a customer asks to stop messages?',
            a: 'If the customer writes "إيقاف" (stop) or a similar Arabic word, we stop marketing messages to them immediately and record it. They only come back if they opt in again themselves.',
        },
        {
            q: 'My store has fewer than 300 orders a month. Is it a fit?',
            a: 'Probably not, for now. At that size there is not enough data for an honest measurement, and the price would not make sense for you. We would rather tell you now than have you pay for a result that cannot come out.',
        },
        {
            q: 'Do you support Zid?',
            a: 'Coming soon, not yet. We start with Salla; Zid is on the roadmap.',
        },
        // LEGAL REVIEW: the 21-day refund (this and the next answer).
        {
            q: 'How long does activation take?',
            a: 'We aim for a few days once your accounts are ready (Salla, WhatsApp and a card on Meta). Our hard limit is 21 days from payment: if we have not activated by then because of us, we refund you in full.',
        },
        {
            q: 'Can I cancel?',
            a: 'You can stop sending at any time from the dashboard with one click. The pilot is paid upfront for 90 days; after that you continue on a monthly plan or stop with no commitment. Detailed refund terms will be in the contract.',
        },
    ],
    cta: {
        title: 'Another question?',
        body: 'Reach us on WhatsApp or by email.',
    },
};
