{{--
|--------------------------------------------------------------------------
| unseen-codes/chat — ChatBox Component
|--------------------------------------------------------------------------
| This is a single-file Livewire component (Volt-style).
| PHP wire: directives handle all state. No controller. No page reload.
|
| Usage:  <livewire:chat-box :conversation="$conversation" />
|
| Publish to customize:
|   php artisan vendor:publish --tag=chat-views
|   → resources/views/vendor/chat/livewire/chat-box.blade.php
|--------------------------------------------------------------------------
--}}

<div
    class="chat-box flex flex-col h-full bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-800 overflow-hidden"
    x-data="{
        init() {
            this.$nextTick(() => this.scrollToBottom());
        },
        scrollToBottom() {
            const el = this.$refs.messageList;
            if (el) el.scrollTop = el.scrollHeight;
        },
        editingId: null,
        editBody: '',
        startEdit(id, body) {
            this.editingId = id;
            this.editBody = body;
            this.$nextTick(() => this.$refs['edit_' + id]?.focus());
        },
        cancelEdit() {
            this.editingId = null;
            this.editBody = '';
        }
    }"
    x-on:chat:scrolltobottom.window="scrollToBottom()"
    x-on:chat:focusinput.window="$nextTick(() => $refs.msgInput?.focus())"
    wire:poll.5s="markRead"
