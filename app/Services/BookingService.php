<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Exceptions\DoubleBookingException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Property;
use App\Traits\ClampsPerPage;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class BookingService
{
    use ClampsPerPage;

    public function __construct(private AuditLogger $audit) {}

    public function list(array $filters, $user): LengthAwarePaginator
    {
        $query = Booking::with(['property', 'client', 'agent.user'])
            ->orderBy('scheduled_at', 'desc');

        if ($user->isAgent()) {
            $query->where('agent_id', $user->agent?->id);
        } elseif ($user->isClient()) {
            $query->where('client_id', $user->client?->id);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['date_from'])) {
            $query->whereDate('scheduled_at', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->whereDate('scheduled_at', '<=', $filters['date_to']);
        }
        if (! empty($filters['property_id'])) {
            $query->where('property_id', $filters['property_id']);
        }

        return $query->paginate($this->clampPerPage($filters['per_page'] ?? null, 20));
    }

    public function schedule(array $data, $user): Booking
    {
        if ($user->isAgent()) {
            $data['agent_id'] = $user->agent?->id;
        }
        if ($user->isClient()) {
            // Client-role users always book as themselves; never trust a supplied client_id.
            $data['client_id'] = $user->client()->firstOrCreate([], [
                'name' => $user->name,
                'email' => $user->email,
            ])->id;
        }
        $data['status'] = BookingStatus::Scheduled->value;
        $data['duration_min'] = (int) ($data['duration_min'] ?? 60);

        try {
            return DB::transaction(function () use ($data) {
                $this->assertSlotFree(
                    (int) $data['property_id'],
                    Carbon::parse($data['scheduled_at']),
                    $data['duration_min']
                );

                // Derive agency_id and the listing agent from the property being booked.
                // Without this, client-created bookings have agent_id = null and never
                // appear in the listing agent's scoped booking list.
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

                $booking = Booking::create($data);
                $this->audit->log(
                    'booking.requested',
                    $booking,
                    null,
                    $booking->toArray(),
                    "Booking #{$booking->id} requested for property #{$booking->property_id}",
                    $booking->client?->user_id
                );

                return $booking->load(['property', 'client', 'agent.user']);
            });
        } catch (UniqueConstraintViolationException) {
            // A concurrent request grabbed the same slot between our check and insert.
            throw new DoubleBookingException;
        }
    }

    public function updateStatus(Booking $booking, string $newStatus, $actor): Booking
    {
        $target = BookingStatus::from($newStatus);

        return DB::transaction(function () use ($booking, $target, $newStatus) {
            // Lock the row so two concurrent status changes cannot both pass the checks.
            $current = Booking::lockForUpdate()->findOrFail($booking->id);
            $old = $current->status;

            if (! $old->canTransitionTo($target)) {
                throw new InvalidStatusTransitionException($old->value, $target->value, 'booking');
            }

            // Moving back to an active status re-claims the slot: it must still be free.
            if ($target->isActive()) {
                $this->assertSlotFree($current->property_id, $current->scheduled_at, $current->duration_min, $current->id);
            }

            $current->update(['status' => $newStatus]);
            $this->audit->log(
                'booking.status_changed',
                $current,
                ['status' => $old->value],
                ['status' => $newStatus],
                "Booking #{$current->id} status changed from {$old->value} to {$newStatus}",
                $current->client?->user_id
            );

            return $current->fresh()->load(['property', 'client', 'agent.user']);
        });
    }

    public function delete(Booking $booking): void
    {
        $id = $booking->id;
        $booking->delete();
        $this->audit->log('booking.deleted', null, ['id' => $id], null, "Booking #{$id} deleted");
    }

    /** Throws if any other Scheduled booking on the property overlaps [start, start + duration). */
    private function assertSlotFree(int $propertyId, Carbon $start, int $durationMin, ?int $ignoreId = null): void
    {
        $end = $start->copy()->addMinutes($durationMin);

        $existing = Booking::withoutGlobalScope('agency')
            ->where('property_id', $propertyId)
            ->where('status', BookingStatus::Scheduled->value)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->lockForUpdate()
            ->get();

        foreach ($existing as $b) {
            $bStart = Carbon::parse($b->scheduled_at);
            $bEnd = $bStart->copy()->addMinutes($b->duration_min);
            if ($start->lt($bEnd) && $end->gt($bStart)) {
                throw new DoubleBookingException(
                    'This slot overlaps an existing booking at '.$bStart->format('Y-m-d H:i').'.'
                );
            }
        }
    }
}
