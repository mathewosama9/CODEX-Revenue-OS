<?php

use App\Models\Team;
use App\Models\User;
use App\Modules\Compliance\ActorType;
use App\Modules\Compliance\AuditLogger;
use App\Modules\Compliance\Models\AuditEvent;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tenants = app(TenantContext::class);
    $this->teamA = Team::factory()->create();
    $this->teamB = Team::factory()->create();
    $this->audit = app(AuditLogger::class);
});

it('records an event for the current tenant', function () {
    $user = User::factory()->create();

    $this->tenants->run($this->teamA->id, fn () => $this->audit->record(
        'discount_policy.updated',
        ActorType::User,
        $user->id,
        context: ['max_percent' => 10],
    ));

    $event = $this->tenants->run($this->teamA->id, fn () => AuditEvent::sole());

    expect($event->tenant_id)->toBe($this->teamA->id)
        ->and($event->actor_type)->toBe(ActorType::User)
        ->and($event->context)->toBe(['max_percent' => 10]);
});

it('keeps each tenant audit log separate', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->audit->record('a.happened', ActorType::System));
    $this->tenants->run($this->teamB->id, fn () => $this->audit->record('b.happened', ActorType::System));

    $actions = $this->tenants->run($this->teamA->id, fn () => AuditEvent::pluck('action')->all());

    expect($actions)->toBe(['a.happened']);
});

it('blocks updates even through raw SQL', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->audit->record('x', ActorType::System));

    DB::table('audit_events')->update(['action' => 'rewritten']);
})->throws(QueryException::class, 'append-only');

it('blocks deletes even through raw SQL', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->audit->record('x', ActorType::System));

    DB::table('audit_events')->delete();
})->throws(QueryException::class, 'append-only');

it('allows deletes only inside the retention purge', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->audit->record('x', ActorType::System));

    DB::transaction(function () {
        DB::statement("SET LOCAL codex.allow_retention_purge = 'on'");
        DB::table('audit_events')->delete();
    });

    expect(DB::table('audit_events')->count())->toBe(0);
});

it('rejects personal data in the context', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->audit->record(
        'consent.revoked',
        ActorType::System,
        context: ['customer' => ['phone' => '+966500000000']],
    ));
})->throws(InvalidArgumentException::class);
