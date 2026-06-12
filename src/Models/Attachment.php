<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $table = 'chat_attachments';

    protected $guarded = [];

    public function getTable(): string
    {
        return config('chat.table_names.attachments', 'chat_attachments');
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(config('chat.models.message'), 'message_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    public function getSizeFormattedAttribute(): string
    {
        $kb = $this->size_bytes / 1024;
        return $kb >= 1024
            ? round($kb / 1024, 1) . ' MB'
            : round($kb) . ' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }
}
