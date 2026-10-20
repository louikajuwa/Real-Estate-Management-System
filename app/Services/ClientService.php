<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientInteraction;
use App\Traits\ClampsPerPage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    use ClampsPerPage;

    public function __construct(private AuditLogger $audit) {}

    public function list(array $filters, $user, int $perPage = 15): LengthAwarePaginator
    {
        $query = Client::with(['assignedAgent.user'])
            ->withCount(['inquiries', 'bookings']);

        if ($user->isAgent()) {
            $query->where('assigned_agent_id', $user->agent?->id);
        }

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }
        if (! empty($filters['agent_id'])) {
            $query->where('assigned_agent_id', $filters['agent_id']);
        }

        $sort = in_array($filters['sort'] ?? 'created_at', ['name', 'email', 'created_at']) ? ($filters['sort'] ?? 'created_at') : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        return $query->paginate($this->clampPerPage($filters['per_page'] ?? null, $perPage));
    }

    public function create(array $data): Client
    {
        // A client created by agency staff belongs to that staff member's agency.
        if (empty($data['agency_id']) && auth()->check() && auth()->user()->agency_id) {
            $data['agency_id'] = auth()->user()->agency_id;
        }
        $client = Client::create($data);
        $this->audit->log('client.created', $client, null, $client->toArray(), "Client '{$client->name}' created");

        return $client->load('assignedAgent.user');
    }

    public function update(Client $client, array $data): Client
    {
        $old = $client->toArray();
        $client->update($data);
        $this->audit->log('client.updated', $client, $old, $client->fresh()->toArray(), "Client '{$client->name}' updated");

        return $client->fresh()->load('assignedAgent.user');
    }

    public function delete(Client $client): void
    {
        if ($client->bookings()->whereIn('status', ['scheduled'])->exists()) {
            throw new \RuntimeException('Cannot delete client with active bookings.');
        }
        $name = $client->name;
        $client->delete();
        $this->audit->log('client.deleted', null, ['name' => $name], null, "Client '{$name}' deleted");
    }

    public function interactions(Client $client): Collection
    {
        return $client->interactions()
            ->with('agent.user')
            ->orderBy('occurred_at', 'desc')
            ->get();
    }

    public function addInteraction(Client $client, array $data): ClientInteraction
    {
        $interaction = $client->interactions()->create($data);
        $this->audit->log('client.interaction_added', $client, null, $interaction->toArray(), "Interaction '{$interaction->type->value}' added for client '{$client->name}'");

        return $interaction->load('agent.user');
    }
}
