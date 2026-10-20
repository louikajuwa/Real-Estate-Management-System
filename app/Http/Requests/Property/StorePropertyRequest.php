<?php

namespace App\Http\Requests\Property;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Models\Property;
use App\Support\TenantRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Property::class);
    }

    public function rules(): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::enum(PropertyType::class)],
            'status' => ['nullable', Rule::enum(PropertyStatus::class)],
            'price' => ['required', 'numeric', 'min:0'],
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
            'agent_id' => ['nullable', 'integer', TenantRules::agent($this->user())],
        ];

        if ($this->user()->isAgent()) {
            $rules['agent_id'] = ['nullable'];
        } elseif ($this->user()->isAdmin()) {
            $rules['agent_id'] = ['required', 'integer', TenantRules::agent($this->user())];
            // Featuring a listing is an agency-admin decision (matches UpdatePropertyRequest).
            $rules['is_featured'] = ['boolean'];
        }

        return $rules;
    }
}
