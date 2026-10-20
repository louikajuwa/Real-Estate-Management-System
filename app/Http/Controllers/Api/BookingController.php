<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Exceptions\DoubleBookingException;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\StoreBookingRequest;
use App\Models\Booking;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookingService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Booking::class);
        $paginator = $this->bookingService->list($request->all(), $request->user());

        return $this->paginate($paginator);
    }

    public function store(StoreBookingRequest $request): JsonResponse
    {
        $this->authorize('create', Booking::class);
        try {
            $booking = $this->bookingService->schedule($request->validated(), $request->user());

            return $this->ok($booking, 'Booking confirmed.', 201);
        } catch (DoubleBookingException $e) {
            return $this->fail($e->getMessage(), ['scheduled_at' => [$e->getMessage()]], 422);
        }
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('view', $booking);

        return $this->ok($booking->load(['property', 'client', 'agent.user']));
    }

    public function update(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('update', $booking);
        $request->validate(['notes' => ['sometimes', 'nullable', 'string', 'max:1000']]);
        $booking->update($request->only('notes'));

        return $this->ok($booking->fresh(), 'Booking updated.');
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);

        if ($request->user()->isClient() && $request->status !== BookingStatus::Cancelled->value) {
            return $this->fail('Clients may only cancel bookings.', [], 403);
        }
        $this->authorize('updateStatus', $booking);

        try {
            $updated = $this->bookingService->updateStatus($booking, $request->status, $request->user());

            return $this->ok($updated, 'Booking status updated.');
        } catch (InvalidStatusTransitionException|DoubleBookingException $e) {
            return $this->fail($e->getMessage(), ['status' => [$e->getMessage()]], 422);
        }
    }

    public function destroy(Request $request, Booking $booking): JsonResponse
    {
        $this->authorize('delete', $booking);
        $this->bookingService->delete($booking);

        return $this->ok(null, 'Booking deleted.');
    }
}
