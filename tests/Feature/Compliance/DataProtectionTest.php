<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Compliance\Jobs\PurgeExpiredData;
use App\Shared\Logging\Redactor;
use App\Shared\Logging\RedactPersonalData;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Webhooks\WebhookEvent;
use App\Shared\Webhooks\WebhookStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create();
    $this->agent = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);
    $this->phone = PhoneNumber::tryParse('0512345678');
    $this->hash = app(SubjectHasher::class)->hash($this->team->id, $this->phone);

    DB::table('customers')->insert(['tenant_id' => $this->team->id, 'external_id' => 'c1', 'phone_hash' => $this->hash, 'phone' => encrypt('+966512345678', false)]);
    DB::table('messages')->insert(['tenant_id' => $this->team->id, 'direction' => 'inbound', 'subject_hash' => $this->hash, 'status' => 'received', 'body' => encrypt('عنواني حي النرجس', false), 'context' => '{}', 'wamid' => 'w1', 'created_at' => now()->subDays(100)]);
    app(TenantContext::class)->run($this->team->id, fn () => app(ConsentGate::class)->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1']));
});

it('redacts phones, emails and secrets before logging', function () {
    expect(Redactor::text('call +966 51 234 5678 or ٠٥١٢٣٤٥٦٧٨, mail a.b@store.sa'))->toBe('call [phone] or [phone], mail [email]')
        ->and(Redactor::context(['order_id' => 77, 'access_token' => 'abc', 'note' => 'رقمه 0512345678']))
        ->toBe(['order_id' => 77, 'access_token' => '[redacted]', 'note' => 'رقمه [phone]']);
});

it('applies the redaction to the real log channel', function () {
    $file = storage_path('logs/redaction-test.log');
    @unlink($file);
    config(['logging.channels.redaction_test' => ['driver' => 'single', 'path' => $file, 'tap' => [RedactPersonalData::class]]]);

    Log::channel('redaction_test')->warning('customer 0512345678 complained', ['email' => 'x@y.sa']);

    expect(file_get_contents($file))->not->toContain('0512345678')->not->toContain('x@y.sa');
    unlink($file);
});

it('exports what we hold about a customer', function () {
    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/data-requests", ['phone' => '0512345678', 'type' => 'export'])
        ->assertOk()
        ->assertJsonPath('data.customers.0.phone', '+966512345678')
        ->assertJsonPath('data.messages.0.body', 'عنواني حي النرجس')
        ->assertJsonPath('data.marketing_consent', 'granted');
});

it('deletes a customer, revokes consent, and keeps the proof', function () {
    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/data-requests", ['phone' => '+966512345678', 'type' => 'delete'])
        ->assertOk();

    expect(DB::table('customers')->value('phone'))->toBeNull()
        ->and(DB::table('messages')->value('body'))->toBeNull()
        ->and(app(TenantContext::class)->run($this->team->id, fn () => app(ConsentGate::class)->mayMessage($this->phone)))->toBeFalse()
        ->and(DB::table('audit_events')->where('action', 'data_request.deleted')->exists())->toBeTrue();
});

it('lets only the owner handle data requests', function () {
    $this->actingAs($this->agent)
        ->postJson("/api/v1/teams/{$this->team->slug}/data-requests", ['phone' => '0512345678', 'type' => 'export'])
        ->assertForbidden();
});

it('drops message text past the retention window but keeps the record', function () {
    PurgeExpiredData::dispatch($this->team->id);

    $message = DB::table('messages')->first();
    expect($message->body)->toBeNull()->and($message->status)->toBe('received');
});

it('scrubs the customer from stored webhook payloads on deletion, keeping the dedupe row (review H5)', function () {
    $other = Team::factory()->create();
    $mine = WebhookEvent::query()->create(['provider' => 'salla', 'external_event_id' => 'e1', 'event_type' => 'abandoned.cart', 'tenant_id' => $this->team->id, 'payload' => ['data' => ['customer' => ['mobile' => '512345678', 'mobile_code' => '+966', 'name' => 'ريم']]], 'status' => 'failed']);
    $unrelated = WebhookEvent::query()->create(['provider' => 'salla', 'external_event_id' => 'e2', 'event_type' => 'abandoned.cart', 'tenant_id' => $this->team->id, 'payload' => ['data' => ['customer' => ['mobile' => '598765432']]], 'status' => 'processed']);
    $foreign = WebhookEvent::query()->create(['provider' => 'salla', 'external_event_id' => 'e3', 'event_type' => 'abandoned.cart', 'tenant_id' => $other->id, 'payload' => ['data' => ['customer' => ['mobile' => '512345678']]], 'status' => 'processed']);

    $this->actingAs($this->owner)
        ->postJson("/api/v1/teams/{$this->team->slug}/data-requests", ['phone' => '+966512345678', 'type' => 'delete'])
        ->assertOk()
        ->assertJsonPath('data.webhook_payloads', 1);

    expect($mine->fresh()->payload)->toBe(['redacted' => 'data_subject_request'])
        ->and($mine->fresh()->status)->toBe(WebhookStatus::Processed)
        ->and($unrelated->fresh()->payload['data']['customer']['mobile'])->toBe('598765432')
        // Another merchant's customer with the same number is not this request.
        ->and($foreign->fresh()->payload['data']['customer']['mobile'])->toBe('512345678');
});
