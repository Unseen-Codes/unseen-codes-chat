<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Participant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_read_at' => 'datetime',
        'joined_at'    => 'datetime',
        'left_at'      => 'datetime',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.participants', 'chat_participants');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(
            config('chat.models.conversation'),
            'conversation_id'
        );
    }

    public function participantable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }
}
