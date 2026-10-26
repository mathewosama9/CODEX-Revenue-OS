// What the product does today and what is still coming, in one place: the
// site's features, roadmap and home page, and the in-app overview, all read it.
// Change a status here when a feature ships (docs/website/site-plan.md).

/** live = in the product; beta = in the product, in Assisted mode; soon = not built yet. */
export type FeatureStatus = 'live' | 'beta' | 'soon';

/** Groups on the features page; null = shown on the roadmap only. */
export type FeatureGroup = 'recovery' | 'measurement' | 'conversations' | 'compliance' | 'team' | 'platform';

/** Roadmap columns. No dates: they depend on the pilots (STATUS.md). */
export type Horizon = 'now' | 'next' | 'launch' | 'later';

export const FEATURES = [
    { id: 'cartRecovery', group: 'recovery', status: 'live', horizon: 'now' },
    { id: 'discountPolicy', group: 'recovery', status: 'live', horizon: 'now' },
    { id: 'quietHours', group: 'recovery', status: 'live', horizon: 'now' },
    { id: 'holdout', group: 'measurement', status: 'live', horizon: 'now' },
    { id: 'profitReport', group: 'measurement', status: 'live', horizon: 'now' },
    { id: 'costLedger', group: 'measurement', status: 'live', horizon: 'now' },
    { id: 'margins', group: 'measurement', status: 'live', horizon: 'now' },
    { id: 'inbox', group: 'conversations', status: 'live', horizon: 'now' },
    { id: 'salesAssistant', group: 'conversations', status: 'beta', horizon: 'now' },
    { id: 'takeover', group: 'conversations', status: 'beta', horizon: 'now' },
    { id: 'autoReply', group: 'conversations', status: 'soon', horizon: 'next' },
    { id: 'cod', group: 'conversations', status: 'soon', horizon: 'next' },
    { id: 'consent', group: 'compliance', status: 'live', horizon: 'now' },
    { id: 'dataRequests', group: 'compliance', status: 'live', horizon: 'now' },
    { id: 'team', group: 'team', status: 'live', horizon: 'now' },
    { id: 'usage', group: 'team', status: 'live', horizon: 'now' },
    { id: 'cartAudit', group: 'platform', status: 'soon', horizon: 'next' },
    { id: 'sallaAppStore', group: 'platform', status: 'soon', horizon: 'launch' },
    { id: 'sallaBilling', group: 'platform', status: 'soon', horizon: 'launch' },
    { id: 'saudiHosting', group: null, status: 'soon', horizon: 'launch' },
    { id: 'zid', group: 'platform', status: 'soon', horizon: 'later' },
    { id: 'winBack', group: null, status: 'soon', horizon: 'later' },
    { id: 'crmLite', group: null, status: 'soon', horizon: 'later' },
    { id: 'channels', group: null, status: 'soon', horizon: 'later' },
    { id: 'gulf', group: null, status: 'soon', horizon: 'later' },
] as const satisfies readonly { id: string; group: FeatureGroup | null; status: FeatureStatus; horizon: Horizon }[];

export type FeatureId = (typeof FEATURES)[number]['id'];

export const FEATURE_GROUPS: FeatureGroup[] = ['recovery', 'measurement', 'conversations', 'compliance', 'team', 'platform'];

export const HORIZONS: Horizon[] = ['now', 'next', 'launch', 'later'];

/** The next things merchants will get, for the home page and the in-app overview. */
export const COMING_NEXT: FeatureId[] = ['autoReply', 'cartAudit', 'sallaAppStore'];
