<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function stats(): JsonResponse
    {
        return $this->ok([
            'total_agencies' => Agency::count(),
            'active_agencies' => Agency::where('is_active', true)->count(),
            'total_properties' => Property::withoutGlobalScope('agency')->count(),
            'available_properties' => Property::withoutGlobalScope('agency')->where('status', 'available')->count(),
            'total_clients' => Client::withoutGlobalScope('agency')->count(),
            'inquiries_today' => Inquiry::withoutGlobalScope('agency')->whereDate('created_at', today())->count(),
        ]);
    }
}
