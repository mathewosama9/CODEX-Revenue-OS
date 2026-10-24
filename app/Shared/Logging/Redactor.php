<?php

namespace App\Shared\Logging;

/**
 * Strips personal data before anything leaves for logs or error trackers
 * (E15, ADR-015): phone numbers, emails, and secret-looking keys.
 */
final class Redactor
{
    private const SECRET_KEYS = [
        'access_token', 'refresh_token', 'token', 'password', 'secret', 'authorization',
        'phone', 'mobile', 'email', 'body', 'text', 'name', 'address', 'payload',
    ];

    public static function text(string $value): string
    {
        // Saudi / international phones in any common shape, Latin or Arabic digits.
        $value = (string) preg_replace('/(?<![\d٠-٩])(\+|00)?[\d٠-٩][\d٠-٩\s\-]{7,16}[\d٠-٩](?![\d٠-٩])/u', '[phone]', $value);

        return (string) preg_replace('/[^\s@"\']+@[^\s@"\']+\.[a-z]{2,}/i', '[email]', $value);
    }

    /**
     * @param  array<array-key, mixed>  $context
     * @return array<array-key, mixed>
     */
    public static function context(array $context): array
    {
        foreach ($context as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::SECRET_KEYS, true)) {
                $context[$key] = '[redacted]';
            } elseif (is_array($value)) {
                $context[$key] = self::context($value);
            } elseif (is_string($value)) {
                $context[$key] = self::text($value);
            }
        }

        return $context;
    }
}
