import type { FeatureId } from '@/lib/features';

import type { features as source } from '../ar/features';
import type { Widen } from '../widen';

export const features: Widen<typeof source> = {
    meta: {
        title: 'Features',
        description: 'Everything CODEX Commerce does today and what is coming next, with an honest status on every feature.',
    },
    hero: {
        eyebrow: 'Features',
        title: 'Every feature shows its real status, no spin.',
        lead: 'Cart recovery, incremental profit measurement, conversations and compliance. Anything marked Available you can use in the pilot; anything marked Coming soon we are still building.',
    },
    filter: {
        label: 'Show',
        all: 'All',
        live: 'Available now',
        soon: 'Coming soon',
    },
    groups: {
        recovery: 'WhatsApp messages for abandoned carts, with limits that protect your margin.',
        measurement: 'We measure the profit the messages actually brought, not just the orders that came after them.',
        conversations: 'One inbox for your team, and an assistant that drafts replies from your store data.',
        compliance: 'Customer consent and rights, from day one.',
        team: 'Who sees what, and what you have spent, as it happens.',
        platform: 'Install and pay from inside Salla, and other platforms after that.',
    },
    cta: {
        title: 'Want to see this on your store?',
        body: 'The pilot runs 90 days on your real store, and ends with a report that tells you what you actually earned.',
    },
    items: {
        cartRecovery: {
            title: 'Abandoned cart reminders on WhatsApp',
            body: 'Messages from your number on the official WhatsApp platform to shoppers who left their cart, using Meta-approved templates.',
        },
        discountPolicy: {
            title: 'Discount policy with a margin floor + owner approval',
            body: 'You set a margin floor, a discount cap and a monthly budget. A discount never goes below the floor, and anything above the cap only goes out with your approval.',
        },
        quietHours: {
            title: 'Quiet hours and Ramadan',
            body: 'No messages from 22:00 to 09:00, with separate hours in Ramadan. A message is postponed to the next allowed time, not cancelled.',
        },
        holdout: {
            title: '10% comparison group',
            body: '10% of abandoned-cart shoppers get no message, so we know how many orders would have come anyway.',
        },
        profitReport: {
            title: 'Incremental profit report',
            body: 'A range for the incremental profit, the probability the effect is positive, and a clear verdict: helpful, harmful or inconclusive.',
        },
        costLedger: {
            title: 'Cost ledger for every riyal',
            body: 'Every riyal spent on messages, discounts and the assistant is recorded, so the profit is net.',
        },
        margins: {
            title: 'Margin per category',
            body: 'Enter each category margin once, and we calculate profit instead of revenue.',
        },
        inbox: {
            title: 'Conversation inbox and handoff to staff',
            body: 'All WhatsApp conversations in one place, assignable across your team.',
        },
        salesAssistant: {
            title: 'A sales assistant drafts, your staff approves',
            body: 'It drafts replies from your catalog and policies, and nothing reaches the customer until your staff approves it.',
        },
        takeover: {
            title: 'Take over a conversation and hand it back',
            body: 'Your staff take over any conversation in one click, and hand it back to the assistant when done.',
        },
        autoReply: {
            title: 'Automatic replies once quality is proven',
            body: 'For stores that pass 28 days of steady quality in approval mode, and only with your explicit consent.',
        },
        cod: {
            title: 'Cash-on-delivery (COD) order confirmation',
            body: 'A confirmation message before shipping, so fewer orders are refused at the door.',
        },
        consent: {
            title: 'Customer consent and instant Arabic opt-out',
            body: 'We record each customer\'s consent, and if they write "إيقاف" (stop), messages to them stop at once.',
        },
        dataRequests: {
            title: 'Customer data requests (view/delete)',
            body: 'When a customer asks to see or delete their data, you handle it from the dashboard.',
        },
        team: {
            title: 'Owner and staff roles, two-factor sign-in',
            body: 'Everyone on your team sees only what concerns them, and each account can turn on two-step sign-in.',
        },
        usage: {
            title: 'Usage as it happens',
            body: 'See how many messages went out and what you spent this month, with no end-of-month surprises.',
        },
        cartAudit: {
            title: 'Free abandoned cart check (Cart Truth Audit)',
            body: 'We read your abandoned cart data and tell you how much real opportunity is in it, before you send a single message.',
        },
        sallaAppStore: {
            title: 'Install from the Salla App Store',
            body: 'Install CODEX Commerce straight from the Salla App Store and get started on your own.',
        },
        sallaBilling: {
            title: 'Billing inside Salla',
            body: 'Pay your subscription from your Salla account, with no separate invoice.',
        },
        saudiHosting: {
            // LEGAL REVIEW: data location wording.
            title: 'Core data hosted in Saudi Arabia',
            body: 'We move core data to the Dammam region. WhatsApp messaging and AI processing stay with their providers.',
        },
        zid: {
            title: 'Zid',
            body: 'The same recovery and measurement for Zid stores, once our customers ask for it.',
        },
        winBack: {
            title: 'Win-back',
            body: 'Messages to customers who bought before and did not return, measured with the same comparison group.',
        },
        crmLite: {
            title: 'Light CRM',
            body: 'A simple profile per customer: orders, conversations and consent in one place.',
        },
        channels: {
            title: 'Instagram, Messenger and email',
            body: 'The same inbox and assistant on channels beyond WhatsApp.',
        },
        gulf: {
            title: 'UAE and the Gulf',
            body: 'Support for stores in the UAE and the rest of the Gulf, with their currencies and rules.',
        },
    } satisfies Record<FeatureId, { title: string; body: string }> as Record<FeatureId, { title: string; body: string }>,
};
