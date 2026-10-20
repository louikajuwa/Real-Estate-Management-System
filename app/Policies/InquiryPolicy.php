<?php

namespace App\Policies;

use App\Models\Inquiry;
use App\Models\User;
use App\Policies\Concerns\ChecksAgency;

class InquiryPolicy
{
    use ChecksAgency;

    // Listing is narrowed per role in the service layer.
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Inquiry $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }
        if ($user->isClient()) {
            return $this->ownsAsClient($user, $model);
        }

        return $this->manage($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->can('inquiries.create');
    }

    public function update(User $user, Inquiry $model): bool
    {
        return $this->manage($user, $model);
    }

    public function delete(User $user, Inquiry $model): bool
    {
        return $user->isAdmin() && $this->sameAgency($user, $model);
    }

    // Status changes: staff as per update(); a client may act on their own record
    // (the controller further restricts clients to cancelling).
    public function updateStatus(User $user, Inquiry $model): bool
    {
        return $user->isClient() ? $this->ownsAsClient($user, $model) : $this->manage($user, $model);
    }

    // Admin: any record of their agency. Agent: records assigned to them.
    private function manage(User $user, Inquiry $model): bool
    {
        if (! $this->sameAgency($user, $model)) {
            return false;
        }
        if ($user->isAdmin()) {
            return true;
        }

        return $this->ownsAsAgent($user, $model->agent_id);
    }

    private function ownsAsClient(User $user, Inquiry $model): bool
    {
        $own = $user->client?->id;

        return $own !== null && $own === $model->client_id;
    }
}
