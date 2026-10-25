import {
    BarChart3,
    BadgePercent,
    BookOpenText,
    Bot,
    ClipboardCheck,
    FileLock2,
    Gauge,
    LayoutDashboard,
    MessageSquareText,
    MessagesSquare,
    RotateCcw,
    Settings,
    ShieldCheck,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { Dictionary } from '@/i18n/ar';
import type { Permission, TeamMembership } from '@/lib/session';

export type NavItem = {
    path: string;
    label: keyof Dictionary['nav'];
    icon: LucideIcon;
    /** null = every member. */
    permission: Permission | null;
};

// Hidden when the user lacks the permission; the API refuses anyway.
export const NAV: NavItem[] = [
    { path: '/overview', label: 'overview', icon: LayoutDashboard, permission: null },
    { path: '/onboarding', label: 'onboarding', icon: ClipboardCheck, permission: 'integrations:manage' },
    { path: '/recovery', label: 'recovery', icon: RotateCcw, permission: 'recovery:manage' },
    { path: '/approvals', label: 'approvals', icon: BadgePercent, permission: 'discounts:manage' },
    { path: '/consent', label: 'consent', icon: ShieldCheck, permission: 'consent:import' },
    { path: '/report', label: 'report', icon: BarChart3, permission: 'reports:view' },
    { path: '/inbox', label: 'inbox', icon: MessagesSquare, permission: 'inbox:use' },
    { path: '/drafts', label: 'drafts', icon: MessageSquareText, permission: 'ai-drafts:review' },
    { path: '/knowledge', label: 'knowledge', icon: BookOpenText, permission: 'knowledge:manage' },
    { path: '/ai', label: 'ai', icon: Bot, permission: 'ai:autonomy' },
    { path: '/usage', label: 'usage', icon: Gauge, permission: 'billing:manage' },
    { path: '/data-requests', label: 'dataRequests', icon: FileLock2, permission: 'data-requests:manage' },
    { path: '/settings', label: 'settings', icon: Settings, permission: null },
];

/** First screen after sign-in: the overview, which every member may open (its cards follow their permissions). */
export function homeFor(team: TeamMembership): string {
    // Kept so a per-role home can come back without touching the callers.
    void team;

    return '/overview';
}
