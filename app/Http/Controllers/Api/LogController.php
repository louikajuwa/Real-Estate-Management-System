<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isAdmin() && ! $request->user()->isSuperAdmin()) {
            return $this->fail('Forbidden.', [], 403);
        }

        $query = AuditLog::with('actor')->orderBy('created_at', 'desc');

        // Agency admins see only their own agency's logs; super_admin sees all.
        $user = $request->user();
        if (! $user->isSuperAdmin() && $user->agency_id) {
            $query->where('agency_id', $user->agency_id);
        }

        if ($request->filled('actor_id')) {
            $query->where('actor_id', $request->actor_id);
        }
        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->action.'%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%");
            });
        }

        $paginator = $query->paginate($this->clampPerPage($request->input('per_page'), 25));

        return $this->paginate($paginator);
    }
}
