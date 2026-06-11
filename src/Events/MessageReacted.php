<?php

namespace UnseenCodes\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use UnseenCodes\Chat\Models\Message;

class MessageReacted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Message $message,
        public readonly Authenticatable $user,
        public readonly string $emoji
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->message->id,
            'user_id'    => $this->user->getKey(),
            'emoji'      => $this->emoji,
        ];
    }
}
