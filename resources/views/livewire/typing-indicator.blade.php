@php
    $text = match(count($names)) {
        1       => $names[0] . ' is typing',
        2       => $names[0] . ' and ' . $names[1] . ' are typing',
        default => 'Several people are typing',
    };
@endphp

<div class="flex items-center gap-2 px-5 py-2"
     x-data
     x-show="true"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
>
    <div class="flex items-center gap-1 bg-gray-100 dark:bg-gray-800 rounded-2xl rounded-bl-sm px-3 py-2">
        {{-- Animated dots --}}
        <span class="flex gap-0.5 items-center h-3">
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 0ms"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 150ms"></span>
            <span class="w-1.5 h-1.5 rounded-full bg-gray-400 dark:bg-gray-500 animate-bounce" style="animation-delay: 300ms"></span>
        </span>
        <span class="text-[11px] text-gray-400 dark:text-gray-500 ml-1">{{ $text }}</span>
    </div>
</div>
