<?php

use App\Models\Team;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentRevoked;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Messaging\Data\SendOutcome;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Modules\Messaging\Events\MessageDelivered;
use App\Modules\Messaging\Events\NumberQualityChanged;
use App\Modules\Messaging\MessageSender;
use App\Modules\Messaging\WhatsAppOnboarding;
use App\Shared\Integrations\TenantRoute;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

const WABA = 'waba-1';
const PHONE_ID = 'pn-1';

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->customer = PhoneNumber::tryParse('0512345678');

    $accountId = DB::table('whatsapp_accounts')->insertGetId([
        'tenant_id' => $this->team->id, 'waba_id' => WABA, 'access_token' => encrypt('token', false), 'status' => 'active',
    ]);
    $this->numberId = DB::table('whatsapp_numbers')->insertGetId([
        'tenant_id' => $this->team->id, 'whatsapp_account_id' => $accountId, 'phone_number_id' => PHONE_ID,
        'display_phone_number' => '+966 55 000 0000', 'quality_rating' => 'GREEN', 'status' => 'active',
    ]);
    DB::table('message_templates')->insert([
        'tenant_id' => $this->team->id, 'whatsapp_account_id' => $accountId, 'name' => 'codex_cart_reminder_1_ar_v1',
        'language' => 'ar', 'category' => 'MARKETING', 'status' => 'APPROVED', 'purpose' => 'cart_recovery_1', 'components' => '[]',
    ]);
    TenantRoute::bind(TenantRoute::META, WABA, $this->team->id);
});

function asTeam(Closure $callback): mixed
{
    return app(TenantContext::class)->run(test()->team->id, $callback);
}

function send(string $key = 'cart-1-step-1')
{
    return asTeam(fn () => app(MessageSender::class)->sendTemplate(test()->customer, 'cart_recovery_1', ['متجر العطور', 'https://s/c/1'], null, $key));
}

function metaWebhook(array $value, string $field = 'messages')
{
    $body = json_encode(['object' => 'whatsapp_business_account', 'entry' => [[
        'id' => WABA,
        'changes' => [['field' => $field, 'value' => $value + ['metadata' => ['phone_number_id' => PHONE_ID]]]],
    ]]]);

    return test()->call('POST', '/webhooks/meta', server: [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $body, 'testing-meta-secret'),
    ], content: $body);
}

it('sends a marketing template through the MM API', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

    expect(send()->outcome)->toBe(SendOutcome::Accepted);

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), PHONE_ID.'/marketing_messages')
        && $request['to'] === '966512345678'
        && $request['template']['name'] === 'codex_cart_reminder_1_ar_v1');

    expect(DB::table('messages')->value('wamid'))->toBe('wamid.1');
});

it('never sends twice for the same idempotency key', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);

    send();

    expect(send()->outcome)->toBe(SendOutcome::Duplicate);
    Http::assertSentCount(1);
});

it('does not retry when the outcome is unknown', function () {
    Http::fake(fn () => throw new ConnectionException('timeout'));

    expect(send()->outcome)->toBe(SendOutcome::Unknown)
        ->and(send()->outcome)->toBe(SendOutcome::Duplicate)
        ->and(DB::table('messages')->value('status'))->toBe('unknown');
});

it('reports the per-user marketing limit separately', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['error' => ['code' => 131049, 'message' => 'limit']], 400)]);

    $result = send();

    expect($result->outcome)->toBe(SendOutcome::PerUserLimit)
        ->and($result->errorCode)->toBe('131049');
});

it('refuses to send on a RED number or without an approved template', function () {
    Http::fake();
    DB::table('whatsapp_numbers')->update(['quality_rating' => 'RED']);

    expect(send()->outcome)->toBe(SendOutcome::NotReady);

    DB::table('whatsapp_numbers')->update(['quality_rating' => 'GREEN']);
    DB::table('message_templates')->update(['status' => 'PAUSED']);

    expect(send('k2')->outcome)->toBe(SendOutcome::NotReady);
    Http::assertNothingSent();
});

it('answers the subscription challenge only for our verify token', function () {
    $this->get('/webhooks/meta?hub.mode=subscribe&hub.verify_token=testing-verify-token&hub.challenge=42')->assertOk()->assertSee('42');
    $this->get('/webhooks/meta?hub.mode=subscribe&hub.verify_token=wrong&hub.challenge=42')->assertForbidden();
});

