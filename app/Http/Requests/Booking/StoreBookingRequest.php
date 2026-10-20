<?php

namespace App\Http\Requests\Booking;

use App\Support\TenantRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->user()->isSuperAdmin();
    }

    public function rules(): array
    {
        $user = $this->user();
        $rules = [
            'property_id' => ['required', 'integer', TenantRules::property($user)],
            'scheduled_at' => ['required', 'date', 'after:now'],
            'duration_min' => ['nullable', 'integer', 'min:15', 'max:480'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        // Client-role users always act as themselves; any client_id input is ignored.
        if (! $user->isClient()) {
            $rules['client_id'] = ['required', 'integer', TenantRules::client($user)];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'scheduled_at.after' => 'The booking time must be in the future.',
        ];
    }
}
