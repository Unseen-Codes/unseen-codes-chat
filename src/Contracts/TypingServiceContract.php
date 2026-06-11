<?php

namespace UnseenCodes\Chat\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use UnseenCodes\Chat\Models\Conversation;

interface TypingServiceContract
{
    public function startTyping(Conversation $conversation, Authenticatable $user): void;

    public function stopTyping(Conversation $conversation, Authenticatable $user): void;

    public function whoIsTyping(Conversation $conversation, Authenticatable $excludeUser): array;
}
