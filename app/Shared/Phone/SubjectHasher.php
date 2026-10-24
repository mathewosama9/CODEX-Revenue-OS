<?php

namespace App\Shared\Phone;

use RuntimeException;

/**
 * Stable per-tenant hash of a customer phone (09 §9.5). The same customer gets
 * a different hash in every tenant, so hashes can't be joined across merchants.
 */
final class SubjectHasher
{
    public function __construct(private readonly string $key)
    {
        if ($key === '') {
            throw new RuntimeException('CODEX_SUBJECT_HASH_KEY is not set.');
        }
    }

    public function hash(int $tenantId, PhoneNumber $phone): string
    {
        return hash_hmac('sha256', $tenantId.'|'.$phone->e164, $this->key);
    }
}
