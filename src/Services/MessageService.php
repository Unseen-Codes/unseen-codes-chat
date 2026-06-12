<?php

namespace UnseenCodes\Chat\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
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
        abort_if($message->sender_id !== $editor->getKey(), 403, 'Cannot edit another user\'s message.');

        $message->update([
            'body'      => $newBody,
            'edited_at' => now(),
        ]);

        return $message->fresh();
    }

    public function delete(Message $message, Authenticatable $actor): void
    {
        abort_if($message->sender_id !== $actor->getKey(), 403, 'Cannot delete another user\'s message.');

        config('chat.messages.soft_delete', true)
            ? $message->delete()
            : $message->forceDelete();
    }

    public function react(Message $message, Authenticatable $user, string $emoji): void
    {
        $model = config('chat.models.reaction');

        $existing = $model::where([
            'message_id' => $message->id,
            'user_id'    => $user->getKey(),
            'emoji'      => $emoji,
        ])->first();

        $existing ? $existing->delete() : $model::create([
            'message_id' => $message->id,
            'user_id'    => $user->getKey(),
            'emoji'      => $emoji,
        ]);
    }

    public function markAsRead(Conversation $conversation, Authenticatable $user): void
    {
        if (! config('chat.features.read_receipts')) {
            return;
        }

        config('chat.models.message')::query()
            ->where('conversation_id', $conversation->id)
            ->where('sender_id', '!=', $user->getKey())
            ->whereDoesntHave('readReceipts', fn ($q) => $q->where('user_id', $user->getKey()))
            ->each(function ($message) use ($user) {
                config('chat.models.read_receipt')::firstOrCreate([
                    'message_id' => $message->id,
                    'user_id'    => $user->getKey(),
                ], ['read_at' => now()]);
            });

        config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->update(['last_read_at' => now()]);
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
        $exists = config('chat.models.participant')::query()
            ->where('conversation_id', $conversation->id)
            ->where('participantable_id', $user->getKey())
            ->where('participantable_type', get_class($user))
            ->whereNull('left_at')
            ->exists();

        if (! $exists) {
            throw new ParticipantNotAllowedException(
                "User [{$user->getKey()}] is not a participant in conversation [{$conversation->id}]."
            );
        }
    }
}