it('rejects a webhook with a bad signature', function () {
    $this->call('POST', '/webhooks/meta', server: ['HTTP_X_HUB_SIGNATURE_256' => 'sha256=nope'], content: '{}')->assertUnauthorized();
});

it('moves statuses forward only and announces delivery once', function () {
    Event::fake([MessageDelivered::class]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]])]);
    send();

    $status = fn (string $status) => ['statuses' => [[
        'id' => 'wamid.1', 'status' => $status, 'timestamp' => (string) now()->timestamp,
        'pricing' => ['billable' => true, 'category' => 'marketing'],
    ]]];

    metaWebhook($status('delivered'))->assertOk();
    metaWebhook($status('sent'));
    metaWebhook($status('read'));
    metaWebhook($status('delivered'));

    $message = DB::table('messages')->first();

    expect($message->status)->toBe('read')
        ->and($message->pricing_category)->toBe('marketing')
        ->and($message->delivered_at)->not->toBeNull();
    Event::assertDispatchedTimes(MessageDelivered::class, 1);
});

it('treats a stop reply as opt-out before anything else, and confirms', function () {
    Event::fake([ConsentRevoked::class, InboundMessageReceived::class]);
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.confirm']]])]);

    asTeam(fn () => app(ConsentGate::class)->grant($this->customer, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));

    metaWebhook(['messages' => [['from' => '966512345678', 'id' => 'wamid.in.1', 'type' => 'text', 'text' => ['body' => 'إيقاف']]]]);

    expect(asTeam(fn () => app(ConsentGate::class)->mayMessage($this->customer)))->toBeFalse();
    Event::assertDispatched(ConsentRevoked::class);
    Event::assertDispatched(InboundMessageReceived::class, fn ($event) => $event->isOptOut);
    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/messages') && $request['type'] === 'text');
});

it('treats the template opt-out button as opt-out', function () {
    Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.confirm']]])]);
    asTeam(fn () => app(ConsentGate::class)->grant($this->customer, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));

    metaWebhook(['messages' => [['from' => '966512345678', 'id' => 'wamid.in.2', 'type' => 'button', 'button' => ['text' => 'إيقاف العروض', 'payload' => 'x']]]]);

    expect(asTeam(fn () => app(ConsentGate::class)->mayMessage($this->customer)))->toBeFalse()
        ->and(DB::table('consent_records')->latest('id')->value('source'))->toBe('template_button');
});

it('flags a not-now reply as ambiguous without revoking consent', function () {
    Event::fake([InboundMessageReceived::class]);
    asTeam(fn () => app(ConsentGate::class)->grant($this->customer, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));

    metaWebhook(['messages' => [['from' => '966512345678', 'id' => 'wamid.in.3', 'type' => 'text', 'text' => ['body' => 'مو الحين']]]]);

    expect(asTeam(fn () => app(ConsentGate::class)->mayMessage($this->customer)))->toBeTrue();
    Event::assertDispatched(InboundMessageReceived::class, fn ($event) => $event->isAmbiguous && ! $event->isOptOut);
});

it('ignores a duplicate inbound message', function () {
    $inbound = ['messages' => [['from' => '966512345678', 'id' => 'wamid.in.4', 'type' => 'text', 'text' => ['body' => 'كم السعر؟']]]];

    metaWebhook($inbound);
    $this->travel(1)->days();
    metaWebhook($inbound + ['retry' => true]);

    expect(DB::table('messages')->where('direction', 'inbound')->count())->toBe(1);
});

it('marks the number RED when Meta flags its quality', function () {
    Event::fake([NumberQualityChanged::class]);

    metaWebhook(['display_phone_number' => '966550000000', 'event' => 'FLAGGED'], 'phone_number_quality_update');

    expect(DB::table('whatsapp_numbers')->value('quality_rating'))->toBe('RED');
    Event::assertDispatched(NumberQualityChanged::class);
});

it('refuses to register a number before the data region is decided (ADR-006)', function () {
    config(['services.meta.data_localization_region' => null]);

    asTeam(fn () => app(WhatsAppOnboarding::class)->complete('code', WABA, PHONE_ID, '123456', null));
})->throws(RuntimeException::class, 'ADR-006');
