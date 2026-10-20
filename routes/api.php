<?php

use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\InquiryController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PropertyController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\SocketAuthController;
use App\Http\Controllers\Api\SuperAdmin\AgencyController as SuperAgencyController;
use App\Http\Controllers\Api\SuperAdmin\DashboardController as SuperDashboardController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

// Public auth routes
Route::get('/browse', [PublicController::class, 'browseApi']);   // public property listing (no auth)
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');               // client portal
Route::post('/staff/login', [AuthController::class, 'staffLogin'])->middleware('throttle:auth');    // staff portal — rejects non-staff after auth
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth');

// Socket auth (called by Node.js - no session required, validates HMAC token)
Route::get('/socket/auth', [SocketAuthController::class, 'auth']);

// Authenticated API routes (session + CSRF)
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    // Properties
    Route::apiResource('properties', PropertyController::class);
    Route::patch('properties/{property}/status', [PropertyController::class, 'updateStatus']);
    Route::post('properties/{property}/images', [PropertyController::class, 'uploadImages']);
    Route::delete('properties/{property}/images/{image}', [PropertyController::class, 'deleteImage']);
    Route::patch('properties/{property}/images/{image}/primary', [PropertyController::class, 'setPrimaryImage']);

    // Agents
    Route::apiResource('agents', AgentController::class);

    // Clients
    Route::apiResource('clients', ClientController::class);
    Route::get('clients/{client}/interactions', [ClientController::class, 'interactions']);
    Route::post('clients/{client}/interactions', [ClientController::class, 'addInteraction']);

    // Inquiries
    Route::apiResource('inquiries', InquiryController::class);
    Route::patch('inquiries/{inquiry}/status', [InquiryController::class, 'updateStatus']);

    // Bookings
    Route::apiResource('bookings', BookingController::class);
    Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus']);

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('activity', [DashboardController::class, 'activity']);
        Route::get('charts', [DashboardController::class, 'charts']);
    });

    // Logs (admin only)
    Route::get('logs', [LogController::class, 'index']);

    // Roles (admin only)
    Route::get('roles', [RoleController::class, 'index']);
    Route::post('users/{user}/roles', [RoleController::class, 'assign']);

    // Notifications
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::patch('notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Chat
    Route::get('chat/users', [ChatController::class, 'users']);
    Route::get('chat/{userId}/messages', [ChatController::class, 'messages']);
    Route::post('chat', [ChatController::class, 'send'])->middleware('throttle:60,1');
});

// Super Admin routes (platform management)
Route::middleware(['auth', 'active', 'super_admin'])->prefix('super')->group(function () {
    Route::get('stats', [SuperDashboardController::class, 'stats']);

    Route::get('agencies', [SuperAgencyController::class, 'index']);
    Route::post('agencies', [SuperAgencyController::class, 'store']);
    Route::get('agencies/{agency:slug}', [SuperAgencyController::class, 'show']);
    Route::put('agencies/{agency:slug}', [SuperAgencyController::class, 'update']);
    Route::patch('agencies/{agency:slug}/status', [SuperAgencyController::class, 'toggleStatus']);
    Route::post('agencies/{agency:slug}/reset-admin-password', [SuperAgencyController::class, 'resetAdminPassword']);
    Route::delete('agencies/{agency:slug}', [SuperAgencyController::class, 'destroy']);
});
