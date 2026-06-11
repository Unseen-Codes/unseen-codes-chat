<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
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
        $bytes = $this->size_bytes;

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1024) . ' KB';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }
}
