<?php

namespace App\Http\Requests\Inquiry;

use App\Support\TenantRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreInquiryRequest extends FormRequest
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
            'message' => ['required', 'string', 'max:2000'],
        ];

        // Client-role users always act as themselves; any client_id input is ignored.
        if (! $user->isClient()) {
            $rules['client_id'] = ['required', 'integer', TenantRules::client($user)];
        }

        return $rules;
    }
}
