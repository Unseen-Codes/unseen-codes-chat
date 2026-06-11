<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reaction extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return config('chat.table_names.reactions', 'chat_reactions');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(config('chat.models.message'), 'message_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('chat.user_model'), 'user_id');
    }
}
