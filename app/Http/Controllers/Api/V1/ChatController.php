<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\NewChatMessage;
use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Trip;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function index(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request->user(), $trip);

        $messages = $trip->chatMessages()
            ->with('sender:id,name')
            ->orderBy('created_at')
            ->get()
            ->map(fn (ChatMessage $message) => $this->payload($message));

        return response()->json(['messages' => $messages]);
    }

    public function store(Request $request, Trip $trip)
    {
        $this->authorizeTrip($request->user(), $trip);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $message = $trip->chatMessages()->create([
            'sender_id' => $request->user()->id,
            'message' => $data['message'],
        ]);

        broadcast(new NewChatMessage($message->load('sender')));

        return response()->json([
            'message' => 'Message sent.',
            'chat' => $this->payload($message),
        ], 201);
    }

    private function authorizeTrip($user, Trip $trip): void
    {
        if (! $trip->isParticipant($user)) {
            abort(403, 'Only trip participants can use the chat.');
        }
    }

    private function payload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'trip_id' => $message->trip_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name,
            'message' => $message->message,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
