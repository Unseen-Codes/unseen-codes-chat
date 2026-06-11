<?php

namespace UnseenCodes\Chat\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Events\MessageReacted;
use UnseenCodes\Chat\Events\MessageRead;
use UnseenCodes\Chat\Events\MessageSent;
use UnseenCodes\Chat\Exceptions\ParticipantNotAllowedException;
use UnseenCodes\Chat\Models\Conversation;
use UnseenCodes\Chat\Models\Message;

class MessageService implements MessageServiceContract
{
    public function send(
        Conversation $conversation,
        Authenticatable $sender,
        string $body,
        ?string $replyToId = null,
        array $attachments = []
    ): Message {
        $this->ensureParticipant($sender, $conversation);

        $message = DB::transaction(function () use ($conversation, $sender, $body, $replyToId, $attachments) {
            /** @var Message $message */
            $message = config('chat.models.message')::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $sender->getKey(),
                'body'            => $body,
                'reply_to_id'     => $replyToId,
                'type'            => count($attachments) > 0 ? 'file' : 'text',
            ]);

            foreach ($attachments as $file) {
                $this->storeAttachment($message, $file);
            }

            return $message;
        });

        $conversation->touch();

        if (config('chat.broadcasting.enabled')) {
            broadcast(new MessageSent($message))->toOthers();
        }

        return $message->load(['sender', 'attachments', 'reactions', 'replyTo.sender']);
    }

    public function edit(Message $message, Authenticatable $editor, string $newBody): Message
    {
        abort_if(
            $message->sender_id !== $editor->getKey(),
            403,
            'Cannot edit another user\'s message.'
        );

        $message->update([
            'body'      => $newBody,
            'edited_at' => now(),
        ]);

        return $message->fresh();
    }

    public function delete(Message $message, Authenticatable $actor): void
    {
        abort_if(
            $message->sender_id !== $actor->getKey(),
            403,
            'Cannot delete another user\'s message.'
        );

        if (config('chat.messages.soft_delete', true)) {
            $message->delete();
        } else {
            $message->forceDelete();
        }
    }

    public function react(Message $message, Authenticatable $user, string $emoji): void
    {
        if (! config('chat.features.reactions', true)) {
            return;
        }

        $reactionModel = config('chat.models.reaction');

        $existing = $reactionModel::where([
            'message_id' => $message->id,
            'user_id'    => $user->getKey(),
            'emoji'      => $emoji,
        ])->first();

        if ($existing) {
            $existing->delete();
        } else {
            $reactionModel::create([
                'message_id' => $message->id,
                'user_id'    => $user->getKey(),
                'emoji'      => $emoji,
            ]);
        }

        if (config('chat.broadcasting.enabled')) {
            broadcast(new MessageReacted($message, $user, $emoji))->toOthers();
        }
    }

    public function markAsRead(Conversation $conversation, Authenticatable $user): void
    {
        if (! config('chat.features.read_receipts', true)) {
            return;
        }

        $unread = config('chat.models.message')::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $user->getKey()))
            ->get();

        foreach ($unread as $message) {
            config('chat.models.read_receipt')::firstOrCreate([
                'message_id' => $message->id,
                'user_id'    => $user->getKey(),
            ], [
                'read_at' => now(),
            ]);
        }

        config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->update(['last_read_at' => now()]);

        if (config('chat.broadcasting.enabled')) {
            broadcast(new MessageRead($conversation, $user))->toOthers();
        }
    }

    protected function storeAttachment(Message $message, UploadedFile $file): void
    {
        $disk = config('chat.attachments.disk', 'public');
        $path = $file->store(config('chat.attachments.path', 'chat/attachments'), $disk);

        config('chat.models.attachment')::create([
            'message_id'    => $message->id,
            'disk'          => $disk,
            'path'          => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type'     => $file->getMimeType(),
            'size_bytes'    => $file->getSize(),
        ]);
    }

    protected function ensureParticipant(Authenticatable $user, Conversation $conversation): void
    {
        $isParticipant = config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->whereNull('left_at')
            ->exists();

        if (! $isParticipant) {
            throw new ParticipantNotAllowedException(
                "User [{$user->getKey()}] is not a participant in conversation [{$conversation->id}]."
            );
        }
    }
}
