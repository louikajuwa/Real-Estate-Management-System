<?php

namespace App\Http\Controllers\Api;

use App\Enums\PropertyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Property\StorePropertyRequest;
use App\Http\Requests\Property\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    public function __construct(private PropertyService $propertyService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Property::class);
        $paginator = $this->propertyService->list($request->all(), $this->clampPerPage($request->input('per_page')));

        return $this->paginate($paginator);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $agentId = null;

        // Listings belong to an agency; only agency staff have that context.
        if (! $user->isAgent() && ! $user->isAdmin()) {
            return $this->fail('Only agency staff can create listings. Sign in with an agency account.', [], 422);
        }

        if ($user->isAgent()) {
            $agentId = $user->agent?->id;
            if (! $agentId) {
                return $this->fail('Your account has no agent profile, so it cannot own listings.', [], 422);
            }
            unset($data['agent_id']);
        }

        $property = $this->propertyService->create($data, $agentId);

        return $this->ok($property, 'Property created.', 201);
    }

    public function show(Request $request, Property $property): JsonResponse
    {
        $this->authorize('view', $property);

        return $this->ok($property->load(['agent.user', 'images']));
    }

    public function update(UpdatePropertyRequest $request, Property $property): JsonResponse
    {
        $updated = $this->propertyService->update($property, $request->validated());

        return $this->ok($updated, 'Property updated.');
    }

    public function updateStatus(Request $request, Property $property): JsonResponse
    {
        $this->authorize('update', $property);
        $request->validate(['status' => ['required', Rule::enum(PropertyStatus::class)]]);
        $updated = $this->propertyService->updateStatus($property, $request->status);

        return $this->ok($updated, 'Status updated.');
    }

    public function destroy(Request $request, Property $property): JsonResponse
    {
        $this->authorize('delete', $property);
        $this->propertyService->delete($property);

        return $this->ok(null, 'Property deleted.');
    }

    public function uploadImages(Request $request, Property $property): JsonResponse
    {
        $this->authorize('manageImages', $property);
        $request->validate([
            'images' => ['required', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $images = $this->propertyService->uploadImages($property, $request->file('images'));

        return $this->ok($images, 'Images uploaded.', 201);
    }

    public function deleteImage(Request $request, Property $property, PropertyImage $image): JsonResponse
    {
        $this->authorize('manageImages', $property);
        if ($image->property_id !== $property->id) {
            return $this->fail('Image does not belong to this property.', [], 422);
        }
        $this->propertyService->deleteImage($image);

        return $this->ok(null, 'Image deleted.');
    }

    public function setPrimaryImage(Request $request, Property $property, PropertyImage $image): JsonResponse
    {
        $this->authorize('manageImages', $property);
        if ($image->property_id !== $property->id) {
            return $this->fail('Image does not belong to this property.', [], 422);
        }
        $this->propertyService->setPrimaryImage($image);

        return $this->ok(null, 'Primary image updated.');
    }
}
