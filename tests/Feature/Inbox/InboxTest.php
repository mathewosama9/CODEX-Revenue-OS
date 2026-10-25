<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Inbox\Notifications\CustomerNeedsHuman;
use App\Modules\Messaging\Events\InboundMessageReceived;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create();
    $this->agent = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);
    $this->hash = app(SubjectHasher::class)->hash($this->team->id, PhoneNumber::tryParse('0512345678'));

    DB::table('customers')->insert(['tenant_id' => $this->team->id, 'external_id' => 'c1', 'phone_hash' => $this->hash, 'phone' => encrypt('+966512345678', false)]);
    DB::table('messages')->insert(['tenant_id' => $this->team->id, 'direction' => 'inbound', 'subject_hash' => $this->hash, 'status' => 'received', 'body' => encrypt('عندكم عود كمبودي؟', false), 'context' => '{}', 'wamid' => 'w1', 'created_at' => now()]);
});

function customerReplied(bool $ambiguous = false, bool $optOut = false): void
{
    InboundMessageReceived::dispatch(test()->team->id, 1, test()->hash, $optOut, $ambiguous);
}

it('opens a conversation and tells the team once', function () {
    customerReplied();
    customerReplied();

    expect(DB::table('conversations')->count())->toBe(1);
    Notification::assertSentTimes(CustomerNeedsHuman::class, 2); // owner + agent, once each
});

it('does not open a conversation for an opt-out', function () {
    customerReplied(optOut: true);

    expect(DB::table('conversations')->count())->toBe(0);
});

it('lets an agent read the queue with a masked phone and the messages', function () {
    customerReplied(ambiguous: true);
    $id = DB::table('conversations')->value('id');
    $base = "/api/v1/teams/{$this->team->slug}/inbox";

    $this->actingAs($this->agent)->getJson($base)
        ->assertOk()
        ->assertJsonPath('data.0.reason', 'not_now')
        ->assertJsonPath('data.0.phone', '+9665•••••678');

    $this->actingAs($this->agent)->getJson("{$base}/{$id}")
        ->assertOk()
        ->assertJsonPath('data.messages.0.body', 'عندكم عود كمبودي؟');
});

it('assigns, notes and closes, only to team members', function () {
    customerReplied();
    $id = DB::table('conversations')->value('id');
    $base = "/api/v1/teams/{$this->team->slug}/inbox/{$id}";
    $stranger = User::factory()->create();

    $this->actingAs($this->owner)->patchJson($base, ['assigned_user_id' => $stranger->id])->assertUnprocessable();
    $this->actingAs($this->owner)->patchJson($base, ['assigned_user_id' => $this->agent->id, 'status' => 'pending'])->assertOk();
    $this->actingAs($this->agent)->postJson("{$base}/notes", ['body' => 'كلمته وطلب يرجع بكرة'])->assertOk()
        ->assertJsonPath('data.notes.0.body', 'كلمته وطلب يرجع بكرة');
    $this->actingAs($this->agent)->patchJson($base, ['status' => 'closed'])->assertOk()->assertJsonPath('data.status', 'closed');
});

it('keeps another store conversations out of reach', function () {
    customerReplied();
    $id = DB::table('conversations')->value('id');

    $otherTeam = Team::factory()->create();
    $otherOwner = User::factory()->create();
    $otherTeam->members()->attach($otherOwner, ['role' => TeamRole::Owner->value]);

    $this->actingAs($otherOwner)->getJson("/api/v1/teams/{$otherTeam->slug}/inbox/{$id}")->assertNotFound();
});

it('pages the queue newest first with a cursor, masking phones in one query (review L1)', function () {
    $rows = [];

    foreach (range(1, 60) as $i) {
        $rows[] = ['tenant_id' => $this->team->id, 'subject_hash' => hash('sha256', "c{$i}"), 'status' => 'open', 'reason' => 'customer_message', 'last_message_at' => now()->subMinutes($i), 'created_at' => now(), 'updated_at' => now()];
    }

    DB::table('conversations')->insert($rows);
    $url = "/api/v1/teams/{$this->team->slug}/inbox";

    DB::enableQueryLog();
    $first = $this->actingAs($this->agent)->getJson($url)->assertOk();
    $customerQueries = collect(DB::getQueryLog())->filter(fn ($query) => str_contains($query['query'], 'from "customers"'))->count();

    $second = $this->actingAs($this->agent)->getJson($url.'?cursor='.$first->json('meta.next_cursor'))->assertOk();

    expect($first->json('data'))->toHaveCount(50)
        ->and($customerQueries)->toBe(1)
        ->and($second->json('data'))->toHaveCount(10)
        ->and($second->json('meta.next_cursor'))->toBeNull()
        ->and(array_intersect(array_column($first->json('data'), 'id'), array_column($second->json('data'), 'id')))->toBe([]);

    $this->actingAs($this->agent)->getJson("{$url}?status=everything")->assertUnprocessable();
});
