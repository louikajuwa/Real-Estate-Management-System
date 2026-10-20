<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Inquiry;
use App\Models\Property;
use App\Policies\AgentPolicy;
use App\Policies\BookingPolicy;
use App\Policies\ClientPolicy;
use App\Policies\InquiryPolicy;
use App\Policies\PropertyPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Property::class, PropertyPolicy::class);
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(Client::class, ClientPolicy::class);
        Gate::policy(Inquiry::class, InquiryPolicy::class);
        Gate::policy(Booking::class, BookingPolicy::class);

        // Brute-force protection for login / staff login / registration.
        RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(10)
            ->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
    }
}
