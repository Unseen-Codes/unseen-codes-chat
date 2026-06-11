<?php

namespace UnseenCodes\Chat\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;
use UnseenCodes\Chat\Models\Conversation;
use UnseenCodes\Chat\Models\Message;

interface MessageServiceContract
{
    public function send(
        Conversation $conversation,
        Authenticatable $sender,
        string $body,
        ?string $replyToId = null,
        array $attachments = []
    ): Message;

    public function edit(Message $message, Authenticatable $editor, string $newBody): Message;

    public function delete(Message $message, Authenticatable $actor): void;

    public function react(Message $message, Authenticatable $user, string $emoji): void;

    public function markAsRead(Conversation $conversation, Authenticatable $user): void;
}
