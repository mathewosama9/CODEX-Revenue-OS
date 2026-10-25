import {
    BarChart3,
    BadgePercent,
    BookOpenText,
    Bot,
    ClipboardCheck,
    FileLock2,
    Gauge,
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

/** First screen the member is allowed to open. */
export function homeFor(team: TeamMembership): string {
    return NAV.find((item) => item.permission !== null && team.permissions.includes(item.permission))?.path ?? '/settings';
}
