// Response shapes of the Laravel API (routes/api/tenant.php). Money is in halalas.

export type Checklist = Record<
    | 'store_connected'
    | 'whatsapp_connected'
    | 'number_quality_ok'
    | 'first_template_approved'
    | 'margins_entered'
    | 'holdout_active'
    | 'consent_source_ready',
    boolean
>;

export type RecoverySettings = {
    enabled: boolean;
    first_delay_minutes: number;
    second_delay_minutes: number;
    max_messages: number;
    min_cart_halalas: number;
    frequency_days: number;
    discount_enabled: boolean;
    discount_percent: number;
    discount_code: string | null;
    discount_cap_percent: number;
    margin_floor_bps: number;
    monthly_discount_budget_halalas: number;
    approval_window_minutes: number;
};

export type Recovery = {
    settings: RecoverySettings;
    go_live_checklist: Checklist;
};

export type WhatsApp = {
    connected: boolean;
    number: {
        display_phone_number: string;
        quality_rating: 'GREEN' | 'YELLOW' | 'RED' | 'UNKNOWN';
        status: string;
    } | null;
    templates: {
        name: string;
        language: string;
        category: string;
        status: string;
        purpose: string;
    }[];
};

export type Margins = {
    default: { margin_bps: number; source: string };
    categories: {
        external_id: string;
        name: string;
        margin_bps: number | null;
    }[];
};

export type Approval = {
    id: number;
    percent: number;
    reason: string;
    cart_id: number;
    cart_total_halalas: number | null;
    expires_at: string;
};

export type Interval = {
    low: number | null;
    point: number | null;
    high: number | null;
    per_customer?: { low: number; high: number };
    win_probability: number | null;
    verdict: Verdict;
};

export type Verdict = 'helpful' | 'harmful' | 'inconclusive';

type ReportBase = {
    period: { from: string; to: string };
    method_version: string;
    outcome_window_days: number;
    holdout_bps: number;
};

export type ProfitReport =
    | (ReportBase & { status: 'frozen'; reason: string })
    | (ReportBase & {
          status: 'ok';
          sample: { treatment: number; control: number; not_yet_matured: number };
          conversion: { treatment: number | null; control: number | null };
          attributed_revenue_halalas: number;
          incremental_revenue_halalas: Interval;
          incremental_profit_halalas: Interval;
          costs_halalas: Record<string, number> & { total: number };
          margin_quality: { revenue_on_assumed_margin_share: number; warning: boolean };
          verdict: Verdict;
          additional_customers_needed: number;
      });

export type Snapshot = {
    period_start: string;
    period_end: string;
    method_version: string;
    report: ProfitReport;
};

export type ConversationSummary = {
    id: number;
    /** Masked by the API; never the full number. */
    phone: string | null;
    status: 'open' | 'pending' | 'closed';
    reason: string | null;
    assigned_user_id: number | null;
    /** A member took over: the AI writes nothing here (US-E12-03). */
    ai_paused: boolean;
    last_message_at: string;
};

export type Conversation = ConversationSummary & {
    messages: {
        id: number;
        direction: string;
        body: string | null;
        template: string | null;
        status: string;
        /** ai_draft | ai_autonomous | ai_night_handoff | null (a person or a template). */
        source: string | null;
        at: string;
    }[];
    notes: { body: string; user_id: number | null; at: string }[];
};

export type Usage = { month: string; usage: Record<string, number> };

export type Members = {
    members: { id: number; name: string; role: 'owner' | 'agent'; email?: string }[];
    invitations: { id: number; email: string; role: 'owner' | 'agent'; expires_at: string | null }[];
};

export type ConsentImportSummary = {
    id: number;
    rows_total: number;
    granted: number;
    already_granted: number;
    skipped_opted_out: number;
    skipped_invalid: number;
    collected_via?: string;
    created_at?: string;
};

export type DraftStatus = 'pending' | 'sending' | 'approved' | 'edited' | 'rejected' | 'superseded' | 'expired' | 'failed';

export type ReplyDraft = {
    id: number;
    conversation_id: number;
    status: DraftStatus;
    body: string;
    final_body: string | null;
    send_error: string | null;
    decided_by: number | null;
    decided_at: string | null;
    at: string;
    /** Only in the drafts list: masked by the API. */
    phone?: string | null;
};

export type AgentRun = {
    id: number;
    status: 'running' | 'drafted' | 'sent' | 'handed_off' | 'skipped';
    handoff_reason: string | null;
    attempts: number;
    validator_blocks: number;
    tools: { attempt: number; tool?: string; product_id?: string; topic?: string; failed?: boolean; blocked_numbers?: string[] }[];
    draft: ReplyDraft | null;
    at: string;
};

export type AiActivity = { ai_paused: boolean; runs: AgentRun[] };

export type AgentMode = 'off' | 'assisted' | 'scoped_autonomous';

export type AgentSettings = {
    mode: AgentMode;
    daily_cap_usd: number;
    max_daily_cap_usd: number;
    spent_today_usd: number;
    handoff_cart_sar: number | null;
    assisted_days: number;
    assisted_days_required: number;
    autonomy_blockers: string[];
    autonomy_confirmation: string;
    auto_downgraded_at: string | null;
    draft_quality: { approved: number; edited: number; rejected: number; edit_rate: number | null };
    validator: { runs: number; blocked: number; rate: number | null };
    model: string;
};

export type KnowledgeKind = 'faq' | 'shipping' | 'returns';

export type KnowledgeDocument = {
    id: number;
    kind: KnowledgeKind;
    title: string;
    body: string;
    updated_at: string;
};
