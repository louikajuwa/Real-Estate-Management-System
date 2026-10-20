<?php

namespace App\Policies;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\User;
use App\Policies\Concerns\ChecksAgency;

class PropertyPolicy
{
    use ChecksAgency;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Property $property): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClient()) {
            // Clients (platform customers) only see public listings of active agencies.
            return $property->status === PropertyStatus::Available
                && (bool) $property->agency()->where('is_active', true)->exists();
        }

        return $this->sameAgency($user, $property);
    }

    public function create(User $user): bool
    {
        return $user->can('properties.create');
    }

    public function update(User $user, Property $property): bool
    {
        return $this->manage($user, $property);
    }

    public function delete(User $user, Property $property): bool
    {
        return $this->manage($user, $property);
    }

    public function manageImages(User $user, Property $property): bool
    {
        return $this->manage($user, $property);
    }

    private function manage(User $user, Property $property): bool
    {
        if (! $this->sameAgency($user, $property)) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $this->ownsAsAgent($user, $property->agent_id);
    }
}
