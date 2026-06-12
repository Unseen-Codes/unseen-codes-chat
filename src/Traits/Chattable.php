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

    public function chatWith(self $user): Conversation
    {
        return app(ChatManagerContract::class)->findOrCreatePrivateConversation($this, $user);
    }

    public function startGroupChat(string $name, array $participants = []): Conversation
    {
        return app(ChatManagerContract::class)->createGroupConversation($name, $this, $participants);
    }

    public function unreadMessageCount(): int
    {
        $ids = $this->conversations()->pluck(
            config('chat.table_names.conversations') . '.id'
        );

        return config('chat.models.message')::query()
            ->whereIn('conversation_id', $ids)
            ->where('sender_id', '!=', $this->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $this->getKey()))
            ->count();
    }

    public function unreadCountIn(Conversation $conversation): int
    {
        return config('chat.models.message')::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $this->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $this->getKey()))
            ->count();
    }
}
