<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Agent;
use App\Models\Property;
use App\Models\User;
use App\Traits\ClampsPerPage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentService
{
    use ClampsPerPage;

    public function __construct(private AuditLogger $audit) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Agent::with('user')
            ->withCount(['properties', 'clients']);

        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($outer) use ($q) {
                $outer->whereHas('user', function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                })->orWhere('license_no', 'like', "%{$q}%");
            });
        }

        $sort = in_array($filters['sort'] ?? 'created_at', ['created_at', 'id']) ? ($filters['sort'] ?? 'created_at') : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        return $query->paginate($this->clampPerPage($filters['per_page'] ?? null, $perPage));
    }

    public function create(array $data): Agent
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);
            $user->assignRole(RoleName::Agent->value);

            $agencyId = $data['agency_id'] ?? (auth()->check() ? auth()->user()->agency_id : null);
            $user->agency_id = $agencyId;
            $user->save();

            $agent = Agent::create([
                'agency_id' => $agencyId,
                'user_id' => $user->id,
                'license_no' => $data['license_no'] ?? null,
                'bio' => $data['bio'] ?? null,
                'phone' => $data['phone'] ?? null,
            ]);

            $this->audit->log('agent.created', $agent, null, $agent->toArray(), "Agent '{$user->name}' created");

            return $agent->load('user');
        });
    }

    public function update(Agent $agent, array $data): Agent
    {
        $old = $agent->toArray();
        DB::transaction(function () use ($agent, $data) {
            $userFields = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], fn ($v) => $v !== null);

            if (! empty($data['password'])) {
                $userFields['password'] = Hash::make($data['password']);
            }
            if ($userFields) {
                $agent->user->update($userFields);
            }

            $agentFields = array_filter([
                'license_no' => $data['license_no'] ?? null,
                'bio' => $data['bio'] ?? null,
                'phone' => $data['phone'] ?? null,
            ], fn ($v) => $v !== null);

            if ($agentFields) {
                $agent->update($agentFields);
            }
        });

        $this->audit->log('agent.updated', $agent, $old, $agent->fresh()->toArray(), "Agent '{$agent->user->name}' updated");

        return $agent->fresh()->load('user');
    }

    public function delete(Agent $agent): void
    {
        // Restrict if agent has active bookings
        if ($agent->bookings()->whereIn('status', ['scheduled'])->exists()) {
            throw new \RuntimeException('Cannot delete agent with active bookings.');
        }
        // properties.agent_id is restrictOnDelete; soft-deleted listings still hold the FK.
        if (Property::withoutGlobalScope('agency')->withTrashed()->where('agent_id', $agent->id)->exists()) {
            throw new \RuntimeException('Cannot delete an agent who still has properties. Reassign or delete their listings first.');
        }
        $name = $agent->user->name;
        DB::transaction(function () use ($agent) {
            $user = $agent->user;
            $agent->delete();
            $user->delete();
        });
        $this->audit->log('agent.deleted', null, ['name' => $name], null, "Agent '{$name}' deleted");
    }
}
