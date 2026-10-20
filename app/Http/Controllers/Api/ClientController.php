<?php

namespace App\Http\Controllers\Api;

use App\Enums\InteractionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Client\StoreClientRequest;
use App\Http\Requests\Client\UpdateClientRequest;
use App\Models\Client;
use App\Services\ClientService;
use App\Support\TenantRules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ClientController extends Controller
{
    public function __construct(private ClientService $clientService) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Client::class);
        $paginator = $this->clientService->list($request->all(), $request->user());

        return $this->paginate($paginator);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $this->authorize('create', Client::class);
        $data = $request->validated();
        if ($request->user()->isAgent() && empty($data['assigned_agent_id'])) {
            $data['assigned_agent_id'] = $request->user()->agent?->id;
        }
        $client = $this->clientService->create($data);

        return $this->ok($client, 'Client created.', 201);
    }

    public function show(Client $client): JsonResponse
    {
        $this->authorize('view', $client);

        return $this->ok($client->load(['assignedAgent.user'])->loadCount(['inquiries', 'bookings']));
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $updated = $this->clientService->update($client, $request->validated());

        return $this->ok($updated, 'Client updated.');
    }

    public function destroy(Request $request, Client $client): JsonResponse
    {
        $this->authorize('delete', $client);
        try {
            $this->clientService->delete($client);

            return $this->ok(null, 'Client deleted.');
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), [], 422);
        }
    }

    public function interactions(Request $request, Client $client): JsonResponse
    {
        $this->authorize('view', $client);
        $interactions = $this->clientService->interactions($client);

        return $this->ok($interactions);
    }

    public function addInteraction(Request $request, Client $client): JsonResponse
    {
        $this->authorize('update', $client);
        $user = $request->user();

        $request->validate([
            'type' => ['required', Rule::enum(InteractionType::class)],
            'notes' => ['required', 'string', 'max:5000'],
            'occurred_at' => ['nullable', 'date'],
            'agent_id' => ['nullable', 'integer', TenantRules::agent($user)],
        ]);

        $data = $request->only(['type', 'notes', 'occurred_at']);
        $data['occurred_at'] ??= now();
        // Agents always log interactions as themselves; admins may attribute to an agent of their agency.
        $data['agent_id'] = $user->isAgent() ? $user->agent?->id : $request->input('agent_id');

        $interaction = $this->clientService->addInteraction($client, $data);

        return $this->ok($interaction, 'Interaction recorded.', 201);
    }
}
