<div
    class="chat-box flex flex-col bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden h-full"
    x-data="chatBoxAlpine()"
    x-on:chat:scroll-to-bottom.window="scrollToBottom()"
    x-on:chat:focus-input.window="$nextTick(() => $refs.input?.focus())"
>

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-3 px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="flex-1 min-w-0">
            <p class="font-semibold text-gray-900 dark:text-white truncate">
                {{ $conversation->isGroup()
                    ? ($conversation->name ?? 'Group Chat')
                    : $conversation->getDisplayNameFor(auth()->user()) }}
            </p>
            @if($conversation->isGroup())
                <p class="text-xs text-gray-400 mt-0.5">
                    {{ $conversation->participants->count() }} members
                </p>
            @endif
        </div>

        {{-- Unread badge --}}
        @if(config('chat.features.read_receipts'))
            @php($unread = auth()->user()->unreadCountIn($conversation))
            @if($unread > 0)
                <span class="flex-shrink-0 inline-flex items-center justify-center w-5 h-5 text-[10px] font-bold text-white bg-blue-500 rounded-full">
                    {{ $unread > 99 ? '99+' : $unread }}
                </span>
            @endif
        @endif
    </div>

    {{-- ── Messages ────────────────────────────────────────────────────────── --}}
    <div
        class="flex-1 overflow-y-auto flex flex-col-reverse px-4 py-4 gap-1 scroll-smooth"
        x-ref="messageList"
        wire:poll.absent.5s="markRead"
    >
        {{-- Empty state --}}
        @if($messages->isEmpty())
            <div class="flex flex-col items-center justify-center h-full text-center py-12">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-200">No messages yet</p>
                <p class="text-xs text-gray-400 mt-1">Be the first to say something!</p>
            </div>
        @else
            {{-- Load more --}}
            @if($messages->hasMorePages())
                <div class="text-center py-2 order-first">
                    <button
                        wire:click="loadMore"
                        wire:loading.attr="disabled"
                        class="text-xs text-blue-500 hover:text-blue-700 font-medium px-3 py-1 rounded-full hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                    >
                        <span wire:loading.remove wire:target="loadMore">Load older messages</span>
                        <span wire:loading wire:target="loadMore">Loading…</span>
                    </button>
                </div>
            @endif

            {{-- Messages --}}
            @foreach($messages as $message)
                @include('chat::livewire.message-item', [
                    'message' => $message,
                    'authId'  => $authId,
                    'features'=> $features,
                ])
            @endforeach
        @endif
    </div>

    {{-- ── Typing indicator ────────────────────────────────────────────────── --}}
    @if($features['typing_indicator'] && count($whoIsTyping) > 0)
        @include('chat::livewire.typing-indicator', ['names' => array_values($whoIsTyping)])
    @endif

    {{-- ── Reply preview ───────────────────────────────────────────────────── --}}
    @if($replyMessage)
        <div class="flex items-center gap-3 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-t border-blue-100 dark:border-blue-800">
            <div class="w-0.5 h-8 bg-blue-400 rounded-full flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-blue-600 dark:text-blue-400">{{ $replyMessage->sender->name }}</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ \Illuminate\Support\Str::limit($replyMessage->body, 60) }}</p>
            </div>
            <button
                wire:click="cancelReply"
                class="flex-shrink-0 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- ── File previews ───────────────────────────────────────────────────── --}}
    @if($features['attachments'] && count($tempFiles) > 0)
        <div class="flex flex-wrap gap-2 px-4 pt-2 border-t border-gray-100 dark:border-gray-800">
            @foreach($tempFiles as $i => $file)
                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300">
                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    {{ \Illuminate\Support\Str::limit($file->getClientOriginalName(), 24) }}
                    <button type="button" wire:click="$set('tempFiles.{{ $i }}', null)"
                            class="ml-0.5 text-gray-400 hover:text-red-500 transition-colors font-bold">×</button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ── Input area ──────────────────────────────────────────────────────── --}}
    <div class="px-4 py-3 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="flex items-end gap-2">

            {{-- Attachment button --}}
            @if($features['attachments'])
                <label class="flex-shrink-0 cursor-pointer p-2 text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-xl transition-colors">
                    <input
                        type="file"
                        class="hidden"
                        wire:model="tempFiles"
                        multiple
                        accept="{{ implode(',', config('chat.attachments.allowed_types', ['image/*'])) }}"
                    >
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                </label>
            @endif

            {{-- Textarea --}}
            <div class="flex-1 relative">
                <textarea
                    wire:model.live.debounce.400ms="body"
                    x-ref="input"
                    placeholder="Write a message…"
                    rows="1"
                    class="w-full resize-none rounded-2xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all"
                    style="min-height: 40px; max-height: 160px;"
                    x-on:keydown.enter.prevent="
                        if (!$event.shiftKey) { $wire.sendMessage(); }
                    "
                    x-on:input="$el.style.height = 'auto'; $el.style.height = Math.min($el.scrollHeight, 160) + 'px';"
                ></textarea>
            </div>

            {{-- Send button --}}
            <button
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-50 cursor-not-allowed"
                class="flex-shrink-0 w-10 h-10 flex items-center justify-center bg-blue-600 hover:bg-blue-700 active:scale-95 text-white rounded-2xl transition-all disabled:opacity-50"
                title="Send message"
            >
                <svg wire:loading.remove class="w-4 h-4 translate-x-px" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11a1 1 0 112 0v4.571a1 1 0 00.725.962l5 1.428a1 1 0 001.17-1.408l-7-14z"/>
                </svg>
                <svg wire:loading class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </button>
        </div>
    </div>
</div>

@script
<script>
function chatBoxAlpine() {
    return {
        init() {
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) {
                el.scrollTop = 0; // flex-col-reverse: 0 = bottom
            }
        }
    };
}
</script>
@endscript
