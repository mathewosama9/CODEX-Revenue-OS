<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Compliance\Models\ConsentImport;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->owner = User::factory()->create();
    $this->agent = User::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->team->members()->attach($this->agent, ['role' => TeamRole::Agent->value]);
    $this->url = "/api/v1/teams/{$this->team->slug}/consent/imports";
});

function consentCsv(string $contents): UploadedFile
{
    return UploadedFile::fake()->createWithContent('customers.csv', $contents);
}

function importPayload(UploadedFile $file, array $overrides = []): array
{
    return $overrides + [
        'file' => $file,
        'consent_text' => 'أوافق على استلام عروض وتذكيرات السلة عبر واتساب من المتجر.',
        'collected_via' => 'checkout',
        'attested' => true,
    ];
}

function mayMessage(string $phone): bool
{
    return app(TenantContext::class)->run(test()->team->id, fn () => app(ConsentGate::class)->mayMessage(PhoneNumber::tryParse($phone)));
}

it('imports consent from a CSV with an Arabic header, and keeps the proof (review B1)', function () {
    $csv = "\xEF\xBB\xBFالاسم,جوال\nريم,0551234567\nسارة,+966559876543\nخطأ,12345\nمكرر,0551234567\n";

    $this->actingAs($this->owner)
        ->post($this->url, importPayload(consentCsv($csv)), ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.granted', 2)
        ->assertJsonPath('data.skipped_invalid', 1)
        ->assertJsonPath('data.rows_total', 4);

    $record = DB::table('consent_records')->first();
    $evidence = json_decode($record->evidence, true);
    $import = ConsentImport::query()->withoutGlobalScopes()->first();

    expect(mayMessage('0551234567'))->toBeTrue()
        ->and(mayMessage('0559876543'))->toBeTrue()
        ->and($record->source)->toBe(ConsentSource::MerchantImport->value)
        ->and($evidence['import'])->toBe($import->reference)
        ->and($evidence['attested_by'])->toBe($this->owner->id)
        // Proof without PII: no phone in the evidence, the file is kept only as a hash.
        ->and(json_encode($evidence))->not->toContain('551234567')
        ->and($import->file_sha256)->toHaveLength(64)
        ->and($import->consent_text)->toContain('واتساب');

    // The go-live checklist now has a consent source.
    $this->actingAs($this->owner)
        ->getJson("/api/v1/teams/{$this->team->slug}/recovery")
        ->assertJsonPath('data.go_live_checklist.consent_source_ready', true);
});

it('never re-grants a customer who opted out, and counts existing grants', function () {
    app(TenantContext::class)->run($this->team->id, function () {
        $gate = app(ConsentGate::class);
        $gate->grant(PhoneNumber::tryParse('0551234567'), ConsentSource::CheckoutOptIn, ['text_version' => 'v1']);
        $gate->revoke(PhoneNumber::tryParse('0551234567'), ConsentSource::OptOutKeyword);
        $gate->grant(PhoneNumber::tryParse('0559876543'), ConsentSource::CheckoutOptIn, ['text_version' => 'v1']);
    });

    $this->actingAs($this->owner)
        ->post($this->url, importPayload(consentCsv("0551234567\n0559876543\n0551112222\n")), ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.skipped_opted_out', 1)
        ->assertJsonPath('data.already_granted', 1)
        ->assertJsonPath('data.granted', 1);

    expect(mayMessage('0551234567'))->toBeFalse();
});

it('requires the attestation and the consent wording', function () {
    $this->actingAs($this->owner)
        ->post($this->url, importPayload(consentCsv("0551234567\n"), ['attested' => false, 'consent_text' => '']), ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['attested', 'consent_text']);

    expect(DB::table('consent_records')->count())->toBe(0);
});

it('lets only the owner import consent', function () {
    $this->actingAs($this->agent)
        ->post($this->url, importPayload(consentCsv("0551234567\n")), ['Accept' => 'application/json'])
        ->assertForbidden();
});

it('keeps the import record append-only', function () {
    $this->actingAs($this->owner)->post($this->url, importPayload(consentCsv("0551234567\n")), ['Accept' => 'application/json'])->assertCreated();

    expect(fn () => DB::table('consent_imports')->update(['granted' => 999]))->toThrow(Exception::class);
});
