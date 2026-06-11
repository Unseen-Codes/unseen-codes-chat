<?php

namespace UnseenCodes\Chat\Livewire;

use Illuminate\Contracts\Auth\Authenticatable;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Models\Conversation;

class ChatBox extends Component
{
    use WithFileUploads;

    // ── Locked: cannot be tampered from the browser ───────────────────────────
    #[Locked]
    public string $conversationId;

    // ── Public reactive state ─────────────────────────────────────────────────
    public string $body = '';
    public ?string $replyToId = null;
    public array $tempFiles = [];
    public array $whoIsTyping = [];
    public int $page = 1;

    // ── Internal ──────────────────────────────────────────────────────────────
    private ?Conversation $resolvedConversation = null;

    public function mount(Conversation $conversation): void
    {
        abort_unless(
            app(ChatManagerContract::class)->isParticipant(auth()->user(), $conversation),
            403,
            'You are not a participant in this conversation.'
        );

        $this->conversationId = $conversation->id;
        $this->resolvedConversation = $conversation;
    }

    // ── Computed properties ───────────────────────────────────────────────────

    public function getConversationProperty(): Conversation
    {
        return $this->resolvedConversation
            ??= config('chat.models.conversation')::findOrFail($this->conversationId);
    }

    public function getMessagesProperty()
    {
        return config('chat.models.message')::query()
            ->where('conversation_id', $this->conversationId)
            ->with([
                'sender',
                'replyTo.sender',
                'attachments',
                'reactions',
                'readReceipts',
            ])
            ->latest()
            ->paginate(config('chat.pagination.messages_per_page', 30), ['*'], 'page', $this->page);
    }

    public function getReplyMessageProperty(): ?object
    {
        if (! $this->replyToId) {
            return null;
        }

        return config('chat.models.message')::with('sender')->find($this->replyToId);
    }

    // ── Actions ───────────────────────────────────────────────────────────────

    public function sendMessage(): void
    {
        $this->validate([
            'body'       => ['required_without:tempFiles', 'string', 'max:' . config('chat.messages.max_length', 5000)],
            'tempFiles.*'=> ['nullable', 'file', 'max:' . config('chat.attachments.max_size_kb', 10240)],
        ]);

        app(MessageServiceContract::class)->send(
            conversation: $this->conversation,
            sender:       auth()->user(),
            body:         trim($this->body),
            replyToId:    $this->replyToId,
            attachments:  $this->tempFiles,
        );

        $this->reset(['body', 'replyToId', 'tempFiles']);

        if (config('chat.features.read_receipts', true)) {
            app(MessageServiceContract::class)->markAsRead($this->conversation, auth()->user());
        }

        $this->dispatch('chat:message-sent');
        $this->dispatch('chat:scroll-to-bottom');
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    public function replyTo(string $messageId): void
    {
        $this->replyToId = $messageId;
        $this->dispatch('chat:focus-input');
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function react(string $messageId, string $emoji): void
    {
        if (! config('chat.features.reactions', true)) {
            return;
        }

        $message = config('chat.models.message')::findOrFail($messageId);

        app(MessageServiceContract::class)->react($message, auth()->user(), $emoji);
    }

    public function deleteMessage(string $messageId): void
    {
        $message = config('chat.models.message')::findOrFail($messageId);
        app(MessageServiceContract::class)->delete($message, auth()->user());
    }

    public function markRead(): void
    {
        if (config('chat.features.read_receipts', true)) {
            app(MessageServiceContract::class)->markAsRead($this->conversation, auth()->user());
        }
    }

    public function updatedBody(): void
    {
        if (
            config('chat.features.typing_indicator', true) &&
            config('chat.broadcasting.enabled', false)
        ) {
            broadcast(new \UnseenCodes\Chat\Events\UserTyping(
                $this->conversation,
                auth()->user()
            ))->toOthers();
        }
    }

    // ── Broadcast listeners ───────────────────────────────────────────────────

    #[On('echo-private:conversation.{conversationId},MessageSent')]
    public function onMessageSent(array $payload): void
    {
        $this->dispatch('$refresh');
        $this->dispatch('chat:scroll-to-bottom');
    }

    #[On('echo-private:conversation.{conversationId},UserTyping')]
    public function onUserTyping(array $payload): void
    {
        if (! config('chat.features.typing_indicator', true)) {
            return;
        }

        $userId = $payload['user_id'] ?? null;
        $name   = $payload['user_name'] ?? 'Someone';

        if ($userId && $userId !== auth()->id()) {
            $this->whoIsTyping[$userId] = $name;

            // Auto-clear after TTL
            $ttl = config('chat.typing.ttl_seconds', 3) * 1000;
            $this->dispatch('chat:clear-typing', userId: $userId, delay: $ttl);
        }
    }

    #[On('chat:clear-typing')]
    public function clearTyping(int $userId): void
    {
        unset($this->whoIsTyping[$userId]);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        return view('chat::livewire.chat-box', [
            'messages'     => $this->messages,
            'conversation' => $this->conversation,
            'replyMessage' => $this->replyMessage,
            'authId'       => auth()->id(),
            'features'     => config('chat.features'),
        ]);
    }
}
