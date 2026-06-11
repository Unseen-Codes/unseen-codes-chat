<?php

use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Exceptions\ParticipantNotAllowedException;
use UnseenCodes\Chat\Models\Conversation;
use UnseenCodes\Chat\Models\Message;

beforeEach(function () {
    $this->alice = \App\Models\User::factory()->create(['name' => 'Alice']);
    $this->bob   = \App\Models\User::factory()->create(['name' => 'Bob']);
    $this->chat  = app(ChatManagerContract::class);
    $this->msgs  = app(MessageServiceContract::class);
});

it('creates a private conversation between two users', function () {
    $conv = $this->chat->createPrivateConversation($this->alice, $this->bob);

    expect($conv)->toBeInstanceOf(Conversation::class)
        ->and($conv->type)->toBe('private')
        ->and($conv->participants)->toHaveCount(2);
});

it('finds an existing conversation instead of creating a duplicate', function () {
    $conv1 = $this->chat->findOrCreatePrivateConversation($this->alice, $this->bob);
    $conv2 = $this->chat->findOrCreatePrivateConversation($this->alice, $this->bob);

    expect($conv1->id)->toBe($conv2->id);
    expect(Conversation::count())->toBe(1);
});

it('sends a message to a conversation', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $message = $this->msgs->send($conv, $this->alice, 'Hello Bob!');

    expect($message)->toBeInstanceOf(Message::class)
        ->and($message->body)->toBe('Hello Bob!')
        ->and($message->sender_id)->toBe($this->alice->id)
        ->and($message->conversation_id)->toBe($conv->id);
});

it('prevents non-participants from sending messages', function () {
    $charlie = \App\Models\User::factory()->create();
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);

    expect(fn () => $this->msgs->send($conv, $charlie, 'Sneaky!'))
        ->toThrow(ParticipantNotAllowedException::class);
});

it('soft-deletes a message', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $message = $this->msgs->send($conv, $this->alice, 'Delete me');

    $this->msgs->delete($message, $this->alice);

    expect(Message::find($message->id))->toBeNull();
    expect(Message::withTrashed()->find($message->id))->not->toBeNull();
});

it('cannot delete another user\'s message', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $message = $this->msgs->send($conv, $this->alice, 'Alice wrote this');

    expect(fn () => $this->msgs->delete($message, $this->bob))
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

it('toggles a reaction on and off', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $message = $this->msgs->send($conv, $this->alice, 'React to me');

    $this->msgs->react($message, $this->bob, '👍');
    expect($message->reactions()->count())->toBe(1);

    $this->msgs->react($message, $this->bob, '👍'); // toggle off
    expect($message->reactions()->count())->toBe(0);
});

it('marks messages as read', function () {
    $conv = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $this->msgs->send($conv, $this->alice, 'Read me');
    $this->msgs->send($conv, $this->alice, 'Read me too');

    $this->msgs->markAsRead($conv, $this->bob);

    expect($conv->messages()->whereHas('readReceipts',
        fn ($q) => $q->where('user_id', $this->bob->id)
    )->count())->toBe(2);
});

it('creates a group conversation', function () {
    $charlie = \App\Models\User::factory()->create(['name' => 'Charlie']);
    $conv    = $this->chat->createGroupConversation('Team Chat', $this->alice, [$this->bob, $charlie]);

    expect($conv->type)->toBe('group')
        ->and($conv->name)->toBe('Team Chat')
        ->and($conv->participants)->toHaveCount(3);
});

it('checks participant status correctly', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $charlie = \App\Models\User::factory()->create();

    expect($this->chat->isParticipant($this->alice, $conv))->toBeTrue();
    expect($this->chat->isParticipant($charlie, $conv))->toBeFalse();
});

it('replies to a message', function () {
    $conv    = $this->chat->createPrivateConversation($this->alice, $this->bob);
    $parent  = $this->msgs->send($conv, $this->alice, 'Original message');
    $reply   = $this->msgs->send($conv, $this->bob, 'This is a reply', $parent->id);

    expect($reply->reply_to_id)->toBe($parent->id)
        ->and($reply->replyTo->body)->toBe('Original message');
});
