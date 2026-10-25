<?php

namespace App\Modules\SalesAgent;

/**
 * Why a person must answer instead of the AI. Stored on the conversation
 * (`ai_` + value) and on the run, and shown in the inbox.
 */
enum HandoffReason: string
{
    // Deterministic, before any model call (US-E11-03/04).
    case CustomerAskedHuman = 'asked_human';
    case Complaint = 'complaint';
    case DiscountRequest = 'discount_request';
    case HighCartValue = 'high_cart_value';

    // The agent itself, or its guards.
    case AgentHandoff = 'agent_handoff';
    case ValidatorFailed = 'validator_failed';
    case ToolFailed = 'tool_failed';
    case PolicyMissing = 'policy_missing';
    case CapReached = 'cap_reached';
    case ProviderUnavailable = 'provider_unavailable';
    case EmptyReply = 'empty_reply';

    public function conversationReason(): string
    {
        return 'ai_'.$this->value;
    }
}
