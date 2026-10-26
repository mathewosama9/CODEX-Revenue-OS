<?php

namespace App\Modules\Commerce\Http;

use App\Modules\Commerce\WooCommerce\WooConnectFailed;
use App\Modules\Commerce\WooCommerce\WooConnections;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Connect a WooCommerce site from the app (protocol §1). The Store ID and the
 * connector secret are in the connect (and rotate) answer only, once.
 */
class WooCommerceController
{
    public function connect(Request $request, WooConnections $woo): JsonResponse
    {
        $validated = $request->validate([
            'site_url' => ['required', 'string', 'max:255'],
            'consumer_key' => ['required', 'string', 'max:128', 'regex:/^ck_[A-Za-z0-9]+$/'],
            'consumer_secret' => ['required', 'string', 'max:128', 'regex:/^cs_[A-Za-z0-9]+$/'],
        ]);

        $siteUrl = WooConnections::normalizeSiteUrl($validated['site_url']);

        if ($siteUrl === null) {
            throw ValidationException::withMessages(['site_url' => __('woocommerce.invalid_url')]);
        }

        try {
            $connection = $woo->connect($siteUrl, $validated['consumer_key'], $validated['consumer_secret'], $request->user()?->id);
        } catch (WooConnectFailed $exception) {
            Log::info('WooCommerce connect failed.', ['reason' => $exception->reason]);

            $field = in_array($exception->reason, [WooConnectFailed::BAD_KEYS], true) ? 'consumer_key' : 'site_url';

            $message = __('woocommerce.'.$exception->reason, $exception->replace);

            return response()->json([
                'message' => $message,
                'reason' => $exception->reason,
                'errors' => [$field => [$message]],
            ], in_array($exception->reason, [WooConnectFailed::OTHER_WORKSPACE, WooConnectFailed::OTHER_PLATFORM, WooConnectFailed::CURRENCY_MISMATCH], true) ? 409 : 422);
        }

        return $this->secretResponse($connection, 201);
    }

    public function rotateSecret(Request $request, WooConnections $woo): JsonResponse
    {
        $connection = $woo->rotateSecret($request->user()?->id);

        abort_if($connection === null, 404);

        return $this->secretResponse($connection);
    }

    public function disconnect(Request $request, WooConnections $woo): JsonResponse
    {
        $woo->disconnect($request->user()?->id);

        return response()->json(['data' => ['connected' => false]]);
    }

    /**
     * @param  array{store_id: string, connector_secret: string}  $connection
     */
    private function secretResponse(array $connection, int $status = 200): JsonResponse
    {
        $base = rtrim((string) config('app.url'), '/');

        return response()->json(['data' => [
            'store_id' => $connection['store_id'],
            'connector_secret' => $connection['connector_secret'],
            'api_base' => $base,
            'webhook_url' => "{$base}/webhooks/woocommerce/{$connection['store_id']}",
        ]], $status)->header('Cache-Control', 'no-store');
    }
}
