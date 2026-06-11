<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasUuids, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
    ];

    public function getTable(): string
    {
        return config('chat.table_names.conversations', 'chat_conversations');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(
            config('chat.models.participant'),
            'conversation_id'
        )->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(
            config('chat.models.message'),
            'conversation_id'
        )->latest();
    }

    public function latestMessage(): HasMany
    {
        return $this->hasMany(
            config('chat.models.message'),
            'conversation_id'
        )->latest()->limit(1);
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }

    /**
     * Get display name — group name or other participant's name.
     */
    public function getDisplayNameFor($user): string
    {
        if ($this->isGroup()) {
            return $this->name ?? 'Group Chat';
        }

        $other = $this->participants()
            ->where('participantable_id', '!=', $user->getKey())
            ->with('participantable')
            ->first();

        return $other?->participantable?->name ?? 'Unknown';
    }
}
