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
    last_message_at: string;
};

export type Conversation = ConversationSummary & {
    messages: {
        direction: string;
        body: string | null;
        template: string | null;
        status: string;
        at: string;
    }[];
    notes: { body: string; user_id: number | null; at: string }[];
};

export type Usage = { month: string; usage: Record<string, number> };
