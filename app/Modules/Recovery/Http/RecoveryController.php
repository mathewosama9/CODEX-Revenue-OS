<?php

namespace App\Modules\Recovery\Http;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Recovery\ActionStatus;
use App\Modules\Recovery\GoLiveChecklist;
use App\Modules\Recovery\Models\DiscountApproval;
use App\Modules\Recovery\Models\RecoverySettings;
use App\Modules\Recovery\RecoveryControls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecoveryController
{
    public function __construct(
        private readonly GoLiveChecklist $checklist,
        private readonly AuditLogger $audit,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json(['data' => [
            'settings' => RecoverySettings::current()->only([
                'enabled', 'first_delay_minutes', 'second_delay_minutes', 'max_messages', 'min_cart_halalas',
                'frequency_days', 'discount_enabled', 'discount_percent', 'discount_code', 'discount_cap_percent',
                'margin_floor_bps', 'monthly_discount_budget_halalas', 'approval_window_minutes',
            ]),
            'go_live_checklist' => $this->checklist->items(),
        ]]);
    }

    /**
     * Safe bounds (US-E05-06): never under 60 minutes, never more than 3 messages.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'first_delay_minutes' => ['integer', 'min:60', 'max:4320'],
            'second_delay_minutes' => ['integer', 'min:60', 'max:10080'],
            'max_messages' => ['integer', 'min:1', 'max:3'],
            'min_cart_halalas' => ['integer', 'min:0'],
            'frequency_days' => ['integer', 'min:1', 'max:60'],
            'discount_enabled' => ['boolean'],
            'discount_percent' => ['integer', 'min:0', 'max:50'],
            'discount_code' => ['nullable', 'string', 'max:64'],
            'discount_cap_percent' => ['integer', 'min:0', 'max:50'],
            'margin_floor_bps' => ['integer', 'min:0', 'max:10000'],
            'monthly_discount_budget_halalas' => ['integer', 'min:0'],
            'approval_window_minutes' => ['integer', 'min:15', 'max:2880'],
        ]);

        $settings = RecoverySettings::current();
        $settings->fill($validated);

        // A discount that is "on" must be able to go out: a code and a budget
        // (no budget = no discounts, Rule 9). Checked on the merged result, so a
        // partial update can't leave a silent half-configured policy (review B6).
        if ($settings->discount_enabled) {
            $missing = array_filter([
                'discount_code' => blank($settings->discount_code) ? __('Enter the Salla discount code.') : null,
                'discount_percent' => $settings->discount_percent <= 0 ? __('Enter a discount above 0%.') : null,
                'monthly_discount_budget_halalas' => $settings->monthly_discount_budget_halalas <= 0 ? __('Set a monthly discount budget above 0.') : null,
            ]);

            if ($missing !== []) {
                throw ValidationException::withMessages($missing);
            }
        }

        $settings->save();

        $this->audit->record('recovery.settings_updated', ActorType::User, $request->user()?->id, subject: $settings, context: [
            'fields' => array_keys($validated),
        ]);

        return $this->show();
    }

    public function enable(Request $request): JsonResponse
    {
        if (! $this->checklist->passes()) {
            throw ValidationException::withMessages(['enabled' => __('Complete the go-live checklist first.')]);
        }

        RecoverySettings::current()->update(['enabled' => true]);
        $this->audit->record('recovery.enabled', ActorType::User, $request->user()?->id);

        return $this->show();
    }

    /** Whether recovery is sending, for anyone holding the kill switch. */
    public function status(): JsonResponse
    {
        return response()->json(['data' => ['enabled' => RecoverySettings::current()->enabled]]);
    }

    /** Kill switch: stops sending now; pending steps are cancelled, not deleted (US-E14-04). */
    public function pause(Request $request, RecoveryControls $controls): JsonResponse
    {
        $controls->pause(ActorType::User, $request->user()?->id, 'merchant_kill_switch');

        // Agents may stop sending, but the settings themselves stay owner-only.
        /** @var User $user */
        $user = $request->user();
        $team = Team::query()->findOrFail(RecoverySettings::current()->tenant_id);

        return $user->hasTeamPermission($team, TeamPermission::ManageRecoverySettings) ? $this->show() : $this->status();
    }

    public function approvals(): JsonResponse
    {
        return response()->json(['data' => DiscountApproval::query()
            ->with('action.sequence')
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            // Not when the sequence already ended (the customer bought or replied).
            ->whereHas('action', fn ($query) => $query->where('status', ActionStatus::AwaitingApproval))
            ->orderBy('expires_at')
            ->get()
            ->map(fn (DiscountApproval $approval) => [
                'id' => $approval->id,
                'percent' => $approval->percent,
                'reason' => $approval->reason,
                'cart_id' => $approval->action->sequence->cart_id,
                'expires_at' => $approval->expires_at->toIso8601String(),
            ])->values()]);
    }

    public function decide(Request $request): JsonResponse
    {
        $validated = $request->validate(['decision' => ['required', 'in:approve,reject']]);

        // One decision per approval, even with two owners clicking at once.
        $record = DB::transaction(function () use ($request, $validated) {
            $record = DiscountApproval::query()->where('status', 'pending')->lockForUpdate()->findOrFail((int) $request->route('approval'));

            abort_if($record->expires_at->isPast(), 410, __('This approval expired; the message went without a discount.'));

            $action = $record->action()->lockForUpdate()->first();

            abort_if($action?->status !== ActionStatus::AwaitingApproval, 409, __('This cart no longer needs a decision.'));

            $record->update([
                'status' => $validated['decision'] === 'approve' ? 'approved' : 'rejected',
                'decided_by' => $request->user()?->id,
                'decided_at' => now(),
            ]);

            // Run the waiting step now with the decision.
            $action->update(['status' => ActionStatus::Pending, 'run_at' => now()]);

            return $record;
        });

        $this->audit->record('discount.'.$record->status, ActorType::User, $request->user()?->id, subject: $record, context: [
            'percent' => $record->percent,
            'reason' => $record->reason,
        ]);

        return $this->approvals();
    }
}
