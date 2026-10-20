<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * `exists:` validation hits the DB directly and so bypasses the BelongsToAgency
 * global scope. These helpers re-apply tenant boundaries to referenced ids.
 */
class TenantRules
{
    public static function agent(User $user): Exists
    {
        return Rule::exists('agents', 'id')->where('agency_id', $user->agency_id);
    }

    public static function client(User $user): Exists
    {
        return Rule::exists('clients', 'id')->where('agency_id', $user->agency_id);
    }

    /** Staff: own agency's live properties. Clients/others: available listings of active agencies. */
    public static function property(User $user): Exists
    {
        $rule = Rule::exists('properties', 'id')->whereNull('deleted_at');

        if ($user->isAgencyStaff()) {
            return $rule->where('agency_id', $user->agency_id);
        }

        return $rule->using(function (Builder $query) {
            $query->where('status', 'available')
                ->whereIn('agency_id', fn (Builder $q) => $q->select('id')->from('agencies')->where('is_active', true));
        });
    }
}
