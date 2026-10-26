import type { common as source } from '../ar/common';
import type { Widen } from '../widen';

export const common: Widen<typeof source> = {
    brandSuffix: 'CODEX Commerce',
    pilotBanner: 'We are in the pilot phase, open to a limited number of stores.',
    nav: {
        home: 'Home',
        howItWorks: 'How it works',
        features: 'Features',
        aiAgent: 'Sales assistant',
        pilot: 'Pilot',
        security: 'Security',
        roadmap: 'Roadmap',
        faq: 'FAQ',
        contact: 'Contact',
        privacy: 'Privacy',
        terms: 'Terms',
        menu: 'Menu',
    },
    cta: {
        requestPilot: 'Request a Proof Pilot',
        seeHow: 'See how it works',
        signIn: 'Sign in',
        contact: 'Contact us',
        roadmap: 'See the roadmap',
    },
    status: {
        live: 'Available',
        beta: 'Beta',
        soon: 'Coming soon',
    },
    group: {
        recovery: 'Recovery',
        measurement: 'Measurement',
        conversations: 'Conversations',
        compliance: 'Compliance',
        team: 'Team',
        platform: 'Platform',
    },
    horizon: {
        now: 'Now: the pilot',
        next: 'Next',
        launch: 'Public launch',
        later: 'Later, as customers ask',
    },
    example: 'Illustrative example',
    footer: {
        tagline: 'Measured profit.',
        company: 'CODEX AI Systems',
        trademark: 'WhatsApp is a trademark of Meta. Salla is a trademark of its owners.',
        product: 'Product',
        companyLinks: 'Company',
        legal: 'Legal',
    },
};
