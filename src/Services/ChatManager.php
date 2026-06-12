<?php

namespace UnseenCodes\Chat\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Models\Conversation;

class ChatManager implements ChatManagerContract
{
    private function conversationModel(): string
    {
        return config('chat.models.conversation');
    }

    private function participantModel(): string
    {
        return config('chat.models.participant');
    }

    public function createPrivateConversation(Authenticatable $from, Authenticatable $to): Conversation
    {
        $conversation = $this->conversationModel()::create(['type' => 'private']);

        $this->addParticipant($from, $conversation, 'owner');
        $this->addParticipant($to, $conversation, 'member');

        return $conversation->load('participants');
    }

    public function createGroupConversation(string $name, Authenticatable $owner, array $participants = []): Conversation
    {
        $conversation = $this->conversationModel()::create([
            'type' => 'group',
            'name' => $name,
        ]);

        $this->addParticipant($owner, $conversation, 'owner');

        foreach ($participants as $participant) {
            $this->addParticipant($participant, $conversation, 'member');
        }

        return $conversation->load('participants');
    }

    public function findOrCreatePrivateConversation(Authenticatable $from, Authenticatable $to): Conversation
    {
        $existing = $this->conversationModel()::query()
            ->where('type', 'private')
            ->whereHas('participants', fn ($q) => $q
                ->where('participantable_id', $from->getKey())
                ->where('participantable_type', get_class($from))
            )
            ->whereHas('participants', fn ($q) => $q
                ->where('participantable_id', $to->getKey())
                ->where('participantable_type', get_class($to))
            )
            ->first();

        return $existing ?? $this->createPrivateConversation($from, $to);
    }

    public function isParticipant(Authenticatable $user, Conversation $conversation): bool
    {
        return $this->participantModel()::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->whereNull('left_at')
            ->exists();
    }

    public function addParticipant(Authenticatable $user, Conversation $conversation, string $role = 'member'): void
    {
        $this->participantModel()::firstOrCreate(
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
        $this->participantModel()::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->update(['left_at' => now()]);
    }
}
