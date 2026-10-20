<?php

namespace App\Http\Controllers\Api\SuperAdmin;

use App\Enums\PropertyStatus;
use App\Enums\PropertyType;
use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Models\Agent;
use App\Models\Client;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AgencyController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(Request $request): JsonResponse
    {
        $query = Agency::withCount(['users', 'agents', 'properties'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%");
            });
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        return $this->paginate($query->paginate($this->clampPerPage($request->input('per_page'), 20)));
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:100', 'unique:agencies,slug', 'regex:/^[a-z0-9-]+$/'],
            'email' => ['nullable', 'email'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'plan_type' => ['sometimes', 'in:trial,basic,pro'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'unique:users,email'],
            // Optional: if the super admin doesn't set one, we generate a temp password.
            'admin_password' => ['nullable', 'string', 'min:8'],
        ]);

        // A platform-generated temporary password the super admin shares with the agency.
        $tempPassword = $request->filled('admin_password') ? $request->admin_password : Str::password(12);

        $result = DB::transaction(function () use ($request, $tempPassword) {
            $slug = $request->slug ?: Str::slug($request->name);
            // Ensure slug uniqueness
            $base = $slug;
            $i = 1;
            while (Agency::where('slug', $slug)->exists()) {
                $slug = $base.'-'.$i++;
            }

            $agency = Agency::create([
                'name' => $request->name,
                'slug' => $slug,
                'email' => $request->email,
                'tagline' => $request->tagline,
                'city' => $request->city,
                'plan_type' => $request->plan_type ?? 'trial',
                'is_active' => true,
                'created_by' => auth()->id(),
            ]);

            $adminUser = User::create([
                'name' => $request->admin_name,
                'email' => $request->admin_email,
                'password' => Hash::make($tempPassword),
                'agency_id' => $agency->id,
                'is_active' => true,
            ]);
            $adminUser->assignRole(RoleName::Admin->value);

            return $agency->loadCount(['users', 'agents', 'properties']);
        });

        // Surface the credentials so the super admin can forward them to the agency.
        return $this->ok([
            'agency' => $result,
            'admin_email' => $request->admin_email,
            'admin_password' => $tempPassword,
        ], 'Agency created.', 201);
    }

    public function show(Agency $agency): JsonResponse
    {
        $agency->loadCount(['users', 'agents', 'properties']);
        $agency->load('agents.user');

        // The agency's customers (clients). Bypass the agency global scope —
        // the super admin has no agency, so the scope would otherwise see all.
        $clients = Client::withoutGlobalScope('agency')
            ->where('agency_id', $agency->id)
            ->withCount(['inquiries', 'bookings'])
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'email', 'phone', 'assigned_agent_id', 'created_at']);

        // The agency's property listings (super admin sees everything).
        $properties = Property::withoutGlobalScope('agency')
            ->where('agency_id', $agency->id)
            ->with('agent.user')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'title' => $p->title,
                'type' => $p->type instanceof PropertyType ? $p->type->value : $p->type,
                'status' => $p->status instanceof PropertyStatus ? $p->status->value : $p->status,
                'price' => (float) $p->price,
                'city' => $p->city,
                'agent' => $p->agent && $p->agent->user ? $p->agent->user->name : null,
            ]);

        $data = $agency->toArray();
        $data['clients_count'] = $clients->count();
        $data['clients'] = $clients;
        $data['properties'] = $properties;

        return $this->ok($data);
    }

    public function destroy(Agency $agency): JsonResponse
    {
        // Removing an agency tears down everything it owns. Done in a transaction
        // so a partial failure can't leave orphaned, unscoped staff/listings.
        $imagePaths = [];

        DB::transaction(function () use ($agency, &$imagePaths) {
            // Include soft-deleted listings (and any still pointing at this agency's agents):
            // properties.agent_id is restrictOnDelete, so a trashed row would block the agent delete.
            $agentIds = Agent::withoutGlobalScope('agency')->where('agency_id', $agency->id)->pluck('id');
            $properties = Property::withoutGlobalScope('agency')->withTrashed()
                ->where(fn ($q) => $q->where('agency_id', $agency->id)->orWhereIn('agent_id', $agentIds));

            $imagePaths = PropertyImage::whereIn('property_id', (clone $properties)->select('id'))->pluck('path')->all();

            // Properties cascade their images/inquiries/bookings via FK on delete.
            $properties->forceDelete();

            // Detach platform customers — they keep their account, just lose the
            // agency link (they can re-engage another agency later).
            Client::withoutGlobalScope('agency')->where('agency_id', $agency->id)
                ->update(['agency_id' => null, 'assigned_agent_id' => null]);

            // Delete agency staff (agents + their user accounts, admins).
            $staffUserIds = User::where('agency_id', $agency->id)->pluck('id');
            Agent::withoutGlobalScope('agency')->where('agency_id', $agency->id)->delete();
            User::whereIn('id', $staffUserIds)->delete();

            $name = $agency->name;
            $agency->delete();
            $this->audit->log('agency.deleted', null, ['name' => $name], null, "Agency '{$name}' deleted by super admin");
        });

        // Files are removed only after the DB work committed.
        Storage::disk('public')->delete($imagePaths);

        return $this->ok(null, 'Agency and all its data have been deleted.');
    }

    public function update(Request $request, Agency $agency): JsonResponse
    {
        $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'email'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string'],
            'plan_type' => ['sometimes', 'in:trial,basic,pro'],
        ]);

        $agency->update($request->only(['name', 'tagline', 'email', 'phone', 'city', 'country', 'website', 'address', 'plan_type']));

        return $this->ok($agency->fresh(), 'Agency updated.');
    }

    public function resetAdminPassword(Agency $agency): JsonResponse
    {
        // Reset the agency's primary (oldest) admin account — recovery path when
        // the one-time credentials shown at creation are lost.
        $admin = User::where('agency_id', $agency->id)
            ->role(RoleName::Admin->value)
            ->orderBy('id')
            ->first();

        if (! $admin) {
            return $this->fail('This agency has no admin account to reset.', [], 422);
        }

        $newPassword = Str::password(12);
        $admin->update(['password' => Hash::make($newPassword)]);

        $this->audit->log('agency.admin_password_reset', $admin, null, null,
            "Admin password reset for agency '{$agency->name}'");

        return $this->ok([
            'admin_email' => $admin->email,
            'admin_password' => $newPassword,
        ], 'Admin password reset.');
    }

    public function toggleStatus(Agency $agency): JsonResponse
    {
        $agency->update(['is_active' => ! $agency->is_active]);
        $status = $agency->is_active ? 'activated' : 'deactivated';

        return $this->ok($agency, "Agency {$status}.");
    }
}
