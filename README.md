# unseen-codes/chat

A plug-and-play Livewire chat system for Laravel 11+.

[![Laravel](https://img.shields.io/badge/Laravel-11+-red.svg)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3+-blue.svg)](https://livewire.laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-purple.svg)](https://php.net)

---

## Features

- 1-on-1 and group conversations
- Real-time messaging via Livewire (polling or WebSocket broadcast)
- Emoji reactions (toggleable)
- Reply-to threading
- File attachments
- Read receipts with ✓✓ indicators
- Typing indicator
- Soft-deleted messages
- Fully config-driven — swap any model, table, or view
- Zero hardcoded `App\Models\User`

---

## Requirements

- PHP 8.2+
- Laravel 11-13
- Livewire 3-4

---

## Installation

```bash
composer require unseen-codes/chat
```

### 1. Publish config and run migrations

```bash
php artisan vendor:publish --tag=chat-config
php artisan migrate
```

### 2. Add the `Chattable` trait to your User model

```php
// app/Models/User.php
use UnseenCodes\Chat\Traits\Chattable;

class User extends Authenticatable
{
    use Chattable;
}
```

### 3. (Optional) Publish views to customize them

```bash
php artisan vendor:publish --tag=chat-views
```

---

## Basic Usage

### Start or resume a conversation

```php
// In a controller
use UnseenCodes\Chat\Contracts\ChatManagerContract;

public function show(User $otherUser, ChatManagerContract $chat)
{
    $conversation = $chat->findOrCreatePrivateConversation(auth()->user(), $otherUser);

    return view('chat.show', compact('conversation'));
}
```

### Drop the component in any Blade view

```blade
{{-- resources/views/chat/show.blade.php --}}
<div class="h-[600px]">
    <livewire:chat-box :conversation="$conversation" />
</div>
```

That's it. The component handles sending, loading, reactions, replies, and read receipts.

---

## Using the Chattable Trait Helpers

```php
// Start a 1-on-1 chat
$conversation = $alice->chatWith($bob);

// Create a group chat
$conversation = $alice->startGroupChat('Dev Team', [$bob, $charlie]);

// Get all conversations for a user
$conversations = $alice->conversations;

// Unread count badge
$unread = $alice->unreadMessageCount();

// Unread in a specific conversation
$unread = $alice->unreadCountIn($conversation);
```

---

## Using Services Directly

```php
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;

// Inject via constructor or use app()
$chat = app(ChatManagerContract::class);
$msgs = app(MessageServiceContract::class);

// Create conversations
$conv = $chat->createPrivateConversation($alice, $bob);
$conv = $chat->createGroupConversation('Team', $alice, [$bob, $charlie]);

// Send a message
$message = $msgs->send($conv, auth()->user(), 'Hello!');

// Send with reply
$message = $msgs->send($conv, auth()->user(), 'Agreed!', replyToId: $originalMessage->id);

// React
$msgs->react($message, auth()->user(), '👍');

// Mark conversation as read
$msgs->markAsRead($conv, auth()->user());

// Edit / delete
$msgs->edit($message, auth()->user(), 'Updated body');
$msgs->delete($message, auth()->user());
```

---

## Configuration

Publish and edit `config/chat.php` to control everything:

```php
// Swap any model
'models' => [
    'message' => \App\Models\Chat\Message::class,
],

// Toggle features
'features' => [
    'attachments'      => true,
    'reactions'        => true,
    'read_receipts'    => true,
    'typing_indicator' => true,
    'group_chat'       => true,
],

// Enable/disable broadcasting (WebSockets)
'broadcasting' => [
    'enabled' => env('CHAT_BROADCASTING_ENABLED', false),
],
```

---

## Overriding Models

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

## Broadcasting (Real-time)

Enable in `.env`:

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
    return $conversation && app(ChatManagerContract::class)->isParticipant($user, $conversation);
});
```

Without broadcasting, the component uses Livewire polling (`wire:poll`) as a fallback — no WebSocket server needed.

---

## Disabling Features

```php
'features' => [
    'attachments'      => false,  // Hides file upload button
    'reactions'        => false,  // Removes emoji reaction bar
    'read_receipts'    => false,  // No ✓✓ indicators
    'typing_indicator' => false,  // No "is typing..." bubble
    'group_chat'       => false,  // Only 1-on-1 conversations
],
```

---

## License

MIT © Unseen Codes
