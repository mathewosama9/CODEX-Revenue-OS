<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_requires_authentication()
    {
        $this->postJson(route('password.confirm.store'), ['password' => 'password'])->assertUnauthorized();

        // A plain browser is sent to the app's sign-in, not a Laravel page (ADR-002).
        $this->post(route('password.confirm.store'), ['password' => 'password'])->assertRedirect(config('app.frontend_url').'/ar/login');
    }
}
