<?php

namespace App\Modules\Commerce\WooCommerce;

use RuntimeException;

/**
 * A WooCommerce or plugin request that did not succeed. The message never
 * carries the URL: with query-string auth it holds the consumer secret.
 */
class WooRequestFailed extends RuntimeException
{
    /** No answer at all (DNS, TLS, timeout). */
    public const UNREACHABLE = 0;

    public function __construct(public readonly int $status, string $message)
    {
        parent::__construct($message);
    }

    public static function unreachable(): self
    {
        return new self(self::UNREACHABLE, 'The WooCommerce site could not be reached.');
    }
}
