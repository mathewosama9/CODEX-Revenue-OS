import type { aiAgent as source } from '../ar/aiAgent';
import type { Widen } from '../widen';

export const aiAgent: Widen<typeof source> = {
    meta: {
        title: 'Sales assistant',
        description: 'An assistant that drafts WhatsApp replies from your store catalog and policies; your staff approve before any reply reaches a customer.',
    },
    hero: {
        eyebrow: 'The sales assistant inside your inbox',
        title: 'It answers your customers from your catalog, and your staff approve.',
        lead: 'Part of the conversation inbox: it drafts replies from your live store data, and nothing goes out until your staff approve it.',
    },
    can: {
        title: 'What it can do',
        items: [
            'Answer price, stock and size questions from your live store data.',
            'Answer shipping and returns questions from your policy texts.',
            'Send a product link or a link to the customer’s abandoned cart.',
            'Hand the conversation to your staff when needed.',
        ],
    },
    cannot: {
        title: 'What it does not do, on purpose',
        lead: 'These are fixed limits in the product, not settings.',
        items: [
            'It never gives a discount or a coupon.',
            'It never creates a payment link or asks for card details.',
            'It never changes or cancels an order.',
            'It never answers anything outside your store.',
        ],
    },
    numbers: {
        title: 'How we stop it making up numbers',
        body: 'Every number and link in a reply is checked before your staff see it, and it must come from your store data. If a number or link does not, the assistant gets one retry; if that fails too, the conversation goes to a staff member.',
    },
    handoff: {
        title: 'When it hands off to staff',
        items: [
            'The customer asks for a human.',
            'The customer complains.',
            'The customer asks for a discount.',
            'The cart value is high.',
        ],
    },
    // LEGAL REVIEW: PII and the model (ADR-008 §1, §5). A name the customer types in the chat still reaches the model.
    privacy: {
        title: 'Your customers\' privacy',
        body: 'Phone numbers, emails and your team’s names are replaced with placeholders before they reach the model. A name a customer types in their own message can still reach the model today, and we are working on masking it.',
    },
    assisted: {
        title: 'Approval mode first',
        body: 'Today every reply is a draft, and your staff approve, edit or reject it.',
        autoTitle: 'Automatic replies',
        autoBody: 'Locked until 28 consecutive days in approval mode with steady quality, and then only with your explicit consent.',
    },
    cap: {
        title: 'A daily spend cap',
        body: 'Every store has a daily cap on assistant cost, and you can lower it. When it is reached, the assistant pauses until midnight Riyadh time and your team keeps replying.',
    },
    chat: {
        customer: 'Customer',
        assistant: 'Assistant, approved by staff',
        system: 'System note',
        messages: {
            ask: 'Hi, how much is the Cambodian oud oil? Is it in stock?',
            reply: 'Hi! The Cambodian oud oil, 12 ml, is {price}, and there are {stock} left. Here is the link:',
            link: 'yourstore.com/oud-cambodi',
            discount: 'Is there a discount if I take two?',
            handoff: 'Conversation transferred to a staff member: discount request.',
        },
    },
    cta: {
        title: 'Try the assistant in the pilot',
        body: 'The assistant is part of the pilot in approval mode, on your real catalog.',
    },
};
