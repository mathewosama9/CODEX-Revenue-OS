<?php

namespace App\Shared\Tenancy;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Required on every tenant-owned model (enforced by tests/Arch).
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $tenantId = app(TenantContext::class)->require();
            $given = $model->getAttribute('tenant_id');

            if ($given === null) {
                $model->setAttribute('tenant_id', $tenantId);
            } elseif ((int) $given !== $tenantId) {
                throw new TenantMismatch;
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id')) {
                throw new TenantMismatch;
            }
        });
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'tenant_id');
    }
}
