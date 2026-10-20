<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Your account has been deactivated.'], 403);
                }
                return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
            }

            // Check if the user's agency is still active (skip for super_admin and clients)
            if ($user->agency_id && $user->agency && !$user->agency->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                if ($request->expectsJson()) {
                    return response()->json(['success' => false, 'message' => 'Your agency account has been deactivated.'], 403);
                }
                return redirect()->route('login')->withErrors(['email' => 'Your agency account has been deactivated. Please contact support.']);
            }
        }

        return $next($request);
    }
}
