<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\StoreAgentRequest;
use App\Http\Requests\Agent\UpdateAgentRequest;
use App\Models\Agent;
use App\Services\AgentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(private AgentService $agentService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Agent::class);
        $paginator = $this->agentService->list($request->all());

        return $this->paginate($paginator);
    }

    public function store(StoreAgentRequest $request): JsonResponse
    {
        $this->authorize('create', Agent::class);
        try {
            $agent = $this->agentService->create($request->validated());

            return $this->ok($agent, 'Agent created.', 201);
        } catch (QueryException $e) {
            // e.g. a unique-email race. Never echo driver/SQL text back to the client.
            report($e);

            return $this->fail('The agent could not be created.', [], 422);
        }
    }

    public function show(Agent $agent): JsonResponse
    {
        $this->authorize('view', $agent);

        return $this->ok($agent->load(['user'])->loadCount(['properties', 'clients', 'bookings']));
    }

    public function update(UpdateAgentRequest $request, Agent $agent): JsonResponse
    {
        $updated = $this->agentService->update($agent, $request->validated());

        return $this->ok($updated, 'Agent updated.');
    }

    public function destroy(Request $request, Agent $agent): JsonResponse
    {
        $this->authorize('delete', $agent);
        try {
            $this->agentService->delete($agent);

            return $this->ok(null, 'Agent deleted.');
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }
    }
}
