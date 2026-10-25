<?php

namespace App\Modules\AiGateway;

final readonly class ValidationResult
{
    /**
     * @param  list<string>  $unmatched  numbers in the reply that no tool returned
     */
    public function __construct(public array $unmatched) {}

    public function passed(): bool
    {
        return $this->unmatched === [];
    }
}
