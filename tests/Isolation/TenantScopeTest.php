<?php

use App\Models\Team;
use App\Shared\Tenancy\MissingTenantContext;
use App\Shared\Tenancy\TenantContext;
use App\Shared\Tenancy\TenantMismatch;
use Tests\Isolation\Fixtures\TenancyProbe;

beforeEach(function () {
    TenancyProbe::createTable();

    $this->tenants = app(TenantContext::class);
    $this->teamA = Team::factory()->create();
    $this->teamB = Team::factory()->create();

    $this->tenants->run($this->teamA->id, fn () => TenancyProbe::create(['label' => 'A']));
    $this->tenants->run($this->teamB->id, fn () => TenancyProbe::create(['label' => 'B']));
});

it('only returns the current tenant rows', function () {
    $labels = $this->tenants->run($this->teamA->id, fn () => TenancyProbe::pluck('label')->all());

    expect($labels)->toBe(['A']);
});

it('cannot find another tenant row by id', function () {
    $rowB = $this->tenants->run($this->teamB->id, fn () => TenancyProbe::first());

    $found = $this->tenants->run($this->teamA->id, fn () => TenancyProbe::find($rowB->id));

    expect($found)->toBeNull();
});

it('fails closed when no tenant is set', function () {
    TenancyProbe::count();
})->throws(MissingTenantContext::class);

it('refuses to create a row without a tenant', function () {
    TenancyProbe::create(['label' => 'orphan']);
})->throws(MissingTenantContext::class);

it('fills tenant_id from the context on create', function () {
    $row = $this->tenants->run($this->teamA->id, fn () => TenancyProbe::create(['label' => 'A2']));

    expect($row->tenant_id)->toBe($this->teamA->id);
});

it('refuses to create a row for another tenant', function () {
    $this->tenants->run($this->teamA->id, fn () => TenancyProbe::create([
        'label' => 'sneaky',
        'tenant_id' => $this->teamB->id,
    ]));
})->throws(TenantMismatch::class);

it('refuses to move a row to another tenant', function () {
    $this->tenants->run($this->teamA->id, function () {
        $row = TenancyProbe::first();
        $row->tenant_id = $this->teamB->id;
        $row->save();
    });
})->throws(TenantMismatch::class);

it('does not update another tenant rows through a mass update', function () {
    $this->tenants->run($this->teamA->id, fn () => TenancyProbe::query()->update(['label' => 'changed']));

    $labelB = $this->tenants->run($this->teamB->id, fn () => TenancyProbe::value('label'));

    expect($labelB)->toBe('B');
});

it('restores the previous tenant after run()', function () {
    $this->tenants->set($this->teamA->id);

    $this->tenants->run($this->teamB->id, fn () => null);

    expect($this->tenants->id())->toBe($this->teamA->id);
});
