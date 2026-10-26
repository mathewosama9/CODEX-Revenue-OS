import type { howItWorks as source } from '../ar/howItWorks';
import type { Widen } from '../widen';

export const howItWorks: Widen<typeof source> = {
    meta: {
        title: 'How it works',
        description: 'From connecting Salla and the official WhatsApp to the comparison group and the weekly report: how we measure incremental profit honestly.',
    },
    hero: {
        eyebrow: 'How it works',
        title: 'From an abandoned cart to a profit figure you can trust',
        lead: 'We message the customers worth messaging, keep a small share without a message, and compare. That is how we know what the messages really caused.',
    },
    connect: {
        eyebrow: 'Step 1',
        title: 'Connect',
        salla: { title: 'Salla', body: 'Abandoned carts and orders reach us from your store as they happen.' },
        whatsapp: { title: 'Official WhatsApp', body: 'Messages go out from your own store number, through the official WhatsApp platform from Meta.' },
    },
    eligibility: {
        eyebrow: 'Step 2',
        title: 'Who gets a message',
        lead: 'Not every cart gets one. It must:',
        items: {
            phone: 'Have a mobile number.',
            // LEGAL REVIEW: what counts as valid consent (STATUS.md §7).
            consent: 'Come from a customer who agreed to receive messages.',
            quiet: 'Fall outside quiet hours: 22:00 to 09:00, with special hours in Ramadan.',
        },
    },
    holdout: {
        eyebrow: 'Step 3',
        title: 'The comparison group',
        body: '10% of customers get no message, chosen once and kept fixed per customer. We compare their purchases with everyone else, so we know the difference the messages made.',
        diagramLabel: 'Out of every 100 customers: 90 receive messages, 10 are a comparison group with no message',
        treated: '90 customers receive messages',
        control: '10 customers in the comparison group, no message',
    },
    discount: {
        eyebrow: 'Step 4',
        title: 'Discounts with a calculation',
        body: 'The first message has no discount. Any discount after it never goes below the margin floor you set or beyond your monthly budget, and anything above your cap needs your approval.',
    },
    report: {
        eyebrow: 'Step 5',
        title: 'The weekly report',
        lead: 'Every week you get four figures:',
        items: {
            attributed: 'Attributed revenue: orders that came after the messages.',
            incremental: 'Incremental revenue: the difference from the comparison group, as a range.',
            cost: 'Cost: Meta fees and discounts.',
            profit: 'Incremental profit: the figure that matters, after cost and margin.',
        },
        verdictsTitle: 'Then the verdict:',
        verdicts: {
            helpful: 'Helpful',
            harmful: 'Harmful',
            inconclusive: 'Inconclusive',
        },
    },
    honesty: {
        eyebrow: 'Honestly',
        title: 'We tell you the result as it is',
        body: 'The result is a range, not an exact figure, and it can come out “inconclusive” in the first 90 days if carts are few. We will tell you so, clearly.',
    },
    cta: {
        title: 'Try it on your store with a Proof Pilot',
        body: '90 days, a comparison group from day one, and a report every week.',
    },
};
