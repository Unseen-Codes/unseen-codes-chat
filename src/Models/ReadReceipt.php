<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadReceipt extends Model
{
    protected $table = 'chat_read_receipts';

    protected $guarded = [];

    protected $casts = ['read_at' => 'datetime'];

    public function getTable(): string
    {
        return config('chat.table_names.read_receipts', 'chat_read_receipts');
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
