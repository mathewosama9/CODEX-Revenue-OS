<?php

namespace App\Modules\Compliance\Consent;

/** Where a consent record came from (US-E04-01). Grants need documented evidence. */
enum ConsentSource: string
{
    case CheckoutOptIn = 'checkout_opt_in';
    case MerchantImport = 'merchant_import';
    case OptOutKeyword = 'opt_out_keyword';
    case TemplateButton = 'template_button';
    case MerchantRequest = 'merchant_request';
}
