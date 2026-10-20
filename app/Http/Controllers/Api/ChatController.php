<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(private ChatService $chatService) {}

    public function users(Request $request): JsonResponse
    {
        return $this->ok($this->chatService->contacts($request->user()));
    }

    public function messages(Request $request, int $userId): JsonResponse
    {
        $me = $request->user()->id;
        $messages = ChatMessage::where(function ($q) use ($me, $userId) {
            $q->where('sender_id', $me)->where('receiver_id', $userId);
        })
            ->orWhere(function ($q) use ($me, $userId) {
                $q->where('sender_id', $userId)->where('receiver_id', $me);
            })
            ->orderBy('created_at', 'asc')
            ->paginate($this->clampPerPage($request->input('per_page'), 50, 100));

        return $this->paginate($messages);
    }

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:5000'],
        ]);

        if ($request->receiver_id == $request->user()->id) {
            return $this->fail('Cannot send message to yourself.', [], 422);
        }

        // Only users inside the sender's tenant relationship may be messaged.
        if (! $this->chatService->canMessage($request->user(), (int) $request->receiver_id)) {
            return $this->fail('You are not allowed to message this user.', [], 403);
        }

        $message = $this->chatService->send(
            $request->user()->id,
            $request->receiver_id,
            $request->message
        );

        return $this->ok($message, 'Message sent.', 201);
    }
}
