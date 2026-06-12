# unseen-codes/chat

A plug-and-play Livewire single-file chat component for Laravel.

[![Laravel](https://img.shields.io/badge/Laravel-11%20|%2012%20|%2013-red)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3%20|%204-blue)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple)](https://php.net)

---

## What you get

- Single-file Livewire component — drop `<livewire:chat-box>` anywhere
- 1-on-1 and group conversations
- Emoji reactions (toggle on/off)
- Reply threading
- File attachments
- Read receipts (✓✓)
- Typing indicator
- Inline message editing
- Soft-deleted messages
- Config-driven — toggle every feature, swap every model
- No controller needed — all wire: directives

---

## Requirements

| Package | Version |
|---------|---------|
| PHP | 8.2+ |
| Laravel | 11, 12, or 13 |
| Livewire | 3 or 4 |

---

## Installation

### 1. Install the package

```bash
composer require unseen-codes/chat
```

### 2. Publish config

```bash
php artisan vendor:publish --tag=chat-config
```

### 3. Publish and run migrations

```bash
php artisan vendor:publish --tag=chat-migrations
php artisan migrate
```

### 4. Add Chattable trait to your User model

```php
// app/Models/User.php
use UnseenCodes\Chat\Traits\Chattable;

class User extends Authenticatable
{
    use Chattable;
}
```

---

## Usage

### Start a conversation (in any route/page)

```php
// routes/web.php — no controller needed with Livewire/Volt pages
Route::get('/chat/{user}', function (User $user) {
    $conversation = auth()->user()->chatWith($user);
    return view('chat', compact('conversation'));
})->middleware('auth');
```

### Or using the service directly

```php
use UnseenCodes\Chat\Contracts\ChatManagerContract;

$chat = app(ChatManagerContract::class);

// 1-on-1
$conversation = $chat->findOrCreatePrivateConversation($alice, $bob);

// Group
$conversation = $chat->createGroupConversation('Team Chat', $alice, [$bob, $charlie]);
```

### Drop the component in any Blade view

```blade
{{-- resources/views/chat.blade.php --}}
<div class="h-[600px]">
    <livewire:chat-box :conversation="$conversation" />
</div>
```

That's it. No controller. No extra routes. The component handles everything.

---

## Using Volt single-file pages

If you want the whole chat page as a Volt single file:

```bash
php artisan make:volt chat/show --class
```

```php
{{-- resources/views/livewire/chat/show.blade.php --}}
<?php

use Livewire\Component;
use App\Models\User;
use UnseenCodes\Chat\Contracts\ChatManagerContract;

new class extends Component {

    public $conversation;

    public function mount(User $user): void
    {
        $this->conversation = app(ChatManagerContract::class)
            ->findOrCreatePrivateConversation(auth()->user(), $user);
    }

    public function render()
    {
        return view('livewire.chat.show');
    }
}
?>

<div class="h-screen p-4">
    <livewire:chat-box :conversation="$conversation" />
</div>
```

Route it:

```php
// Livewire 4
Route::livewire('/chat/{user}', 'chat.show')->middleware('auth');

// Livewire 3
Route::get('/chat/{user}', \App\Livewire\Chat\Show::class)->middleware('auth');
```

---

## Chattable trait helpers

```php
// Start or resume a 1-on-1 chat
$conversation = $alice->chatWith($bob);

// Create a group
$conversation = $alice->startGroupChat('Dev Team', [$bob, $charlie]);

// Get all conversations (sorted by latest activity)
$conversations = $alice->conversations;

// Total unread count
$unread = $alice->unreadMessageCount();

// Unread in a specific conversation
$unread = $alice->unreadCountIn($conversation);
```

---

## Config reference — config/chat.php

```php
// Swap any model
'models' => [
    'message' => \App\Models\Chat\Message::class, // extend the base model
],

// Toggle features — disabled = hidden from UI + skipped in logic
'features' => [
    'attachments'      => true,
    'reactions'        => true,
    'read_receipts'    => true,
    'typing_indicator' => true,
    'group_chat'       => true,
    'message_editing'  => true,
],

// Disable broadcasting → falls back to wire:poll (no WebSocket needed)
'broadcasting' => [
    'enabled' => env('CHAT_BROADCASTING_ENABLED', false),
],
```

---

## Publish views to customize

```bash
php artisan vendor:publish --tag=chat-views
```

Views land in `resources/views/vendor/chat/livewire/chat-box.blade.php`.
Edit freely — the package uses your file automatically.

---

## Enable real-time broadcasting (optional)

Install Reverb (ships with Laravel 11+):

```bash
php artisan install:broadcasting
```

Update `.env`:

```env
BROADCAST_DRIVER=reverb
CHAT_BROADCASTING_ENABLED=true
```

Add channel auth to `routes/channels.php`:

```php
use UnseenCodes\Chat\Models\Conversation;
use UnseenCodes\Chat\Contracts\ChatManagerContract;

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    return $conversation
        && app(ChatManagerContract::class)->isParticipant($user, $conversation);
});
```

Without broadcasting the component auto-falls back to `wire:poll.5s` — works fine for most apps.

---

## Overriding a model

```php
// app/Models/Chat/Message.php
namespace App\Models\Chat;

use UnseenCodes\Chat\Models\Message as BaseMessage;

class Message extends BaseMessage
{
    public function isFlagged(): bool
    {
        return (bool) ($this->meta['flagged'] ?? false);
    }
}
```

Then in `config/chat.php`:

```php
'models' => [
    'message' => \App\Models\Chat\Message::class,
],
```

---

## Seeder (optional demo data)

```php
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;

$chat = app(ChatManagerContract::class);
$msgs = app(MessageServiceContract::class);

$conv = $chat->createPrivateConversation($alice, $bob);
$msgs->send($conv, $alice, 'Hey Bob!');
$msgs->send($conv, $bob, 'Hey Alice!');
```

---

## License

MIT © Unseen Codes
