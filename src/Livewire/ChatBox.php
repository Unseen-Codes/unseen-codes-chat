<?php

namespace UnseenCodes\Chat\Livewire;

use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Models\Conversation;

class ChatBox extends Component
{
    use WithFileUploads;

    // Locked: cannot be tampered from the browser
    #[Locked]
    public string $conversationId;

    // Form state
    #[Validate('nullable|string|max:5000')]
    public string $body = '';

    public ?string $replyToId = null;

    #[Validate('nullable|array')]
    public array $tempFiles = [];

    // Typing
    public array $whoIsTyping = [];

    // Pagination
    public int $page = 1;
    public bool $hasMorePages = false;

    private ?Conversation $resolvedConversation = null;

    public function mount(Conversation $conversation): void
    {
        abort_unless(
            app(ChatManagerContract::class)->isParticipant(auth()->user(), $conversation),
            403
        );

        $this->conversationId = $conversation->id;
        $this->resolvedConversation = $conversation;
    }

    // ── Computed ──────────────────────────────────────────────────────────────

    public function getConversationProperty(): Conversation
    {
        return $this->resolvedConversation
            ??= config('chat.models.conversation')::findOrFail($this->conversationId);
    }

    public function getMessagesProperty()
    {
        $paginated = config('chat.models.message')::query()
            ->where('conversation_id', $this->conversationId)
            ->with(['sender', 'replyTo.sender', 'attachments', 'reactions', 'readReceipts'])
            ->latest()
            ->paginate(
                config('chat.pagination.messages_per_page', 30),
                ['*'],
                'page',
                $this->page
            );

        $this->hasMorePages = $paginated->hasMorePages();

        // Return newest-last for display (reverse so oldest at top)
        return $paginated->getCollection()->reverse()->values();
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
            'body'        => 'required_without:tempFiles|nullable|string|max:' . config('chat.messages.max_length', 5000),
            'tempFiles.*' => 'nullable|file|max:' . config('chat.attachments.max_size_kb', 10240),
        ]);

        if (blank(trim($this->body)) && empty($this->tempFiles)) {
            return;
        }

        app(MessageServiceContract::class)->send(
            conversation: $this->conversation,
            sender:       auth()->user(),
            body:         trim($this->body),
            replyToId:    $this->replyToId,
            attachments:  $this->tempFiles,
        );

        $this->reset(['body', 'replyToId', 'tempFiles']);

        if (config('chat.features.read_receipts')) {
            app(MessageServiceContract::class)->markAsRead($this->conversation, auth()->user());
        }

        $this->dispatch('chat:scrollToBottom');
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    public function replyTo(string $messageId): void
    {
        $this->replyToId = $messageId;
        $this->dispatch('chat:focusInput');
    }

    public function cancelReply(): void
    {
        $this->replyToId = null;
    }

    public function react(string $messageId, string $emoji): void
    {
        if (! config('chat.features.reactions')) {
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

    public function editMessage(string $messageId, string $newBody): void
    {
        if (! config('chat.features.message_editing')) {
            return;
        }

        $message = config('chat.models.message')::findOrFail($messageId);
        app(MessageServiceContract::class)->edit($message, auth()->user(), $newBody);
    }

    public function markRead(): void
    {
        if (config('chat.features.read_receipts')) {
            app(MessageServiceContract::class)->markAsRead($this->conversation, auth()->user());
        }
    }

    // Typing — fires on wire:model.live update of body
    public function updatedBody(): void
    {
        if (config('chat.broadcasting.enabled') && config('chat.features.typing_indicator')) {
            broadcast(new \UnseenCodes\Chat\Events\UserTyping(
                $this->conversation,
                auth()->user()
            ))->toOthers();
        }
    }

    // ── Broadcast listeners (Livewire v3 + v4 compatible) ────────────────────

    #[On('echo-private:conversation.{conversationId},MessageSent')]
    public function onMessageSent(): void
    {
        $this->dispatch('$refresh');
        $this->dispatch('chat:scrollToBottom');
    }

    #[On('echo-private:conversation.{conversationId},UserTyping')]
    public function onUserTyping(array $payload): void
    {
        $userId = $payload['user_id'] ?? null;

        if ($userId && $userId !== auth()->id()) {
            $this->whoIsTyping[$userId] = $payload['user_name'] ?? 'Someone';

            // Auto-remove after TTL
            $this->dispatch('chat:clearTyping', userId: $userId)
                 ->self()
                 ->after(config('chat.typing.ttl_seconds', 3) * 1000);
        }
    }

    #[On('chat:clearTyping')]
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
