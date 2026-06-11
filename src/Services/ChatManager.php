<?php

namespace UnseenCodes\Chat\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Models\Conversation;

class ChatManager implements ChatManagerContract
{
    public function createPrivateConversation(
        Authenticatable $from,
        Authenticatable $to
    ): Conversation {
        /** @var Conversation $conversation */
        $conversation = config('chat.models.conversation')::create([
            'type' => 'private',
        ]);

        $this->addParticipant($from, $conversation, 'owner');
        $this->addParticipant($to, $conversation, 'member');

        return $conversation->load('participants');
    }

    public function createGroupConversation(
        string $name,
        Authenticatable $owner,
        array $participants = []
    ): Conversation {
        /** @var Conversation $conversation */
        $conversation = config('chat.models.conversation')::create([
            'type' => 'group',
            'name' => $name,
        ]);

        $this->addParticipant($owner, $conversation, 'owner');

        foreach ($participants as $participant) {
            $this->addParticipant($participant, $conversation, 'member');
        }

        return $conversation->load('participants');
    }

    public function findOrCreatePrivateConversation(
        Authenticatable $from,
        Authenticatable $to
    ): Conversation {
        $existing = config('chat.models.conversation')::query()
            ->where('type', 'private')
            ->whereHas('participants', fn ($q) => $q->where('participantable_id', $from->getKey())
                ->where('participantable_type', get_class($from)))
            ->whereHas('participants', fn ($q) => $q->where('participantable_id', $to->getKey())
                ->where('participantable_type', get_class($to)))
            ->first();

        return $existing ?? $this->createPrivateConversation($from, $to);
    }

    public function isParticipant(Authenticatable $user, Conversation $conversation): bool
    {
        return config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->whereNull('left_at')
            ->exists();
    }

    public function addParticipant(
        Authenticatable $user,
        Conversation $conversation,
        string $role = 'member'
    ): void {
        config('chat.models.participant')::firstOrCreate(
            [
                'conversation_id'      => $conversation->id,
                'participantable_id'   => $user->getKey(),
                'participantable_type' => get_class($user),
            ],
            [
                'role'      => $role,
                'joined_at' => now(),
            ]
        );
    }

    public function removeParticipant(Authenticatable $user, Conversation $conversation): void
    {
        config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->update(['left_at' => now()]);
    }
}
