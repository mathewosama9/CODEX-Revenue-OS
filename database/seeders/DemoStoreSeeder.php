<?php

namespace Database\Seeders;

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use App\Modules\Billing\UsageMeter;
use App\Modules\Compliance\Consent\ConsentGate;
use App\Modules\Compliance\Consent\ConsentSource;
use App\Modules\Measurement\ExperimentAssigner;
use App\Shared\Phone\PhoneNumber;
use App\Shared\Phone\SubjectHasher;
use App\Shared\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * A fake store for local demos and the web E2E suite (web/e2e). Never run it
 * against production: the WhatsApp and Salla rows point at nothing real.
 *
 *   php artisan db:seed --class=DemoStoreSeeder
 *
 * Owner: owner@demo.test / password. Agent: agent@demo.test / password.
 * Everything on the go-live checklist is green except the margins, so the
 * onboarding journey can finish it.
 */
class DemoStoreSeeder extends Seeder
{
    public function run(TenantContext $tenants, SubjectHasher $hasher): void
    {
        $owner = User::factory()->create(['name' => 'مالك المتجر', 'email' => 'owner@demo.test']);
        $agent = User::factory()->create(['name' => 'موظف خدمة العملاء', 'email' => 'agent@demo.test']);

        // The factory gives each user a personal team; the demo has one store only.
        Team::query()->where('is_personal', true)->whereIn('id', [$owner->current_team_id, $agent->current_team_id])->delete();

        $team = Team::factory()->create(['name' => 'عطور الريم']);
        $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
        $team->members()->attach($agent, ['role' => TeamRole::Agent->value]);
        $owner->update(['current_team_id' => $team->id]);
        $agent->update(['current_team_id' => $team->id]);

        $t = $team->id;
        $now = now();

        DB::table('store_connections')->insert(['tenant_id' => $t, 'provider' => 'salla', 'external_store_id' => 'demo', 'store_name' => 'عطور الريم', 'status' => 'active', 'access_token' => encrypt('demo', false), 'created_at' => $now, 'updated_at' => $now]);
        $account = DB::table('whatsapp_accounts')->insertGetId(['tenant_id' => $t, 'waba_id' => 'demo-waba', 'access_token' => encrypt('demo', false), 'status' => 'active']);
        $number = DB::table('whatsapp_numbers')->insertGetId(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'phone_number_id' => 'demo-pn', 'display_phone_number' => '+966 50 000 0000', 'quality_rating' => 'GREEN', 'status' => 'active']);

        foreach (['cart_recovery_1' => 'cart_recovery_1', 'cart_recovery_2' => 'cart_recovery_2', 'cart_recovery_2_discount' => 'cart_recovery_2_discount'] as $purpose => $name) {
            DB::table('message_templates')->insert(['tenant_id' => $t, 'whatsapp_account_id' => $account, 'name' => $name, 'language' => 'ar', 'category' => 'MARKETING', 'status' => 'APPROVED', 'purpose' => $purpose, 'components' => '[]']);
        }

        foreach (['oud' => 'عود', 'musk' => 'مسك', 'gifts' => 'هدايا'] as $id => $name) {
            DB::table('categories')->insert(['tenant_id' => $t, 'external_id' => $id, 'name' => $name, 'created_at' => $now, 'updated_at' => $now]);
        }

        DB::table('recovery_settings')->insert(['tenant_id' => $t, 'enabled' => false, 'discount_enabled' => true, 'discount_percent' => 10, 'discount_code' => 'BACK10', 'monthly_discount_budget_halalas' => 100_000, 'created_at' => $now, 'updated_at' => $now]);

        $tenants->run($t, function () use ($t, $hasher, $number, $now) {
            app(ExperimentAssigner::class)->experiment();

            $phone = PhoneNumber::tryParse('0551234567');
            assert($phone !== null);
            $hash = $hasher->hash($t, $phone);

            app(ConsentGate::class)->grant($phone, ConsentSource::CheckoutOptIn, ['text_version' => 'demo']);
            // Seeders stay outside the modules (tests/Arch), so plain rows; the phone is encrypted like Customer::$casts.
            $customer = DB::table('customers')->insertGetId(['tenant_id' => $t, 'external_id' => 'demo-c1', 'phone_hash' => $hash, 'phone' => encrypt((string) $phone, false), 'created_at' => $now, 'updated_at' => $now]);

            // A discount waiting for the owner (an existing customer, US-E05-04).
            $cart = DB::table('carts')->insertGetId(['tenant_id' => $t, 'external_id' => 'demo-cart-1', 'customer_id' => $customer, 'phone_hash' => $hash, 'total_halalas' => 42_000, 'item_count' => 1, 'status' => 'abandoned', 'abandoned_at' => $now->copy()->subDay(), 'discovered_by' => 'webhook', 'created_at' => $now, 'updated_at' => $now]);
            $sequence = DB::table('recovery_sequences')->insertGetId(['tenant_id' => $t, 'cart_id' => $cart, 'subject_hash' => $hash, 'arm' => 'treatment', 'status' => 'active', 'started_at' => $now->copy()->subDay(), 'created_at' => $now, 'updated_at' => $now]);
            $action = DB::table('scheduled_actions')->insertGetId(['tenant_id' => $t, 'recovery_sequence_id' => $sequence, 'step' => 2, 'status' => 'awaiting_approval', 'run_at' => $now->copy()->addHours(4), 'idempotency_key' => 'demo-cart-1:2', 'result' => '{}', 'created_at' => $now, 'updated_at' => $now]);
            DB::table('discount_approvals')->insert(['tenant_id' => $t, 'scheduled_action_id' => $action, 'percent' => 15, 'reason' => 'existing_customer', 'status' => 'pending', 'expires_at' => $now->copy()->addHours(4), 'created_at' => $now, 'updated_at' => $now]);

            // The customer answered the first message: a conversation for the inbox.
            // Bodies are encrypted at rest, like Message::$casts does.
            DB::table('messages')->insert([
                ['tenant_id' => $t, 'whatsapp_number_id' => $number, 'direction' => 'outbound', 'subject_hash' => $hash, 'template_name' => 'cart_recovery_1', 'status' => 'delivered', 'body' => encrypt('مرحبًا! سلتك في عطور الريم بانتظارك.', false), 'created_at' => $now->copy()->subHours(3), 'updated_at' => $now],
                ['tenant_id' => $t, 'whatsapp_number_id' => $number, 'direction' => 'inbound', 'subject_hash' => $hash, 'template_name' => null, 'status' => 'delivered', 'body' => encrypt('هل يوجد توصيل لجدة اليوم؟', false), 'created_at' => $now->copy()->subHours(2), 'updated_at' => $now],
            ]);
            DB::table('conversations')->insert(['tenant_id' => $t, 'subject_hash' => $hash, 'status' => 'open', 'reason' => 'customer_message', 'last_message_at' => $now->copy()->subHours(2), 'created_at' => $now, 'updated_at' => $now]);

            $usage = app(UsageMeter::class);
            $usage->increment(UsageMeter::CARTS_ABANDONED, 37);
            $usage->increment(UsageMeter::ELIGIBLE_CARTS, 21);
            $usage->increment(UsageMeter::MESSAGES_DELIVERED.':marketing', 34);
        });
    }
}
