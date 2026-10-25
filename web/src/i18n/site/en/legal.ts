import type { legal as source } from '../ar/legal';
import type { Widen } from '../widen';

export const legal: Widen<typeof source> = {
    draft: {
        title: 'Draft awaiting legal review',
        body: 'This page outlines what the final document will cover; it is not binding text. The full text is published after review by our legal counsel.',
    },
    updated: 'Last updated: not yet published',
    questions: 'Questions about your data or the terms? Contact us.',
    privacy: {
        meta: {
            title: 'Privacy policy',
            description: 'Draft: what the CODEX Commerce privacy policy will cover.',
        },
        title: 'Privacy policy',
        lead: 'How we handle your store’s data and your customers’ data. The sections below are what the final policy will cover.',
        sections: [
            { title: 'Who we are and our role', body: 'The responsible entity, when we process data on the merchant’s behalf, and how to reach us.' },
            { title: 'Data we collect', body: 'Merchant account data, store data from Salla (carts, orders, products), store customers’ phone numbers and consent, and WhatsApp messages.' },
            { title: 'Why we use it', body: 'Cart recovery, measuring incremental profit with a comparison group, replying to customers, billing and security.' },
            { title: 'Where it is processed and stored', body: 'Current hosting and the plan to host in Saudi Arabia, WhatsApp processing at Meta, AI processing with providers outside the Kingdom, and the list of sub-processors.' },
            { title: 'AI and personal data', body: 'What reaches the model and what is masked first, such as phone numbers, emails and known names.' },
            { title: 'Customer consent and opt-out', body: 'How we record a customer’s consent to messages, and how they stop them with an Arabic keyword.' },
            { title: 'Retention and deletion', body: 'How long we keep each kind of data, and how it is deleted.' },
            { title: 'Data subject rights', body: 'Requesting a copy of the data or its deletion, and how a customer or merchant submits it, under the Saudi Personal Data Protection Law (PDPL).' },
            { title: 'Security and incidents', body: 'Encryption, per-store data isolation, the audit log, and notifying the merchant of any incident.' },
            { title: 'Changes to this policy', body: 'How we tell you about any change before it takes effect.' },
        ],
    },
    terms: {
        meta: {
            title: 'Terms of service',
            description: 'Draft: what the CODEX Commerce terms of service will cover.',
        },
        title: 'Terms of service',
        lead: 'The agreement between you and us when you use CODEX Commerce. The sections below are what the final terms will cover.',
        sections: [
            { title: 'The service', body: 'What CODEX Commerce provides, and what is still in beta or coming.' },
            { title: 'Account and permissions', body: 'Your responsibility for your account, your team members and their permissions, and two-factor authentication.' },
            { title: 'Proof Pilot and payment', body: 'The price, the 90 days starting from activation, VAT, the guarantee, refunds and extensions.' },
            { title: 'Meta fees', body: 'WhatsApp fees are paid to Meta directly from the merchant’s card, with no markup from us.' },
            { title: 'Acceptable use', body: 'The official WhatsApp only, no bulk blasts, and compliance with Meta’s policies and customer consent.' },
            { title: 'Reports and estimates', body: 'Incremental profit is an estimate given as a range, not a guaranteed figure.' },
            { title: 'Sales assistant', body: 'What the assistant may do, staff approval of replies, and that it never gives discounts.' },
            { title: 'Stopping and cancellation', body: 'How to stop the service, and what happens to your data after cancellation.' },
            { title: 'Liability', body: 'The limits of our liability, and what depends on third parties such as Salla and Meta.' },
            { title: 'Governing law and contact', body: 'Jurisdiction, and how we communicate about any dispute.' },
        ],
    },
};
