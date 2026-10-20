<?php

namespace App\Http\Controllers\Auth;

use App\Enums\RoleName;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => $request->password, // hashed via cast
                'is_active' => true,
            ]);
            $user->assignRole(RoleName::Client->value);

            // Auto-provision CRM client record (platform-wide, no agency_id)
            Client::create([
                'user_id' => $user->id,
                'name' => $request->name,
                'email' => $request->email,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
            ],
            'redirect' => $this->safeRedirect($request->input('redirect')),
        ], 'Account created.', 201);
    }

    /**
     * The client navigates to this value, so only same-site relative paths are allowed.
     * Rejects absolute / protocol-relative URLs, "/\\host", javascript: and control characters.
     */
    private function safeRedirect(mixed $target, string $default = '/'): string
    {
        if (! is_string($target) || $target === '' || $target[0] !== '/') {
            return $default;
        }
        if (str_starts_with($target, '//') || preg_match('/[\x00-\x20\\\\]/', $target)) {
            return $default;
        }

        return $target;
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return $this->fail('Invalid credentials.', [], 401);
        }
        $user = Auth::user();
        if (! $user->is_active) {
            Auth::logout();

            return $this->fail('Your account has been deactivated.', [], 403);
        }
        $request->session()->regenerate();

        // Determine redirect based on role
        $redirect = '/';
        if ($user->isSuperAdmin()) {
            $redirect = '/super';
        }

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'agency' => $user->agency ? ['id' => $user->agency->id, 'name' => $user->agency->name] : null,
            ],
            'redirect' => $redirect,
        ], 'Logged in successfully.');
    }

    public function staffLogin(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return $this->fail('Invalid email or password.', [], 401);
        }

        $user = Auth::user();

        // Reject if the account is not staff (admin, agent, super_admin)
        if (! $user->isAgencyStaff() && ! $user->isSuperAdmin()) {
            Auth::logout();

            return $this->fail(
                'These credentials are not registered as staff. Please use the client portal to sign in.',
                [],
                403
            );
        }

        if (! $user->is_active) {
            Auth::logout();

            return $this->fail('Your account has been deactivated. Contact your agency administrator.', [], 403);
        }

        // Check agency active status for agency staff
        if ($user->agency_id && $user->agency && ! $user->agency->is_active) {
            Auth::logout();

            return $this->fail('Your agency account has been deactivated. Please contact support.', [], 403);
        }

        $request->session()->regenerate();

        $redirect = $user->isSuperAdmin() ? '/super' : '/';

        return $this->ok([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->getRoleNames(),
                'agency' => $user->agency ? ['id' => $user->agency->id, 'name' => $user->agency->name] : null,
            ],
            'redirect' => $redirect,
        ], 'Welcome back, '.$user->name.'.');
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->ok(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['agent', 'client', 'agency']);

        return $this->ok([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'agency' => $user->agency ? ['id' => $user->agency->id, 'name' => $user->agency->name, 'slug' => $user->agency->slug] : null,
        ]);
    }
}
