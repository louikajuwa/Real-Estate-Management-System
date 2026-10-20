<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function stats(Request $request): JsonResponse
    {
        return $this->ok($this->dashboardService->stats($request->user()));
    }

    public function activity(Request $request): JsonResponse
    {
        return $this->ok($this->dashboardService->activity($request->user()));
    }

    public function charts(Request $request): JsonResponse
    {
        return $this->ok($this->dashboardService->charts($request->user()));
    }
}
