@php
    $isMine    = $message->sender_id === $authId;
    $isDeleted = $message->trashed();
    $align     = $isMine ? 'items-end' : 'items-start';
    $bubbleBg  = $isMine
        ? 'bg-blue-600 text-white rounded-br-sm'
        : 'bg-gray-100 dark:bg-gray-800 text-gray-900 dark:text-white rounded-bl-sm';
@endphp

<div class="flex flex-col {{ $align }} group w-full py-0.5" id="msg-{{ $message->id }}">

    {{-- Sender name (group only, not mine) --}}
    @if(! $isMine && isset($conversation) && $conversation->isGroup())
        <span class="text-[11px] text-gray-400 dark:text-gray-500 px-1 mb-0.5 ml-1">
            {{ $message->sender->name ?? 'Unknown' }}
        </span>
    @endif

    <div class="flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} items-end gap-1.5 max-w-[80%]">

        {{-- Avatar (others only) --}}
        @if(! $isMine)
            <div class="flex-shrink-0 w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-blue-500 flex items-center justify-center text-white text-[11px] font-bold mb-0.5">
                {{ strtoupper(substr($message->sender->name ?? '?', 0, 1)) }}
            </div>
        @endif

        <div class="flex flex-col {{ $isMine ? 'items-end' : 'items-start' }} gap-1">

            {{-- Reply-to preview --}}
            @if($message->replyTo && ! $isDeleted)
                <div class="flex {{ $isMine ? 'flex-row-reverse' : 'flex-row' }} items-stretch gap-1.5 max-w-full">
                    <div class="w-0.5 rounded-full {{ $isMine ? 'bg-blue-300' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                    <div class="text-[11px] {{ $isMine ? 'text-blue-200' : 'text-gray-400 dark:text-gray-500' }} max-w-[200px]">
                        <span class="font-semibold block">{{ $message->replyTo->sender->name ?? 'Unknown' }}</span>
                        <span class="truncate block">{{ \Illuminate\Support\Str::limit($message->replyTo->body, 50) }}</span>
                    </div>
                </div>
            @endif

            {{-- Main bubble --}}
            <div class="relative">
                <div class="rounded-2xl px-3.5 py-2 text-sm leading-relaxed {{ $bubbleBg }} {{ $isDeleted ? 'opacity-50' : '' }}">
                    @if($isDeleted)
                        <span class="italic opacity-70">This message was deleted</span>
                    @else
                        {!! nl2br(e($message->body)) !!}
                        @if($message->isEdited())
                            <span class="text-[10px] opacity-60 ml-1">(edited)</span>
                        @endif
                    @endif
                </div>

                {{-- Hover actions --}}
                @if(! $isDeleted)
                    <div class="absolute {{ $isMine ? '-left-2 -translate-x-full' : '-right-2 translate-x-full' }} top-1
                                hidden group-hover:flex items-center gap-0.5
                                bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700
                                rounded-xl shadow-sm px-1.5 py-1 z-10">

                        @if($features['reactions'])
                            @foreach(['👍','❤️','😂','😮','😢'] as $emoji)
                                <button
                                    wire:click="react('{{ $message->id }}', '{{ $emoji }}')"
                                    class="text-base hover:scale-125 transition-transform leading-none px-0.5"
                                    title="{{ $emoji }}"
                                >{{ $emoji }}</button>
                            @endforeach
                            <div class="w-px h-4 bg-gray-200 dark:bg-gray-700 mx-0.5"></div>
                        @endif

                        <button
                            wire:click="replyTo('{{ $message->id }}')"
                            title="Reply"
                            class="p-1 text-gray-400 hover:text-blue-500 transition-colors rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                        </button>

                        @if($isMine)
                            <button
                                wire:click="deleteMessage('{{ $message->id }}')"
                                wire:confirm="Delete this message?"
                                title="Delete"
                                class="p-1 text-gray-400 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20"
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
                <div class="flex flex-col gap-1.5 w-full max-w-xs">
                    @foreach($message->attachments as $att)
                        @if($att->isImage())
                            <a href="{{ route('chat.attachment.download', $att) }}" target="_blank"
                               class="block rounded-xl overflow-hidden border border-white/20 shadow-sm">
                                <img
                                    src="{{ \Illuminate\Support\Facades\Storage::disk($att->disk)->url($att->path) }}"
                                    alt="{{ $att->original_name }}"
                                    class="max-w-[200px] max-h-[200px] object-cover"
                                    loading="lazy"
                                >
                            </a>
                        @else
                            <a
                                href="{{ route('chat.attachment.download', $att) }}"
                                class="flex items-center gap-2.5 px-3 py-2 rounded-xl
                                       {{ $isMine ? 'bg-blue-500 hover:bg-blue-400' : 'bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 hover:bg-gray-50' }}
                                       transition-colors text-xs max-w-[200px]"
                            >
                                <svg class="w-4 h-4 flex-shrink-0 {{ $isMine ? 'text-blue-200' : 'text-gray-400' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <span class="truncate {{ $isMine ? 'text-white' : 'text-gray-700 dark:text-gray-300' }}">
                                    {{ $att->original_name }}
                                </span>
                                <span class="{{ $isMine ? 'text-blue-200' : 'text-gray-400' }} flex-shrink-0">
                                    {{ $att->size_formatted }}
                                </span>
                            </a>
                        @endif
                    @endforeach
                </div>
            @endif

            {{-- Reactions summary --}}
            @if(! $isDeleted && $message->reactions->isNotEmpty())
                <div class="flex flex-wrap gap-1">
                    @foreach($message->reactions->groupBy('emoji') as $emoji => $group)
                        @php($myReacted = $group->contains('user_id', $authId))
                        <button
                            wire:click="react('{{ $message->id }}', '{{ $emoji }}')"
                            class="flex items-center gap-1 text-xs px-2 py-0.5 rounded-full border transition-all
                                   {{ $myReacted
                                       ? 'bg-blue-100 border-blue-300 dark:bg-blue-900/30 dark:border-blue-700 text-blue-700 dark:text-blue-300'
                                       : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                        >
                            <span>{{ $emoji }}</span>
                            <span class="font-medium">{{ $group->count() }}</span>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- Timestamp + read receipt --}}
            <div class="flex items-center gap-1.5 px-0.5">
                <span class="text-[10px] text-gray-400 dark:text-gray-500">
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
