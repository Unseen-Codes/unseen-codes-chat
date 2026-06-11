<?php

namespace UnseenCodes\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use UnseenCodes\Chat\Models\Conversation;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Conversation $conversation,
        public readonly Authenticatable $user
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->conversation->id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversation->id,
            'user_id'         => $this->user->getKey(),
            'read_at'         => now()->toIsoString(),
        ];
    }
}
