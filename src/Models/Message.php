<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'meta'      => 'array',
        'edited_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.messages', 'chat_messages');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            config('chat.models.conversation'),
            'conversation_id'
        );
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(
            config('chat.user_model'),
            'sender_id'
        );
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(static::class, 'reply_to_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(config('chat.models.attachment'), 'message_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(config('chat.models.reaction'), 'message_id');
    }

    public function readReceipts(): HasMany
    {
        return $this->hasMany(config('chat.models.read_receipt'), 'message_id');
    }

    public function isEdited(): bool
    {
        return $this->edited_at !== null;
    }

    public function isReadBy($user): bool
    {
        return $this->readReceipts()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