>

    {{-- ═══════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════════ --}}
    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900 flex-shrink-0">

        {{-- Avatar --}}
        <div class="w-9 h-9 rounded-full flex-shrink-0 flex items-center justify-center text-sm font-semibold text-white
            {{ $conversation->isGroup() ? 'bg-gradient-to-br from-orange-400 to-pink-500' : 'bg-gradient-to-br from-blue-500 to-violet-600' }}">
            {{ strtoupper(substr($conversation->displayNameFor(auth()->user()), 0, 1)) }}
        </div>

        <div class="flex-1 min-w-0">
            <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                {{ $conversation->displayNameFor(auth()->user()) }}
            </p>
            @if($conversation->isGroup())
                <p class="text-xs text-gray-400">{{ $conversation->participants->count() }} members</p>
            @endif
        </div>

        {{-- Unread badge --}}
        @if($features['read_receipts'])
            @php($unread = auth()->user()->unreadCountIn($conversation))
            @if($unread > 0)
                <span class="text-[10px] font-bold text-white bg-blue-500 rounded-full w-5 h-5 flex items-center justify-center flex-shrink-0">
                    {{ $unread > 99 ? '99+' : $unread }}
                </span>
            @endif
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════
         MESSAGES AREA
    ════════════════════════════════════════════════ --}}
    <div
        class="flex-1 overflow-y-auto flex flex-col gap-1 px-4 py-4"
        x-ref="messageList"
    >

        {{-- Load more --}}
        @if($hasMorePages)
            <div class="text-center py-2">
                <button
                    wire:click="loadMore"
                    wire:loading.attr="disabled"
                    class="text-xs text-blue-500 hover:text-blue-700 font-medium px-4 py-1.5 rounded-full hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors disabled:opacity-50"
                >
                    <span wire:loading.remove wire:target="loadMore">↑ Load older messages</span>
                    <span wire:loading wire:target="loadMore">Loading…</span>
                </button>
            </div>
        @endif

        {{-- Empty state --}}
        @if($messages->isEmpty())
            <div class="flex-1 flex flex-col items-center justify-center text-center py-16">
                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/20 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                    </svg>
                </div>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300">No messages yet</p>
                <p class="text-xs text-gray-400 mt-1">Be the first to say something 👋</p>
            </div>
        @endif

        {{-- ── Each message ── --}}
        @foreach($messages as $message)
            @php
                $isMine    = $message->sender_id === $authId;
                $isDeleted = method_exists($message, 'trashed') && $message->trashed();
            @endphp

            <div
                id="msg-{{ $message->id }}"
                class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }} group"
                x-data
            >
                {{-- Sender name in group chats --}}
                @if(! $isMine && $conversation->isGroup())
                    <span class="text-[11px] text-gray-400 mb-0.5 ml-9">
                        {{ $message->sender->name ?? 'Unknown' }}
                    </span>
                @endif

                <div class="flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} items-end gap-2 max-w-[80%]">

                    {{-- Sender avatar --}}
                    @if(! $isMine)
                        <div class="w-7 h-7 rounded-full flex-shrink-0 flex items-center justify-center text-white text-[11px] font-bold mb-0.5 bg-gradient-to-br from-violet-400 to-blue-500">
                            {{ strtoupper(substr($message->sender->name ?? '?', 0, 1)) }}
                        </div>
                    @endif

                    <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }} gap-1">

                        {{-- Reply-to preview --}}
                        @if($message->replyTo && ! $isDeleted)
                            <div class="flex items-stretch gap-1.5 max-w-full {{ $isMine ? 'flex-row-reverse' : '' }}">
                                <div class="w-0.5 rounded-full {{ $isMine ? 'bg-blue-300' : 'bg-gray-300 dark:bg-gray-600' }} flex-shrink-0"></div>
                                <div class="text-[11px] {{ $isMine ? 'text-blue-200' : 'text-gray-400' }} max-w-[180px]">
                                    <span class="font-semibold block">{{ $message->replyTo->sender->name ?? '?' }}</span>
                                    <span class="truncate block">{{ Str::limit($message->replyTo->body, 45) }}</span>
                                </div>
                            </div>
                        @endif

                        {{-- ── Bubble ── --}}
                        <div class="relative">

                            {{-- Inline edit mode --}}
                            <template x-if="editingId === '{{ $message->id }}'">
                                <div class="flex items-end gap-2">
                                    <textarea
                                        x-ref="edit_{{ $message->id }}"
                                        x-model="editBody"
                                        rows="1"
                                        class="rounded-2xl px-3.5 py-2 text-sm border-2 border-blue-400 bg-white dark:bg-gray-800 dark:text-white outline-none resize-none w-48"
                                        x-on:keydown.enter.prevent="
                                            $wire.editMessage('{{ $message->id }}', editBody);
                                            cancelEdit();
                                        "
                                        x-on:keydown.escape="cancelEdit()"
                                    ></textarea>
                                    <div class="flex flex-col gap-1">
                                        <button
                                            x-on:click="$wire.editMessage('{{ $message->id }}', editBody); cancelEdit();"
                                            class="text-[10px] text-white bg-blue-500 hover:bg-blue-600 px-2 py-1 rounded-lg transition-colors"
                                        >Save</button>
                                        <button
                                            x-on:click="cancelEdit()"
                                            class="text-[10px] text-gray-500 hover:text-gray-700 px-2 py-1 rounded-lg transition-colors"
                                        >Cancel</button>
                                    </div>
                                </div>
                            </template>

                            {{-- Normal bubble --}}
                            <template x-if="editingId !== '{{ $message->id }}'">
                                <div
                                    class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed
                                        {{ $isMine
                                            ? 'bg-blue-600 text-white rounded-br-sm'
                                            : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-bl-sm' }}
                                        {{ $isDeleted ? 'opacity-50' : '' }}"
                                >
                                    @if($isDeleted)
                                        <em class="opacity-70">Message deleted</em>
                                    @else
                                        {!! nl2br(e($message->body)) !!}
                                        @if($message->isEdited())
                                            <span class="text-[10px] opacity-60 ml-1">(edited)</span>
                                        @endif
                                    @endif
                                </div>
                            </template>

                            {{-- Hover action bar --}}
                            @if(! $isDeleted)
                                <div class="absolute {{ $isMine ? '-left-1 -translate-x-full' : '-right-1 translate-x-full' }} top-0
                                            hidden group-hover:flex items-center gap-0.5
                                            bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                            rounded-xl shadow-md px-1.5 py-1 z-10">

                                    {{-- Emoji reactions --}}
                                    @if($features['reactions'])
                                        @foreach(['👍','❤️','😂','😮','😢'] as $emoji)
                                            <button
                                                wire:click="react('{{ $message->id }}', '{{ $emoji }}')"
                                                class="text-base hover:scale-125 transition-transform px-0.5 leading-none"
                                                title="{{ $emoji }}"
                                            >{{ $emoji }}</button>
                                        @endforeach
                                        <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5"></div>
                                    @endif

                                    {{-- Reply --}}
                                    <button
                                        wire:click="replyTo('{{ $message->id }}')"
                                        title="Reply"
                                        class="p-1 text-gray-400 hover:text-blue-500 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                        </svg>
                                    </button>

                                    {{-- Edit (own messages only) --}}
                                    @if($isMine && $features['message_editing'])
                                        <button
                                            x-on:click="startEdit('{{ $message->id }}', {{ json_encode($message->body) }})"
                                            title="Edit"
                                            class="p-1 text-gray-400 hover:text-amber-500 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Delete (own messages only) --}}
                                    @if($isMine)
                                        <button
                                            wire:click="deleteMessage('{{ $message->id }}')"
                                            wire:confirm="Delete this message?"
                                            title="Delete"
                                            class="p-1 text-gray-400 hover:text-red-500 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Attachments --}}
                        @if(! $isDeleted && $message->attachments->isNotEmpty())
                            <div class="flex flex-col gap-1.5 mt-1 max-w-[220px]">
                                @foreach($message->attachments as $att)
                                    @if($att->isImage())
                                        <a href="{{ route('chat.attachment.download', $att->id) }}" target="_blank"
                                           class="block rounded-xl overflow-hidden border border-white/20">
                                            <img
                                                src="{{ \Illuminate\Support\Facades\Storage::disk($att->disk)->url($att->path) }}"
                                                alt="{{ $att->original_name }}"
                                                class="max-w-full max-h-48 object-cover"
                                                loading="lazy"
                                            >
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('chat.attachment.download', $att->id) }}"
                                            class="flex items-center gap-2 px-3 py-2 rounded-xl text-xs transition-colors
                                                {{ $isMine
                                                    ? 'bg-blue-500 hover:bg-blue-400 text-white'
                                                    : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50' }}"
                                        >
                                            <svg class="w-4 h-4 flex-shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span class="truncate">{{ $att->original_name }}</span>
                                            <span class="opacity-60 flex-shrink-0">{{ $att->size_formatted }}</span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif

                        {{-- Reaction pills --}}
                        @if(! $isDeleted && $message->reactions->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-0.5">
                                @foreach($message->reactions->groupBy('emoji') as $emoji => $group)
                                    @php($reacted = $group->contains('user_id', $authId))
                                    <button
                                        wire:click="react('{{ $message->id }}', '{{ $emoji }}')"
                                        class="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border transition-all
                                            {{ $reacted
                                                ? 'bg-blue-100 border-blue-300 dark:bg-blue-900/30 dark:border-blue-700 text-blue-700 dark:text-blue-300'
                                                : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:border-blue-300' }}"
                                    >
                                        <span>{{ $emoji }}</span>
                                        <span class="font-medium">{{ $group->count() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        {{-- Meta: time + read receipt --}}
                        <div class="flex items-center gap-1.5 px-0.5 mt-0.5">
                            <span class="text-[10px] text-gray-400">
                                {{ $message->created_at->format('H:i') }}
                            </span>
                            @if($isMine && $features['read_receipts'])
                                @if($message->readReceipts->isNotEmpty())
                                    <span class="text-[10px] text-blue-500" title="Read">✓✓</span>
                                @else
                                    <span class="text-[10px] text-gray-400" title="Sent">✓</span>
                                @endif
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ═══════════════════════════════════════════════
         TYPING INDICATOR
    ════════════════════════════════════════════════ --}}
    @if($features['typing_indicator'] && count($whoIsTyping) > 0)
        @php
            $names = array_values($whoIsTyping);
            $typingText = match(count($names)) {
                1       => $names[0] . ' is typing',
                2       => $names[0] . ' and ' . $names[1] . ' are typing',
                default => 'Several people are typing',
            };
        @endphp
        <div class="flex items-center gap-2 px-4 py-1.5">
            <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-bl-sm px-3 py-2">
                <span class="flex gap-0.5 items-center h-3">
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:0ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:150ms"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:300ms"></span>
                </span>
                <span class="text-[11px] text-gray-400 ml-1.5">{{ $typingText }}</span>
            </div>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         REPLY PREVIEW BAR
    ════════════════════════════════════════════════ --}}
    @if($replyMessage)
        <div class="flex items-center gap-3 px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border-t border-blue-100 dark:border-blue-900 flex-shrink-0">
            <div class="w-0.5 h-8 bg-blue-400 rounded-full flex-shrink-0"></div>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-semibold text-blue-600 dark:text-blue-400">
                    {{ $replyMessage->sender->name ?? '?' }}
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                    {{ Str::limit($replyMessage->body, 60) }}
                </p>
            </div>
            <button
                wire:click="cancelReply"
                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 p-1 rounded-lg hover:bg-white/60"
            >
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         FILE UPLOAD PREVIEWS
    ════════════════════════════════════════════════ --}}
    @if($features['attachments'] && count($tempFiles) > 0)
        <div class="flex flex-wrap gap-2 px-4 pt-2 pb-1 border-t border-gray-100 dark:border-gray-800 flex-shrink-0">
            @foreach($tempFiles as $i => $file)
                <div class="flex items-center gap-1.5 bg-gray-100 dark:bg-gray-800 rounded-lg px-2.5 py-1.5 text-xs text-gray-700 dark:text-gray-300">
                    <svg class="w-3.5 h-3.5 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                    </svg>
                    <span class="max-w-[120px] truncate">{{ $file->getClientOriginalName() }}</span>
                    <button
                        type="button"
                        wire:click="$set('tempFiles.{{ $i }}', null)"
                        class="ml-0.5 text-gray-400 hover:text-red-500 font-bold transition-colors leading-none"
                    >×</button>
                </div>
            @endforeach
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════
         INPUT AREA
    ════════════════════════════════════════════════ --}}
    <div class="flex-shrink-0 px-4 py-3 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-900">
        <div class="flex items-end gap-2">

            {{-- Attach file --}}
            @if($features['attachments'])
                <label
                    class="flex-shrink-0 cursor-pointer p-2 rounded-xl text-gray-400 hover:text-blue-500 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors"
                    title="Attach file"
                >
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

            {{-- Message textarea --}}
            <textarea
                wire:model.live.debounce.400ms="body"
                x-ref="msgInput"
                placeholder="Write a message… (Enter to send)"
                rows="1"
                class="flex-1 resize-none rounded-2xl border border-gray-200 dark:border-gray-700
                       bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white
                       placeholder-gray-400 px-4 py-2.5 text-sm
                       focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent
                       transition-all"
                style="min-height:40px; max-height:140px;"
                x-on:keydown.enter.prevent="
                    if (!$event.shiftKey) { $wire.sendMessage(); }
                "
                x-on:input="
                    $el.style.height = 'auto';
                    $el.style.height = Math.min($el.scrollHeight, 140) + 'px';
                "
            ></textarea>

            {{-- Send button --}}
            <button
                wire:click="sendMessage"
                wire:loading.attr="disabled"
                wire:loading.class="opacity-60 cursor-not-allowed scale-95"
                class="flex-shrink-0 w-10 h-10 flex items-center justify-center
                       bg-blue-600 hover:bg-blue-700 active:scale-95
                       text-white rounded-2xl transition-all"
                title="Send (Enter)"
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

        {{-- Hint --}}
        <p class="text-[10px] text-gray-300 dark:text-gray-600 mt-1.5 ml-1">
            Enter to send · Shift+Enter for new line
        </p>
    </div>

</div>
