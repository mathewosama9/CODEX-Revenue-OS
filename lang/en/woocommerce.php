<?php

// WooCommerce connect failures the merchant can act on (WooConnectFailed).
return [
    'invalid_url' => 'Enter the full site address starting with https://, e.g. https://shop.example.com.',
    'unreachable' => 'We could not reach the site. Check the address and that the site is up, then try again.',
    'redirected' => 'The site redirects to another address. Enter the final address as it shows in the browser.',
    'bad_keys' => 'WooCommerce rejected the keys. Check the consumer key and secret and that their permission is Read.',
    'not_woocommerce' => 'We found no WooCommerce REST API on this site. Check that WooCommerce is active and the address is right.',
    'currency' => 'We do not support the store currency (:currency) yet. Supported currencies: :supported.',
    'currency_mismatch' => 'This workspace already has store data in :workspace_currency, and this store sells in :currency. Connect it to a new workspace: one workspace has one currency.',
    'other_workspace' => 'This store is connected to another workspace. Disconnect it there first, or contact the CODEX team.',
    'other_platform' => 'This workspace is already connected to another store. Disconnect it first, then connect WooCommerce.',
];
