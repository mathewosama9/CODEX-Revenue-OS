<?php

namespace App\Modules\Commerce\WooCommerce;

use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Phone\PhoneNumber;

/**
 * The checkout consent box (protocol §5, §6), recorded before the cart: the
 * cart's CartAbandoned asks the consent gate at once. Used by the webhook
 * and by reconciliation, so a cart pulled before its event arrives is not
 * judged without consent. Only a ticked box with a phone is a grant; a
 * later opt-out wins unless a new cart was ticked after it (ConsentGate).
 */
final class WooCheckoutConsent
{
    public function __construct(private readonly ConsentGate $consent) {}

    /**
     * @param  array<string, mixed>  $cart  a plugin Cart
     */
    public function record(array $cart): void
    {
        $phone = PhoneNumber::tryParse(is_string($cart['phone'] ?? null) ? $cart['phone'] : null);

        if (($cart['consent'] ?? null) !== true || $phone === null || $this->consent->mayMessage($phone)) {
            return;
        }

        $text = is_string($cart['consent_text'] ?? null) ? trim($cart['consent_text']) : '';

        $this->consent->grant($phone, ConsentSource::CheckoutOptIn, array_filter([
            'text' => $text === '' ? null : mb_substr($text, 0, 2000),
            'text_version' => is_string($cart['consent_text_version'] ?? null) ? mb_substr($cart['consent_text_version'], 0, 64) : null,
            'platform' => TenantRoute::WOOCOMMERCE,
            'cart_id' => (string) ($cart['id'] ?? ''),
            // When the box was ticked: lets a genuinely new checkout opt back in after an opt-out.
            'captured_at' => is_string($cart['updated_at'] ?? null) ? $cart['updated_at'] : null,
        ]));
    }
}
