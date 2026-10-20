<?php

namespace App\Http\Requests\Property;

use App\Enums\PropertyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePropertyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('property'));
    }

    public function rules(): array
    {
        // `status` is deliberately absent: changes go through PATCH /properties/{id}/status
        // so they are audited and notified.
        $rules = [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['sometimes', Rule::enum(PropertyType::class)],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'address' => ['sometimes', 'string', 'max:500'],
            'city' => ['sometimes', 'string', 'max:100'],
            'bedrooms' => ['nullable', 'integer', 'min:0'],
            'bathrooms' => ['nullable', 'integer', 'min:0'],
            'area_sqft' => ['nullable', 'integer', 'min:0'],
        ];

        // Featuring a listing is an agency-admin decision.
        if ($this->user()->isAdmin()) {
            $rules['is_featured'] = ['boolean'];
        }

        return $rules;
    }
}
