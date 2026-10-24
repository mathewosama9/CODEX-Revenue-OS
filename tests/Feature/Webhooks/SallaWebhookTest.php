<?php

use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

function sallaPayload(array $overrides = []): string
{
    return json_encode(array_merge([
        'event' => 'abandoned.cart',
        'merchant' => 1234509876,
        'created_at' => '2026-10-25 10:00:00',
        'data' => ['id' => 555, 'customer' => ['mobile' => '+966512345678']],
    ], $overrides));
}

function postSalla(string $body, ?string $signature = null)
{
    $signature ??= hash_hmac('sha256', $body, 'testing-salla-secret');

    return test()->call('POST', '/webhooks/salla', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_SALLA_SIGNATURE' => $signature,
    ], content: $body);
}

it('accepts a correctly signed webhook and stores it once', function () {
    postSalla(sallaPayload())->assertOk();

    $event = WebhookEvent::sole();

    expect($event->provider)->toBe('salla')
        ->and($event->event_type)->toBe('abandoned.cart')
        ->and($event->status)->toBe(WebhookStatus::Unhandled)
        ->and($event->payload['data']['id'])->toBe(555);
});

it('treats Salla retries as one event', function () {
    $body = sallaPayload();

    postSalla($body)->assertOk();
    postSalla($body)->assertOk();
    postSalla($body)->assertOk();

    expect(WebhookEvent::count())->toBe(1);
});

it('rejects a wrong signature without storing anything', function () {
    postSalla(sallaPayload(), 'not-the-signature')->assertUnauthorized();

    expect(WebhookEvent::count())->toBe(0);
});

it('rejects a body that was changed after signing', function () {
    $signature = hash_hmac('sha256', sallaPayload(), 'testing-salla-secret');

    postSalla(sallaPayload(['merchant' => 999]), $signature)->assertUnauthorized();
});

it('rejects everything when no secret is configured', function () {
    config(['services.salla.webhook_secret' => null]);

    postSalla(sallaPayload())->assertUnauthorized();
});

it('stores the payload encrypted, not as readable PII', function () {
    postSalla(sallaPayload())->assertOk();

    $raw = DB::table('webhook_events')->value('payload');

    expect($raw)->not->toContain('966512345678');
});

it('does not need a session or CSRF token', function () {
    postSalla(sallaPayload())->assertOk()->assertCookieMissing('laravel_session');
});
