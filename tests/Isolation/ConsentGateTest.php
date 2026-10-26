<?php

use App\Models\Team;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentRevoked;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->tenants = app(TenantContext::class);
    $this->gate = app(ConsentGate::class);
    $this->teamA = Team::factory()->create();
    $this->teamB = Team::factory()->create();
    $this->phone = PhoneNumber::tryParse('0512345678');
    $this->evidence = ['text_version' => 'checkout-optin-v1', 'order_reference' => 'SO-1001'];
});

it('says no when there is no consent record', function () {
    $allowed = $this->tenants->run($this->teamA->id, fn () => $this->gate->mayMessage($this->phone));

    expect($allowed)->toBeFalse();
});

it('says no for an invalid number', function () {
    $allowed = $this->tenants->run($this->teamA->id, fn () => $this->gate->mayMessage(PhoneNumber::tryParse('123')));

    expect($allowed)->toBeFalse();
});

it('says yes after a documented grant', function () {
    $allowed = $this->tenants->run($this->teamA->id, function () {
        $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, $this->evidence);

        return $this->gate->mayMessage($this->phone);
    });

    expect($allowed)->toBeTrue();
});

it('refuses a grant without evidence', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, []));
})->throws(InvalidArgumentException::class);

it('says no after an opt-out and announces it', function () {
    Event::fake([ConsentRevoked::class]);

    $allowed = $this->tenants->run($this->teamA->id, function () {
        $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, $this->evidence);
        $this->gate->revoke($this->phone, ConsentSource::OptOutKeyword);

        return $this->gate->mayMessage($this->phone);
    });

    expect($allowed)->toBeFalse();
    Event::assertDispatched(ConsentRevoked::class, fn ($event) => $event->tenantId === $this->teamA->id);
});

it('does not share consent between tenants', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, $this->evidence));

    $allowedInB = $this->tenants->run($this->teamB->id, fn () => $this->gate->mayMessage($this->phone));

    expect($allowedInB)->toBeFalse();
});

it('never stores the phone number itself', function () {
    $this->tenants->run($this->teamA->id, fn () => $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, $this->evidence));

    $row = json_encode(DB::table('consent_records')->first());

    expect($row)->not->toContain('512345678');
});

it('writes every consent change to the audit log', function () {
    $this->tenants->run($this->teamA->id, function () {
        $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, $this->evidence);
        $this->gate->revoke($this->phone, ConsentSource::OptOutKeyword);
    });

    expect(DB::table('audit_events')->pluck('action')->all())->toBe(['consent.granted', 'consent.revoked']);
});

it('keeps an opt-out over a later checkout box unless the opt-in is fresh and from a new cart', function () {
    $states = $this->tenants->run($this->teamA->id, function () {
        $this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1', 'cart_id' => 'c_1']);
        $this->gate->revoke($this->phone, ConsentSource::OptOutKeyword);

        $later = now()->addMinute()->toIso8601String();

        return [
            // No proof of when the box was ticked: the stop wins.
            'undated' => [$this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1', 'cart_id' => 'c_2']), $this->gate->mayMessage($this->phone)],
            // Ticked before the stop (a late retry).
            'before' => [$this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1', 'cart_id' => 'c_2', 'captured_at' => now()->subHour()->toIso8601String()]), $this->gate->mayMessage($this->phone)],
            // The same cart again (restored or edited).
            'same_cart' => [$this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1', 'cart_id' => 'c_1', 'captured_at' => $later]), $this->gate->mayMessage($this->phone)],
            // A new checkout, ticked after the stop.
            'fresh' => [$this->gate->grant($this->phone, ConsentSource::CheckoutOptIn, ['text_version' => 'v1', 'cart_id' => 'c_2', 'captured_at' => $later]), $this->gate->mayMessage($this->phone)],
        ];
    });

    expect($states)->toBe([
        'undated' => [false, false],
        'before' => [false, false],
        'same_cart' => [false, false],
        'fresh' => [true, true],
    ]);
});
