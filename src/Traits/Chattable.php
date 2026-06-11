<?php

namespace UnseenCodes\Chat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Models\Conversation;

trait Chattable
{
    public function conversations(): MorphToMany
    {
        return $this->morphToMany(
            config('chat.models.conversation'),
            'participantable',
            config('chat.table_names.participants'),
            'participantable_id',
            'conversation_id'
        )
        ->wherePivotNull('left_at')
        ->withPivot(['role', 'last_read_at', 'joined_at'])
        ->withTimestamps()
        ->latest('updated_at');
    }

    /**
     * Start or resume a 1-on-1 chat with another user.
     */
    public function chatWith(self $user): Conversation
    {
        return app(ChatManagerContract::class)
            ->findOrCreatePrivateConversation($this, $user);
    }

    /**
     * Create a new group conversation.
     */
    public function startGroupChat(string $name, array $participants = []): Conversation
    {
        return app(ChatManagerContract::class)
            ->createGroupConversation($name, $this, $participants);
    }

    /**
     * Total unread message count across all conversations.
     */
    public function unreadMessageCount(): int
    {
        $conversationIds = $this->conversations()->pluck(
            config('chat.table_names.conversations') . '.id'
        );

        return config('chat.models.message')::query()
            ->whereIn('conversation_id', $conversationIds)
            ->where('sender_id', '!=', $this->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $this->getKey()))
            ->count();
    }

    /**
     * Unread count for a specific conversation.
     */
    public function unreadCountIn(Conversation $conversation): int
    {
        return config('chat.models.message')::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $this->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $this->getKey()))
            ->count();
    }
}
