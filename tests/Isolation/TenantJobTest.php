<?php

use App\Models\Team;
use App\Shared\Tenancy\MissingTenantContext;
use Tests\Isolation\Fixtures\CountProbesJob;
use Tests\Isolation\Fixtures\JobWithoutTenant;
use Tests\Isolation\Fixtures\TenancyProbe;

beforeEach(function () {
    TenancyProbe::createTable();
    CountProbesJob::$seen = [];
});

it('runs each job inside its own tenant only', function () {
    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();

    TenancyProbe::query()->withoutGlobalScopes()->insert([
        ['tenant_id' => $teamA->id, 'label' => 'A'],
        ['tenant_id' => $teamB->id, 'label' => 'B'],
    ]);

    CountProbesJob::dispatch($teamA->id);
    CountProbesJob::dispatch($teamB->id);

    expect(CountProbesJob::$seen)->toBe([['A'], ['B']]);
});

it('fails a tenant job that carries no tenant id', function () {
    JobWithoutTenant::dispatch();
})->throws(MissingTenantContext::class);
