<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class SocketAuthController extends Controller
{
    public function auth(Request $request)
    {
        $token = $request->query('token');
        if (! $token) {
            return response()->json(['error' => 'missing token'], 401);
        }

        $parts = explode('|', $token);
        if (count($parts) !== 3) {
            return response()->json(['error' => 'invalid token format'], 401);
        }

        [$userId, $timestamp, $hmac] = $parts;
        $ttl = 300; // 5 minutes
        if (abs(time() - (int) $timestamp) > $ttl) {
            return response()->json(['error' => 'token expired'], 401);
        }

        $expected = hash_hmac('sha256', $userId.'|'.$timestamp, config('app.key'));
        if (! hash_equals($expected, $hmac)) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        $user = User::find($userId);
        if (! $user || ! $user->is_active) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        return response()->json([
            'userId' => $user->id,
            'name' => $user->name,
            'roles' => $user->getRoleNames()->toArray(),
        ]);
    }
}
