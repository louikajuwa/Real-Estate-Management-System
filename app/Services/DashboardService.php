<?php

namespace App\Services;

use App\Enums\BookingStatus;
use App\Enums\InquiryStatus;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;

class DashboardService
{
    public function stats($user): array
    {
        if ($user->isAdmin() || $user->isSuperAdmin()) {
            $propByStatus = Property::selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')->pluck('cnt', 'status');

            return [
                'properties' => [
                    'total' => Property::count(),
                    'available' => (int) ($propByStatus['available'] ?? 0),
                    'reserved' => (int) ($propByStatus['reserved'] ?? 0),
                    'sold' => (int) ($propByStatus['sold'] ?? 0),
                ],
                'agents' => Agent::count(),
                'clients' => Client::count(),
                'inquiries' => [
                    'total' => Inquiry::count(),
                    'new' => Inquiry::where('status', InquiryStatus::New->value)->count(),
                    'active' => Inquiry::whereNotIn('status', [InquiryStatus::Closed->value])->count(),
                ],
                'bookings' => [
                    'total' => Booking::count(),
                    'upcoming' => Booking::where('status', BookingStatus::Scheduled->value)
                        ->where('scheduled_at', '>=', now())->count(),
                ],
            ];
        }

        if ($user->isAgent()) {
            $agentId = $user->agent?->id;

            return [
                'properties' => [
                    'total' => Property::where('agent_id', $agentId)->count(),
                    'available' => Property::where('agent_id', $agentId)->where('status', 'available')->count(),
                    'reserved' => Property::where('agent_id', $agentId)->where('status', 'reserved')->count(),
                    'sold' => Property::where('agent_id', $agentId)->where('status', 'sold')->count(),
                ],
                'agents' => 1,
                'clients' => Client::where('assigned_agent_id', $agentId)->count(),
                'inquiries' => [
                    'total' => Inquiry::where('agent_id', $agentId)->count(),
                    'new' => Inquiry::where('agent_id', $agentId)->where('status', 'new')->count(),
                    'active' => Inquiry::where('agent_id', $agentId)->whereNotIn('status', ['closed'])->count(),
                ],
                'bookings' => [
                    'total' => Booking::where('agent_id', $agentId)->count(),
                    'upcoming' => Booking::where('agent_id', $agentId)
                        ->where('status', 'scheduled')->where('scheduled_at', '>=', now())->count(),
                ],
            ];
        }

        // Client
        $clientId = $user->client?->id;

        return [
            'properties' => ['total' => Property::where('status', 'available')->count(), 'available' => Property::where('status', 'available')->count(), 'reserved' => 0, 'sold' => 0],
            'agents' => 0,
            'clients' => 1,
            'inquiries' => ['total' => Inquiry::where('client_id', $clientId)->count(), 'new' => Inquiry::where('client_id', $clientId)->where('status', 'new')->count(), 'active' => 0],
            'bookings' => ['total' => Booking::where('client_id', $clientId)->count(), 'upcoming' => Booking::where('client_id', $clientId)->where('status', 'scheduled')->where('scheduled_at', '>=', now())->count()],
        ];
    }

    public function charts($user): array
    {
        $isAdmin = $user->isAdmin() || $user->isSuperAdmin();
        $agentId = $user->agent?->id;

        // Property status doughnut
        $propQuery = Property::selectRaw('status, COUNT(*) as cnt')->groupBy('status');
        if (! $isAdmin && $agentId) {
            $propQuery->where('agent_id', $agentId);
        }
        $propDist = $propQuery->pluck('cnt', 'status');

        // Inquiry pipeline bar
        $inqQuery = Inquiry::selectRaw('status, COUNT(*) as cnt')->groupBy('status');
        if ($user->isAgent()) {
            $inqQuery->where('agent_id', $agentId);
        }
        if ($user->isClient()) {
            $inqQuery->where('client_id', $user->client?->id);
        }
        $inqDist = $inqQuery->pluck('cnt', 'status');

        // Bookings last 30 days line
        $bkQuery = Booking::selectRaw('DATE(scheduled_at) as day, COUNT(*) as cnt')
            ->where('scheduled_at', '>=', now()->subDays(29))
            ->groupBy('day')->orderBy('day');
        if ($user->isAgent()) {
            $bkQuery->where('agent_id', $agentId);
        }
        if ($user->isClient()) {
            $bkQuery->where('client_id', $user->client?->id);
        }
        $bkRaw = $bkQuery->pluck('cnt', 'day');

        $days = [];
        $bkCounts = [];
        for ($i = 29; $i >= 0; $i--) {
            $d = now()->subDays($i)->format('Y-m-d');
            $days[] = now()->subDays($i)->format('M d');
            $bkCounts[] = (int) ($bkRaw[$d] ?? 0);
        }

        $inquiryStatuses = ['new', 'contacted', 'viewing_scheduled', 'negotiation', 'closed'];
        $inquiryLabels = ['New', 'Contacted', 'Viewing Scheduled', 'Negotiation', 'Closed'];
        $inquiryCounts = array_map(fn ($s) => (int) ($inqDist[$s] ?? 0), $inquiryStatuses);

        return [
            'property_distribution' => [
                'labels' => ['Available', 'Reserved', 'Sold'],
                'data' => [
                    (int) ($propDist['available'] ?? 0),
                    (int) ($propDist['reserved'] ?? 0),
                    (int) ($propDist['sold'] ?? 0),
                ],
            ],
            'inquiry_pipeline' => [
                'labels' => $inquiryLabels,
                'data' => $inquiryCounts,
            ],
            'bookings_trend' => [
                'labels' => $days,
                'data' => $bkCounts,
            ],
        ];
    }

    public function activity($user): array
    {
        $query = AuditLog::with('actor')
            ->orderBy('created_at', 'desc')
            ->limit(20);

        // Agency admins see only their agency's activity; super_admin sees all.
        if (! $user->isSuperAdmin() && $user->agency_id) {
            $query->where('agency_id', $user->agency_id);
        }
        if ($user->isAgent()) {
            $query->where('actor_id', $user->id);
        }
        if ($user->isClient()) {
            $query->where('actor_id', $user->id);
        }

        return $query->get()->map(fn ($log) => [
            'id' => $log->id,
            'action' => $log->action,
            'description' => $log->description,
            'actor' => $log->actor?->name ?? 'System',
            'created_at' => $log->created_at?->toIso8601String(),
        ])->toArray();
    }
}
