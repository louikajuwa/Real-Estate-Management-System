<?php

namespace App\Services;

use App\Enums\InquiryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use App\Traits\ClampsPerPage;
use Illuminate\Pagination\LengthAwarePaginator;

class InquiryService
{
    use ClampsPerPage;

    public function __construct(private AuditLogger $audit) {}

    public function list(array $filters, $user): LengthAwarePaginator
    {
        $query = Inquiry::with(['property', 'client', 'agent.user'])
            ->orderBy('created_at', 'desc');

        if ($user->isAgent()) {
            $query->where('agent_id', $user->agent?->id);
        } elseif ($user->isClient()) {
            $query->where('client_id', $user->client?->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('message', 'like', "%{$q}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('property', fn ($p) => $p->where('title', 'like', "%{$q}%"));
            });
        }

        return $query->paginate($this->clampPerPage($filters['per_page'] ?? null, 100, 200));
    }

    public function create(array $data, $user): Inquiry
    {
        if ($user->isAgent()) {
            $data['agent_id'] = $user->agent?->id;
        }
        if ($user->isClient()) {
            // Client-role users always act as themselves; never trust a supplied client_id.
            $data['client_id'] = $user->client()->firstOrCreate([], [
                'name' => $user->name,
                'email' => $user->email,
            ])->id;
        }
        $data['status'] = InquiryStatus::New->value;

        // Derive agency_id and the listing agent from the property being inquired on.
        // Without this, client-submitted inquiries have agent_id = null and never
        // reach the listing agent's scoped pipeline (nor fire their notification).
        if (! empty($data['property_id']) && (empty($data['agency_id']) || empty($data['agent_id']))) {
            $property = Property::withoutGlobalScope('agency')->find($data['property_id']);
            if ($property) {
                if (empty($data['agency_id'])) {
                    $data['agency_id'] = $property->agency_id;
                }
                if (empty($data['agent_id'])) {
                    $data['agent_id'] = $property->agent_id;
                }
            }
        }

        // A platform client (no agency yet) becomes this agency's customer on
        // first engagement, so the agency's staff can see and manage them.
        if (! empty($data['client_id']) && ! empty($data['agency_id'])) {
            $client = Client::withoutGlobalScope('agency')->find($data['client_id']);
            if ($client && empty($client->agency_id)) {
                $client->update(['agency_id' => $data['agency_id']]);
            }
        }

        $inquiry = Inquiry::create($data);
        $this->audit->log('inquiry.submitted', $inquiry, null, $inquiry->toArray(),
            "Inquiry submitted for property #{$inquiry->property_id}",
            $inquiry->agent_id ? $inquiry->agent->user?->id : null);

        return $inquiry->load(['property', 'client', 'agent.user']);
    }

    public function transition(Inquiry $inquiry, string $newStatus, $actor): Inquiry
    {
        $current = $inquiry->status;
        $target = InquiryStatus::from($newStatus);

        if (! $current->canTransitionTo($target)) {
            throw new InvalidStatusTransitionException($current->value, $target->value);
        }

        $old = $inquiry->status->value;
        $inquiry->update(['status' => $newStatus]);
        $this->audit->log('inquiry.status_changed', $inquiry,
            ['status' => $old], ['status' => $newStatus],
            "Inquiry #{$inquiry->id} status changed from {$old} to {$newStatus}",
            $inquiry->client?->user_id);

        return $inquiry->fresh();
    }

    public function delete(Inquiry $inquiry): void
    {
        $id = $inquiry->id;
        $inquiry->delete();
        $this->audit->log('inquiry.deleted', null, ['id' => $id], null, "Inquiry #{$id} deleted");
    }
}
