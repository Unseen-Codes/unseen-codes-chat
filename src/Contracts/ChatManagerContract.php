<?php

namespace UnseenCodes\Chat\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use UnseenCodes\Chat\Models\Conversation;

interface ChatManagerContract
{
    public function createPrivateConversation(Authenticatable $from, Authenticatable $to): Conversation;

    public function createGroupConversation(string $name, Authenticatable $owner, array $participants = []): Conversation;

    public function findOrCreatePrivateConversation(Authenticatable $from, Authenticatable $to): Conversation;

    public function isParticipant(Authenticatable $user, Conversation $conversation): bool;

    public function addParticipant(Authenticatable $user, Conversation $conversation, string $role = 'member'): void;

    public function removeParticipant(Authenticatable $user, Conversation $conversation): void;
}
