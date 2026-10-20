<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksAgency
{
    /** Staff of an agency may only touch records owned by that same agency. */
    protected function sameAgency(User $user, mixed $model): bool
    {
        return $user->agency_id !== null && (int) $user->agency_id === (int) $model->agency_id;
    }

    protected function isStaff(User $user): bool
    {
        return $user->isAdmin() || $user->isAgent();
    }

    protected function ownsAsAgent(User $user, ?int $agentId): bool
    {
        $own = $user->agent?->id;

        return $user->isAgent() && $own !== null && $own === $agentId;
    }
}
