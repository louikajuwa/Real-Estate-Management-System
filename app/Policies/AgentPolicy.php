<?php

namespace App\Policies;

use App\Models\Agent;
use App\Models\User;
use App\Policies\Concerns\ChecksAgency;

class AgentPolicy
{
    use ChecksAgency;

    // Clients have no business listing an agency's agents (they use chat contacts).
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $this->isStaff($user);
    }

    public function view(User $user, Agent $model): bool
    {
        return $user->isSuperAdmin() || ($this->isStaff($user) && $this->sameAgency($user, $model));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() && $user->can('agents.create');
    }

    // Admins manage agents of their agency; an agent may edit their own profile.
    public function update(User $user, Agent $model): bool
    {
        if ($user->isAdmin()) {
            return $this->sameAgency($user, $model);
        }

        return $user->isAgent() && $this->sameAgency($user, $model) && $user->agent?->id === $model->id;
    }

    public function delete(User $user, Agent $model): bool
    {
        return $user->isAdmin() && $this->sameAgency($user, $model);
    }
}
