<?php

namespace App\Policies;

use App\Models\Client;
use App\Models\User;
use App\Policies\Concerns\ChecksAgency;

class ClientPolicy
{
    use ChecksAgency;

    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin() || $this->isStaff($user);
    }

    public function view(User $user, Client $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClient()) {
            return $model->user_id !== null && $model->user_id === $user->id;
        }

        return $this->manage($user, $model);
    }

    public function create(User $user): bool
    {
        return $this->isStaff($user) && $user->can('clients.create');
    }

    public function update(User $user, Client $model): bool
    {
        return $this->manage($user, $model);
    }

    public function delete(User $user, Client $model): bool
    {
        return $this->manage($user, $model);
    }

    // Admin: any client of their agency. Agent: clients assigned to them (matches ClientService::list).
    private function manage(User $user, Client $model): bool
    {
        if (! $this->sameAgency($user, $model)) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $this->ownsAsAgent($user, $model->assigned_agent_id);
    }
}
