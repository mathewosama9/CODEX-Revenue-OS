import type { security as source } from '../ar/security';
import type { Widen } from '../widen';

// LEGAL REVIEW: same as the Arabic file; `providers.body` needs the lawyer's wording before publishing.
export const security: Widen<typeof source> = {
    meta: {
        title: 'Security and privacy',
        description: 'What CODEX Commerce protects today, what is still planned, and where your data is processed, honestly.',
    },
    hero: {
        eyebrow: 'Security and privacy',
        title: 'We protect your store and customer data, and tell you plainly where it goes.',
        lead: 'We separate what runs in the product today from what is still planned, so you know what you can rely on.',
    },
    available: {
        title: 'Available today',
        items: [
            { title: 'Official WhatsApp only', body: 'We send through Meta\'s official WhatsApp Business Platform, with no unofficial numbers or tools.' },
            { title: 'Each store\'s data isolated', body: 'Every store sees only its own data, and isolation is tested automatically on every update.' },
            { title: 'Sensitive data encrypted', body: 'Salla tokens, phone numbers and messages are encrypted.' },
            { title: 'Tamper-proof audit log', body: 'Every important change is kept in a log that cannot be edited or deleted.' },
            { title: 'Personal data masked', body: 'Phone numbers and emails are masked in logs, and replaced with placeholders before reaching the AI model.' },
            { title: 'Retention and deletion periods', body: 'Each kind of data has a retention period, after which it is deleted. The final periods are set after legal review.' },
            { title: 'Customer data requests', body: 'Handle any customer\'s request to see or delete their data.' },
            { title: 'Two-factor sign-in', body: 'Every account on your team can turn on two-step sign-in to the dashboard.' },
            { title: 'Instant message stop', body: 'If a customer writes "إيقاف" (stop), messages to them stop, and you can pause all recovery messages in one click.' },
        ],
    },
    planned: {
        title: 'Planned',
        items: [
            { title: 'Core data hosted in Saudi Arabia', body: 'We move core data to the Dammam region.' },
            { title: 'Data processing agreement (DPA) in Arabic', body: 'A written agreement setting out our responsibilities for your customers\' data.' },
            { title: 'Sub-processor list', body: 'A published list of every provider that processes data on our behalf.' },
            { title: 'Penetration test', body: 'A test by an independent party before public launch.' },
        ],
    },
    providers: {
        title: 'To be clear',
        body: 'WhatsApp messaging and AI processing happen at providers outside the Kingdom, and hosting core data in Saudi Arabia is still planned.',
    },
    cta: {
        title: 'Questions about your data?',
        body: 'Contact us and we will answer in detail before you start.',
    },
};
