<?php

use App\Enums\RoleName;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PublicController;
use App\Models\Agent;
use App\Models\Client;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\RoleMiddleware;

// ── Public routes (no auth required) ────────────────────────────────────────
Route::get('/login', fn () => view('auth.login'))->name('login');
Route::get('/admin/login', fn () => view('auth.admin-login'))->name('admin.login');
Route::get('/register', fn () => view('auth.register'))->name('register');

// Root: guests → /browse, authenticated → / or /super
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('browse');
    }
    if (auth()->user()->isSuperAdmin()) {
        return redirect()->route('super.dashboard');
    }

    return view('dashboard');
})->middleware('active')->name('dashboard');

// Public property browse (no auth required)
Route::get('/browse', [PublicController::class, 'browse'])->name('browse');

// Named logout route (fixes RouteNotFoundException from app.blade.php)
Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// ── Authenticated agency pages (Blade shells, JS handles content) ────────────
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/properties', fn () => view('properties'))->name('properties');
    Route::get('/agents', fn () => view('agents'))->name('agents')
        ->middleware('can:viewAny,'.Agent::class);
    Route::get('/clients', fn () => view('clients'))->name('clients')
        ->middleware('can:viewAny,'.Client::class);
    Route::get('/inquiries', fn () => view('inquiries'))->name('inquiries');
    Route::get('/bookings', fn () => view('bookings'))->name('bookings');
    // Mirrors LogController: agency admins and super admins only.
    Route::get('/logs', fn () => view('logs'))->name('logs')
        ->middleware(RoleMiddleware::using([RoleName::Admin->value, RoleName::SuperAdmin->value]));
    Route::get('/chat', fn () => view('chat'))->name('chat');
});

// ── Super Admin portal ────────────────────────────────────────────────────────
Route::middleware(['auth', 'active', 'super_admin'])
    ->prefix('super')
    ->name('super.')
    ->group(function () {
        Route::get('/', fn () => view('super.dashboard'))->name('dashboard');
        Route::get('/agencies', fn () => view('super.agencies'))->name('agencies');
    });
