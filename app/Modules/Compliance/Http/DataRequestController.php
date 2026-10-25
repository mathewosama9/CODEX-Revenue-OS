<?php

namespace App\Modules\Compliance\Http;

use App\Modules\Compliance\DataSubjectRequests;
use App\Shared\Phone\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class DataRequestController
{
    public function __invoke(Request $request, DataSubjectRequests $requests): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'max:32'],
            'type' => ['required', 'in:export,delete'],
        ]);

        $phone = PhoneNumber::tryParse($validated['phone']) ?? throw ValidationException::withMessages(['phone' => __('Invalid phone number.')]);

        return response()->json(['data' => $validated['type'] === 'export'
            ? $requests->export($phone, $request->user()?->id)
            : $requests->delete($phone, $request->user()?->id)]);
    }
}
