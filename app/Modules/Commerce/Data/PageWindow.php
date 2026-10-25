<?php

namespace App\Modules\Commerce\Data;

/**
 * A slice of a paginated list, so long imports can stop and resume (review H3).
 * After iterating, `next` holds the page to continue from, or null when done.
 */
final class PageWindow
{
    public ?int $next = null;

    public function __construct(
        public readonly int $from = 1,
        public readonly int $pages = 200,
    ) {}
}
