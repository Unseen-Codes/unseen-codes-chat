<?php

namespace UnseenCodes\Chat\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Conversation extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = 'chat_conversations';

    protected $guarded = [];

    protected $casts = ['meta' => 'array'];

    public function getTable(): string
    {
        return config('chat.table_names.conversations', 'chat_conversations');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(config('chat.models.participant'), 'conversation_id')
                    ->whereNull('left_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(config('chat.models.message'), 'conversation_id')
                    ->latest();
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Display name — group name or the other participant's name.
     */
    public function displayNameFor(mixed $user): string
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
