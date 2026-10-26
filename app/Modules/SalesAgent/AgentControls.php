<?php

namespace App\Modules\SalesAgent;

use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\SalesAgent\Models\AgentSettings;
use App\Modules\SalesAgent\Models\ReplyDraft;
use App\Shared\Tenancy\WorkspaceLocale;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * Per-tenant agent settings (09 §9.11.3). A tenant may turn the agent off or
 * on in Assisted mode. Scoped Autonomous needs all three: CODEX's admin flag,
 * at least 4 weeks of Assisted, and the owner's written approval (audited).
 */
class AgentControls
{
    public const ASSISTED_DAYS_BEFORE_AUTONOMY = 28;

    /** Typed by the owner to approve autonomy (US-E11-06). */
    public const AUTONOMY_CONFIRMATION = 'أوافق على الردود التلقائية';

    public function __construct(
        private readonly AuditLogger $audit,
        private readonly int $defaultCapUsdMicros,
        private readonly float $defaultHandoffCartUsd,
        private readonly WorkspaceLocale $locale,
    ) {}

    public function settings(): AgentSettings
    {
        // No handoff_cart_halalas: null means "the default", converted when read, so a
        // workspace whose currency is set later (store connect) still gets the right one.
        return AgentSettings::query()->firstOrCreate([], [
            'mode' => AgentMode::Off,
            'daily_cap_usd_micros' => $this->defaultCapUsdMicros,
        ]);
    }

    /**
     * The cart value that goes to a person, in minor units of the workspace
     * currency (ADR-012): the merchant's own, else the dollar default converted.
     */
    public function handoffCartMinor(?AgentSettings $settings = null): int
    {
        return ($settings ?? $this->settings())->handoff_cart_halalas
            ?? $this->locale->currency()->minorFromUsd($this->defaultHandoffCartUsd);
    }

    public function defaultCapUsdMicros(): int
    {
        return $this->defaultCapUsdMicros;
    }

    /**
     * The merchant may lower the cap, never raise it above CODEX's default.
     * The cart threshold is in minor units of the workspace currency.
     */
    public function updateLimits(int $userId, ?int $dailyCapUsdMicros, ?int $handoffCartMinor): AgentSettings
    {
        $settings = $this->settings();

        if ($dailyCapUsdMicros !== null && ($dailyCapUsdMicros < 100_000 || $dailyCapUsdMicros > $this->defaultCapUsdMicros)) {
            throw ValidationException::withMessages(['daily_cap_usd' => 'out_of_range']);
        }

        $settings->update(array_filter([
            'daily_cap_usd_micros' => $dailyCapUsdMicros,
            'handoff_cart_halalas' => $handoffCartMinor,
        ], fn ($value) => $value !== null));

        $this->audit->record('ai.limits_updated', ActorType::User, $userId, subject: $settings, context: [
            'daily_cap_usd_micros' => $settings->daily_cap_usd_micros,
            'handoff_cart_halalas' => $settings->handoff_cart_halalas,
            'currency' => $this->locale->currency()->code,
        ]);

        return $settings;
    }

    public function setMode(int $userId, AgentMode $mode, ?string $confirmation = null): AgentSettings
    {
        $settings = $this->settings();

        if ($mode === AgentMode::ScopedAutonomous) {
            $blockers = $this->autonomyBlockers($settings);

            if ($confirmation !== self::AUTONOMY_CONFIRMATION) {
                $blockers[] = 'owner_confirmation';
            }

            if ($blockers !== []) {
                throw ValidationException::withMessages(['mode' => $blockers]);
            }
        }

        $settings->update([
            'mode' => $mode,
            // Off resets the clock: autonomy needs 4 weeks of Assisted in a row (review: security #6).
            'assisted_since' => $mode === AgentMode::Off ? null : ($settings->assisted_since ?? CarbonImmutable::now()),
        ] + ($mode === AgentMode::ScopedAutonomous ? ['autonomy_approved_by' => $userId, 'autonomy_approved_at' => CarbonImmutable::now()] : []));

        $this->audit->record('ai.mode_changed', ActorType::User, $userId, subject: $settings, context: ['mode' => $mode->value]);

        return $settings;
    }

    /**
     * What still stands between this tenant and Scoped Autonomous.
     *
     * @return list<string>
     */
    public function autonomyBlockers(?AgentSettings $settings = null): array
    {
        $settings ??= $this->settings();
        $blockers = [];

        if (! $settings->autonomy_allowed_by_admin) {
            $blockers[] = 'admin_flag';
        }

        if ($this->assistedDays($settings) < self::ASSISTED_DAYS_BEFORE_AUTONOMY) {
            $blockers[] = 'assisted_weeks';
        }

        return $blockers;
    }

    public function assistedDays(?AgentSettings $settings = null): int
    {
        $since = ($settings ?? $this->settings())->assisted_since;

        return $since === null ? 0 : (int) $since->diffInDays(CarbonImmutable::now());
    }

    /** CODEX admin only (Platform), audited as the admin. */
    public function allowAutonomy(bool $allowed, int $adminId): void
    {
        $settings = $this->settings();
        $settings->update(['autonomy_allowed_by_admin' => $allowed]);

        if (! $allowed && $settings->mode === AgentMode::ScopedAutonomous) {
            $settings->update(['mode' => AgentMode::Assisted]);
        }

        $this->audit->record('ai.autonomy_flag', ActorType::Admin, $adminId, subject: $settings, context: ['allowed' => $allowed]);
    }

    /**
     * Assisted quality over the last 4 weeks: how often members changed or
     * rejected the AI's draft (input to US-E11-06).
     *
     * @return array{approved: int, edited: int, rejected: int, edit_rate: float|null}
     */
    public function draftQuality(): array
    {
        $counts = ReplyDraft::query()
            ->where('decided_at', '>=', CarbonImmutable::now()->subDays(self::ASSISTED_DAYS_BEFORE_AUTONOMY))
            ->whereIn('status', ['approved', 'edited', 'rejected'])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $approved = (int) ($counts['approved'] ?? 0);
        $edited = (int) ($counts['edited'] ?? 0);
        $rejected = (int) ($counts['rejected'] ?? 0);
        $decided = $approved + $edited + $rejected;

        return [
            'approved' => $approved,
            'edited' => $edited,
            'rejected' => $rejected,
            'edit_rate' => $decided === 0 ? null : round(($edited + $rejected) / $decided, 4),
        ];
    }
}
