import {
    BarChart3,
    BadgePercent,
    ClipboardCheck,
    FileLock2,
    Gauge,
    MessagesSquare,
    RotateCcw,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import type { Dictionary } from '@/i18n/ar';
import type { Permission, TeamMembership } from '@/lib/session';

export type NavItem = {
    path: string;
    label: keyof Dictionary['nav'];
    icon: LucideIcon;
    permission: Permission;
};

// Hidden when the user lacks the permission; the API refuses anyway.
export const NAV: NavItem[] = [
    { path: '/onboarding', label: 'onboarding', icon: ClipboardCheck, permission: 'integrations:manage' },
    { path: '/recovery', label: 'recovery', icon: RotateCcw, permission: 'recovery:manage' },
    { path: '/approvals', label: 'approvals', icon: BadgePercent, permission: 'discounts:manage' },
    { path: '/report', label: 'report', icon: BarChart3, permission: 'reports:view' },
    { path: '/inbox', label: 'inbox', icon: MessagesSquare, permission: 'inbox:use' },
    { path: '/usage', label: 'usage', icon: Gauge, permission: 'billing:manage' },
    { path: '/data-requests', label: 'dataRequests', icon: FileLock2, permission: 'data-requests:manage' },
];

/** First screen the member is allowed to open. */
export function homeFor(team: TeamMembership): string {
    return NAV.find((item) => team.permissions.includes(item.permission))?.path ?? '/inbox';
}
