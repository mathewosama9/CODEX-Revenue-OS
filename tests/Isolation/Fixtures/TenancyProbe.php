<?php

namespace Tests\Isolation\Fixtures;

use App\Shared\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A minimal tenant-owned model, so the isolation suite exercises the tenancy
 * layer itself before the first real module tables exist.
 */
class TenancyProbe extends Model
{
    use BelongsToTenant;

    protected $guarded = [];

    public static function createTable(): void
    {
        Schema::create('tenancy_probes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('teams');
            $table->string('label');
            $table->timestamps();

            $table->index(['tenant_id', 'id']);
        });
    }
}
