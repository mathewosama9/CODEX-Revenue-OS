import type { home as source } from '../ar/home';
import type { Widen } from '../widen';

export const home: Widen<typeof source> = {
    meta: {
        title: 'WhatsApp cart recovery with measured profit',
        description: 'CODEX Commerce recovers abandoned carts for your Salla store over the official WhatsApp, and measures the incremental profit with a real comparison group.',
    },
    hero: {
        eyebrow: 'For Salla stores, over the official WhatsApp',
        title: 'Know how much profit your WhatsApp messages really made, not just how many orders came after them.',
        lead: 'CODEX Commerce recovers abandoned carts for your Salla store over the official WhatsApp, and measures the incremental profit it brought you, with a real comparison group.',
    },
    report: {
        title: 'Weekly report',
        period: 'Last 7 days',
        incrementalProfit: 'Estimated incremental profit',
        verdict: 'Helpful',
        probability: 'Chance it is positive',
        attributed: 'Attributed revenue',
        incrementalRevenue: 'Incremental revenue',
        metaFees: 'Meta fees',
        discounts: 'Discounts',
        note: 'Sample figures, not from a real store.',
    },
    problem: {
        eyebrow: 'The problem',
        title: 'Not every order after a message came because of it.',
        body: 'Most WhatsApp tools count any order after a message as a success. But some of those customers would have bought anyway, without a message and without a discount.',
    },
    pillars: {
        eyebrow: 'What you get',
        title: 'Four things we commit to',
        items: {
            honestProfit: { title: 'Honest profit', body: 'An estimated incremental profit, shown as a range, that sharpens over time.' },
            lessSpend: { title: 'Spend less', body: 'Send and discount only when it is worth it.' },
            sellInChat: { title: 'Sell in the conversation', body: 'Answers from your live catalog, and never invents a price or a discount.' },
            safe: { title: 'Safe and official', body: 'The official WhatsApp only, with customer consent on record.' },
        },
    },
    steps: {
        eyebrow: 'How it works',
        title: 'Four steps to start',
        items: {
            salla: { title: 'Connect Salla', body: 'Carts and orders reach us as they happen.' },
            whatsapp: { title: 'Connect WhatsApp', body: 'The official WhatsApp, on your own store number.' },
            margin: { title: 'Set your margin', body: 'Profit margin per category, and a floor no discount goes below.' },
            report: { title: 'See your report every week', body: 'Your estimated incremental profit, and what it cost you.' },
        },
        more: 'The full details',
    },
    comparison: {
        eyebrow: 'What sets us apart',
        title: 'The same message, counted differently',
        caption: 'The usual way compared with the CODEX way',
        topic: 'Topic',
        usual: 'The usual way',
        codex: 'CODEX',
        rows: {
            success: { topic: 'What counts as success', usual: 'Any order after a message = success', codex: 'The difference from a comparison group that got no message' },
            discount: { topic: 'Discounts', usual: 'A discount for everyone', codex: 'No discount in the first message, and a margin floor it never goes below' },
            result: { topic: 'The result', usual: 'One number that makes everything look good', codex: 'Incremental profit as a range, with a probability and a clear verdict' },
            cost: { topic: 'Cost', usual: 'Left out of the result', codex: 'Meta fees and discounts taken off the profit' },
        },
    },
    fit: {
        eyebrow: 'Who it is for',
        title: 'Built for certain stores, honestly',
        forTitle: 'A good fit if',
        for: {
            sector: 'You sell perfume, beauty or personal care on Salla.',
            volume: 'You get 300 to 3,000 orders a month.',
            proof: 'You want a profit figure you can defend, not just an order count.',
        },
        notTitle: 'Not a fit if',
        not: {
            small: 'You get fewer than 300 orders a month.',
            bulk: 'You want to send bulk messages to your whole list.',
            unofficial: 'You use an unofficial WhatsApp connection.',
        },
    },
    roadmap: {
        eyebrow: 'Roadmap',
        title: 'What comes next',
        lead: 'These are still being built, and are marked “Coming soon”.',
    },
    // LEGAL REVIEW: the refund line (docs/phases/P0/proof-pilot-offer.md).
    cta: {
        title: 'Proof Pilot: 90 days for SAR 1,500.',
        body: 'If we have not switched the service on within 21 days, we refund you in full.',
    },
};
