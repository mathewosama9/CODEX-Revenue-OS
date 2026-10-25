<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_visitors_to_the_merchant_app()
    {
        // Laravel is the API (ADR-002); the app lives on its own domain.
        $this->get(route('home'))->assertRedirect(config('app.frontend_url'));
    }
}
