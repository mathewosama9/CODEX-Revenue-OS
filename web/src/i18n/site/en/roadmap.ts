import type { roadmap as source } from '../ar/roadmap';
import type { Widen } from '../widen';

export const roadmap: Widen<typeof source> = {
    meta: {
        title: 'Roadmap',
        description: 'What CODEX Commerce offers today and what comes next, without dates we could not commit to.',
    },
    hero: {
        eyebrow: 'Roadmap',
        title: 'What we have today, and what comes next.',
        lead: 'Stages without dates: each one starts when the one before it has proven itself with real stores.',
    },
    horizons: {
        now: 'What you use in the pilot today.',
        next: 'The first things we build after the pilot.',
        launch: 'When we open to all stores.',
        later: 'Built when our customers ask for it.',
    },
    closing: 'The roadmap changes with what our customers ask for. We build what you pay for, not what dazzles.',
    cta: {
        title: 'Want a say in the roadmap?',
        body: 'Pilot stores decide what we build next.',
    },
};
