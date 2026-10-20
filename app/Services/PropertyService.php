<?php

namespace App\Services;

use App\Enums\PropertyStatus;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Traits\ClampsPerPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyService
{
    use ClampsPerPage;

    public function __construct(private AuditLogger $audit) {}

    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = Property::with(['agent.user', 'primaryImage'])
            ->withTrashed(false);

        // Platform clients only browse public listings of active agencies.
        if (auth()->user()?->isClient()) {
            $query->available()->whereHas('agency', fn ($a) => $a->where('is_active', true));
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['city'])) {
            $query->where('city', 'like', '%'.$filters['city'].'%');
        }
        if (! empty($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }
        if (! empty($filters['q'])) {
            $q = $filters['q'];
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }

        $sort = in_array($filters['sort'] ?? 'created_at', ['title', 'price', 'city', 'status', 'created_at', 'type']) ? ($filters['sort'] ?? 'created_at') : 'created_at';
        $dir = ($filters['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $dir);

        return $query->paginate($this->clampPerPage($filters['per_page'] ?? null, $perPage));
    }

    public function create(array $data, ?int $agentId = null): Property
    {
        $data['slug'] = $this->uniqueSlug($data['title']);
        $data['status'] = $data['status'] ?? PropertyStatus::Available->value;
        if ($agentId) {
            $data['agent_id'] = $agentId;
        }

        if (empty($data['agency_id']) && auth()->check() && auth()->user()->agency_id) {
            $data['agency_id'] = auth()->user()->agency_id;
        }

        $property = Property::create($data);
        $this->audit->log('property.created', $property, null, $property->toArray(), "Property '{$property->title}' created");

        return $property->load(['agent.user', 'images']);
    }

    public function update(Property $property, array $data): Property
    {
        $old = $property->toArray();
        if (isset($data['title']) && $data['title'] !== $property->title) {
            $data['slug'] = $this->uniqueSlug($data['title'], $property->id);
        }
        $property->update($data);
        $this->audit->log('property.updated', $property, $old, $property->fresh()->toArray(), "Property '{$property->title}' updated");

        return $property->fresh()->load(['agent.user', 'images']);
    }

    public function updateStatus(Property $property, string $status, ?int $actorUserId = null): Property
    {
        $old = $property->status->value;
        $property->update(['status' => $status]);
        $this->audit->log('property.status_changed', $property, ['status' => $old], ['status' => $status], "Property '{$property->title}' status changed to {$status}");

        return $property->fresh();
    }

    public function delete(Property $property): void
    {
        // Delete all images from storage
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->path);
        }
        $this->audit->log('property.deleted', $property, $property->toArray(), null, "Property '{$property->title}' deleted");
        $property->delete(); // soft delete
    }

    public function uploadImages(Property $property, array $files): array
    {
        $images = [];
        $hasPrimary = $property->images()->where('is_primary', true)->exists();
        $sortOrder = $property->images()->max('sort_order') ?? 0;

        foreach ($files as $i => $file) {
            /** @var UploadedFile $file */
            $path = $file->store("properties/{$property->id}", 'public');
            $isPrimary = ! $hasPrimary && $i === 0;
            $img = PropertyImage::create([
                'property_id' => $property->id,
                'path' => $path,
                'is_primary' => $isPrimary,
                'sort_order' => ++$sortOrder,
            ]);
            if ($isPrimary) {
                $hasPrimary = true;
            }
            $images[] = $img;
        }

        return $images;
    }

    public function deleteImage(PropertyImage $image): void
    {
        Storage::disk('public')->delete($image->path);
        $wasPrimary = $image->is_primary;
        $propertyId = $image->property_id;
        $image->delete();

        // If deleted image was primary, promote next image
        if ($wasPrimary) {
            PropertyImage::where('property_id', $propertyId)
                ->orderBy('sort_order')
                ->first()?->update(['is_primary' => true]);
        }
    }

    public function setPrimaryImage(PropertyImage $image): void
    {
        DB::transaction(function () use ($image) {
            PropertyImage::where('property_id', $image->property_id)->update(['is_primary' => false]);
            $image->update(['is_primary' => true]);
        });
    }

    private function uniqueSlug(string $title, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $i = 1;
        while (true) {
            // slug is globally unique across all agencies, so the uniqueness
            // check must bypass the BelongsToAgency global scope — otherwise two
            // agencies with same-titled properties collide on insert (DB 500).
            $q = Property::withoutGlobalScope('agency')->where('slug', $slug);
            if ($excludeId) {
                $q->where('id', '!=', $excludeId);
            }
            if (! $q->exists()) {
                break;
            }
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }
}
