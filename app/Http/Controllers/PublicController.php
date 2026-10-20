<?php

namespace App\Http\Controllers;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function browse(): View
    {
        return view('browse', ['types' => PropertyType::cases()]);
    }

    public function browseApi(Request $request): JsonResponse
    {
        // Array-valued params (q[]=x) used to reach string interpolation and 500.
        $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:50'],
            'city' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $query = Property::withoutGlobalScope('agency')
            ->with(['primaryImage', 'agency'])
            ->where('status', PropertyStatus::Available->value)
            ->whereHas('agency', fn ($a) => $a->where('is_active', true))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('address', 'like', "%{$q}%");
            });
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%'.$request->city.'%');
        }
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        $properties = $query->paginate(12);

        $items = $properties->getCollection()->map(fn ($p) => [
            'id' => $p->id,
            'title' => $p->title,
            'city' => $p->city,
            'type' => $p->type instanceof PropertyType ? $p->type->value : $p->type,
            'price' => (float) $p->price,
            'bedrooms' => $p->bedrooms,
            'bathrooms' => $p->bathrooms,
            'area_sqft' => $p->area_sqft,
            'status' => $p->status instanceof PropertyStatus ? $p->status->value : $p->status,
            'is_featured' => (bool) $p->is_featured,
            'agency' => $p->agency ? ['name' => $p->agency->name] : null,
            'image' => $p->primaryImage ? $p->primaryImage->path : null,
        ]);

        return response()->json([
            'success' => true,
            'data' => $items,
            'pagination' => [
                'current_page' => $properties->currentPage(),
                'last_page' => $properties->lastPage(),
                'total' => $properties->total(),
                'per_page' => $properties->perPage(),
                'from' => $properties->firstItem(),
                'to' => $properties->lastItem(),
            ],
        ]);
    }
}
