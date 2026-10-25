<?php

namespace App\Modules\Messaging\Http;

use App\Modules\Messaging\Models\MessageTemplate;
use App\Modules\Messaging\Models\WhatsAppAccount;
use App\Modules\Messaging\Models\WhatsAppNumber;
use App\Modules\Messaging\TemplateRegistry;
use App\Modules\Messaging\WhatsAppOnboarding;
use App\Modules\Messaging\WhatsAppSignupFailed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppController
{
    public function show(): JsonResponse
    {
        $number = WhatsAppNumber::query()->first();

        return response()->json(['data' => [
            'connected' => $number !== null && $number->status === 'active',
            'number' => $number === null ? null : [
                'display_phone_number' => $number->display_phone_number,
                'quality_rating' => $number->quality_rating->value,
                'status' => $number->status,
            ],
            'templates' => MessageTemplate::query()->orderBy('name')->get()->map(fn (MessageTemplate $template) => [
                'name' => $template->name,
                'language' => $template->language,
                'category' => $template->category,
                'status' => $template->status,
                'purpose' => $template->purpose,
            ])->values(),
        ]]);
    }

    /** Embedded Signup v4 finished in the browser; finish it server-side. */
    public function completeSignup(Request $request, WhatsAppOnboarding $onboarding): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:2048'],
            'waba_id' => ['required', 'string', 'max:64'],
            'phone_number_id' => ['required', 'string', 'max:64'],
            'pin' => ['required', 'digits:6'],
        ]);

        try {
            $onboarding->complete($validated['code'], $validated['waba_id'], $validated['phone_number_id'], $validated['pin'], $request->user()?->id);
        } catch (WhatsAppSignupFailed $exception) {
            // Reason and class only: Meta's exception can carry the app secret in its URL (review H2).
            Log::warning('WhatsApp Embedded Signup failed.', ['reason' => $exception->reason, 'cause' => $exception->getPrevious() === null ? null : $exception->getPrevious()::class]);

            // 422 with a stable reason the app translates, instead of a bare 500 (review B9).
            return response()->json([
                'message' => __('whatsapp.'.$exception->reason),
                'reason' => $exception->reason,
                'errors' => ['signup' => [__('whatsapp.'.$exception->reason)]],
            ], $exception->reason === WhatsAppSignupFailed::ALREADY_CONNECTED ? 409 : 422);
        }

        return $this->show();
    }

    public function submitTemplates(TemplateRegistry $templates): JsonResponse
    {
        $templates->submitLibrary(WhatsAppAccount::query()->firstOrFail());

        return $this->show();
    }

    public function syncTemplates(TemplateRegistry $templates): JsonResponse
    {
        $templates->sync(WhatsAppAccount::query()->firstOrFail());

        return $this->show();
    }
}
