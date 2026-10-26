<?php

namespace App\Modules\Commerce;

use RuntimeException;

/**
 * A connect that would move a store between workspaces, or give one
 * workspace two live stores. The message is for logs; the API maps the
 * reason to its own text.
 */
class StoreAlreadyConnected extends RuntimeException
{
    public const OTHER_WORKSPACE = 'other_workspace';

    public const OTHER_PLATFORM = 'other_platform';

    private function __construct(public readonly string $reason, string $message)
    {
        parent::__construct($message);
    }

    public static function toAnotherWorkspace(string $provider): self
    {
        return new self(self::OTHER_WORKSPACE, "This {$provider} store is connected to another workspace.");
    }

    public static function onAnotherPlatform(string $activeProvider): self
    {
        return new self(self::OTHER_PLATFORM, "This workspace already has an active {$activeProvider} store; disconnect it first.");
    }
}
