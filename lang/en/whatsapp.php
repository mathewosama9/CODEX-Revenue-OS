<?php

// Embedded Signup failures the merchant can act on (WhatsAppSignupFailed).
return [
    'region_not_set' => "WhatsApp connection isn't available yet: the data storage region hasn't been set. Contact the CODEX team.",
    'already_connected' => 'This account is connected to another workspace. Disconnect it there first, or contact the CODEX team.',
    'code_rejected' => 'The Meta session expired before the connection finished. Start connecting again.',
    'registration_failed' => 'Meta refused to register the number. If the number has a two-step PIN, enter that same PIN.',
    'meta_error' => 'Meta refused the request. Try again in a few minutes.',
];
