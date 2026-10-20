<?php

namespace App\Traits;

use App\Models\Agency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToAgency
{
    protected static function bootBelongsToAgency(): void
    {
        static::addGlobalScope('agency', function (Builder $query) {
            // Scope whenever an authenticated web user belongs to an agency.
            // super_admin and client users (agency_id = null) are unscoped here and are
            // kept out of other tenants' data by policies / explicit role checks.
            // Console/seeders have no authenticated user, so they remain unscoped.
            $user = auth('web')->user();
            if ($user && $user->agency_id) {
                $query->where(
                    $query->getModel()->getTable().'.agency_id',
                    $user->agency_id
                );
            }
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
