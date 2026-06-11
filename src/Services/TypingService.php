<?php

namespace UnseenCodes\Chat\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Cache;
use UnseenCodes\Chat\Contracts\TypingServiceContract;
use UnseenCodes\Chat\Events\UserTyping;
use UnseenCodes\Chat\Models\Conversation;

class TypingService implements TypingServiceContract
{
    protected function cacheKey(Conversation $conversation, Authenticatable $user): string
    {
        return "chat.typing.{$conversation->id}.{$user->getKey()}";
    }

    public function startTyping(Conversation $conversation, Authenticatable $user): void
    {
        $store = config('chat.typing.cache_store', 'file');
        $ttl   = config('chat.typing.ttl_seconds', 3);

        Cache::store($store)->put(
            $this->cacheKey($conversation, $user),
            $user->name,
            $ttl
        );

        if (config('chat.broadcasting.enabled')) {
            broadcast(new UserTyping($conversation, $user))->toOthers();
        }
    }

    public function stopTyping(Conversation $conversation, Authenticatable $user): void
    {
        $store = config('chat.typing.cache_store', 'file');
        Cache::store($store)->forget($this->cacheKey($conversation, $user));
    }

    public function whoIsTyping(Conversation $conversation, Authenticatable $excludeUser): array
    {
        $store = config('chat.typing.cache_store', 'file');

        return $conversation->participants()
            ->where('participantable_id', '!=', $excludeUser->getKey())
            ->get()
            ->filter(function ($participant) use ($store, $conversation) {
                $key = "chat.typing.{$conversation->id}.{$participant->participantable_id}";
                return Cache::store($store)->has($key);
            })
            ->pluck('participantable.name')
            ->filter()
            ->values()
            ->toArray();
    }
}
