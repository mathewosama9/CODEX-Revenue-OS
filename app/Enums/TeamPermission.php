<?php

namespace App\Enums;

/**
 * MVP permission matrix (docs/blueprint/09-technical-architecture.md §9.13).
 */
enum TeamPermission: string
{
    case UpdateTeam = 'team:update';
    case DeleteTeam = 'team:delete';

    case AddMember = 'member:add';
    case UpdateMember = 'member:update';
    case RemoveMember = 'member:remove';

    case CreateInvitation = 'invitation:create';
    case CancelInvitation = 'invitation:cancel';

    case ManageIntegrations = 'integrations:manage';
    case ManageRecoverySettings = 'recovery:manage';
    case ManageDiscountPolicy = 'discounts:manage';
    case ManageMargins = 'margins:manage';
    case ViewProfitReport = 'reports:view';
    case ManageBilling = 'billing:manage';
    case ManageDataRequests = 'data-requests:manage';
    case ManageAutonomy = 'ai:autonomy';

    case UseInbox = 'inbox:use';
    case ReviewAiDrafts = 'ai-drafts:review';
    case UseKillSwitch = 'ai:kill-switch';
}
