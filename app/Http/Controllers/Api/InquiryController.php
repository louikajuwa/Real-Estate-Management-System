<?php

namespace App\Http\Controllers\Api;

use App\Enums\InquiryStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inquiry\StoreInquiryRequest;
use App\Models\Inquiry;
use App\Services\InquiryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InquiryController extends Controller
{
    public function __construct(private InquiryService $inquiryService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Inquiry::class);
        $paginator = $this->inquiryService->list($request->all(), $request->user());

        return $this->paginate($paginator);
    }

    public function store(StoreInquiryRequest $request): JsonResponse
    {
        $this->authorize('create', Inquiry::class);
        $inquiry = $this->inquiryService->create($request->validated(), $request->user());

        return $this->ok($inquiry, 'Inquiry submitted.', 201);
    }

    public function show(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('view', $inquiry);

        return $this->ok($inquiry->load(['property', 'client', 'agent.user']));
    }

    public function update(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('update', $inquiry);
        $request->validate(['message' => ['sometimes', 'string', 'max:2000']]);
        $inquiry->update($request->only('message'));

        return $this->ok($inquiry->fresh(), 'Inquiry updated.');
    }

    public function updateStatus(Request $request, Inquiry $inquiry): JsonResponse
    {
        // Clients never change pipeline status.
        if ($request->user()->isClient()) {
            return $this->fail('Forbidden.', [], 403);
        }
        $this->authorize('updateStatus', $inquiry);
        $request->validate(['status' => ['required', Rule::enum(InquiryStatus::class)]]);

        try {
            $updated = $this->inquiryService->transition($inquiry, $request->status, $request->user());

            return $this->ok($updated, 'Status updated.');
        } catch (InvalidStatusTransitionException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }
    }

    public function destroy(Request $request, Inquiry $inquiry): JsonResponse
    {
        $this->authorize('delete', $inquiry);
        $this->inquiryService->delete($inquiry);

        return $this->ok(null, 'Inquiry deleted.');
    }
}
