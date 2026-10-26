<?php

namespace Tests;

use App\Modules\Commerce\WooCommerce\WooHostResolver;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // No test may reach a real provider (Salla, Meta, Anthropic, Voyage):
        // a missing fake fails loudly instead of spending money.
        Http::preventStrayRequests();

        // No real DNS either: every WooCommerce site "resolves" to one public
        // address. Tests of the SSRF guard use the real class directly.
        $this->app->instance(WooHostResolver::class, new class extends WooHostResolver
        {
            public function publicAddresses(string $host): array
            {
                return ['93.184.215.14'];
            }
        });
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
