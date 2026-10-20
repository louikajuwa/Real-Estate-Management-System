<?php

namespace App\Services;

use App\Enums\RoleName;
use App\Models\Booking;
use App\Models\ChatMessage;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ChatService
{
    /**
     * Ids of the active users $user is allowed to chat with.
     *
     * - agency staff: colleagues of the same agency, super admins (admins only), and
     *   clients related to the agency (client record, inquiry or booking).
     * - clients: staff of every agency they have a client record / inquiry / booking with.
     * - super admin: agency admins.
     *
     * Queries bypass the BelongsToAgency scope on purpose: the boundaries are explicit here.
     *
     * @return list<int>
     */
    public function contactIds(User $user): array
    {
        $ids = match (true) {
            $user->isSuperAdmin() => $this->userIdsWithRole([RoleName::Admin->value]),
            $user->isClient() => $this->staffIdsForClient($user),
            default => $this->idsForStaff($user),
        };

        return collect($ids)->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $user->id)->unique()->values()->all();
    }

    public function contacts(User $user)
    {
        return User::whereIn('id', $this->contactIds($user))
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    public function canMessage(User $sender, int $receiverId): bool
    {
        if (! in_array($receiverId, $this->contactIds($sender), true)) {
            return false;
        }

        return (bool) User::where('id', $receiverId)->where('is_active', true)->exists();
    }

    public function send(int $senderId, int $receiverId, string $message): ChatMessage
    {
        $chatMessage = ChatMessage::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => $message,
        ]);

        try {
            Redis::publish('chat', json_encode([
                'id' => $chatMessage->id,
                'senderId' => $senderId,
                'receiverId' => $receiverId,
                'message' => $message,
                'created_at' => $chatMessage->created_at?->toISOString(),
            ]));
        } catch (\Throwable $e) {
            Log::warning('Redis chat publish failed', ['error' => $e->getMessage()]);
        }

        return $chatMessage;
    }

    private function idsForStaff(User $user): array
    {
        $agencyId = $user->agency_id;
        if (! $agencyId) {
            return [];
        }

        $colleagues = User::where('agency_id', $agencyId)
            ->role([RoleName::Admin->value, RoleName::Agent->value])
            ->pluck('id');

        $directClients = Client::withoutGlobalScope('agency')
            ->where('agency_id', $agencyId)->whereNotNull('user_id')->pluck('user_id');

        $inquiryClients = Client::withoutGlobalScope('agency')->whereNotNull('user_id')
            ->whereIn('id', Inquiry::withoutGlobalScope('agency')->where('agency_id', $agencyId)->select('client_id'))
            ->pluck('user_id');

        $bookingClients = Client::withoutGlobalScope('agency')->whereNotNull('user_id')
            ->whereIn('id', Booking::withoutGlobalScope('agency')->where('agency_id', $agencyId)->select('client_id'))
            ->pluck('user_id');

        $platform = $user->isAdmin() ? $this->userIdsWithRole([RoleName::SuperAdmin->value]) : [];

        return collect($colleagues)->merge($directClients)->merge($inquiryClients)->merge($bookingClients)->merge($platform)->all();
    }

    private function staffIdsForClient(User $user): array
    {
        $clientIds = Client::withoutGlobalScope('agency')->where('user_id', $user->id)->pluck('id');

        $agencyIds = Client::withoutGlobalScope('agency')->whereIn('id', $clientIds)->pluck('agency_id')
            ->merge(Inquiry::withoutGlobalScope('agency')->whereIn('client_id', $clientIds)->pluck('agency_id'))
            ->merge(Booking::withoutGlobalScope('agency')->whereIn('client_id', $clientIds)->pluck('agency_id'))
            ->filter()->unique();

        if ($agencyIds->isEmpty()) {
            return [];
        }

        return User::whereIn('agency_id', $agencyIds)
            ->role([RoleName::Admin->value, RoleName::Agent->value])
            ->pluck('id')->all();
    }

    private function userIdsWithRole(array $roles): array
    {
        return User::role($roles)->pluck('id')->all();
    }
}
