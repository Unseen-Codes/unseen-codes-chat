<?php

return [

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Table Names
    |--------------------------------------------------------------------------
    */
    'table_names' => [
        'conversations'  => 'chat_conversations',
        'participants'   => 'chat_participants',
        'messages'       => 'chat_messages',
        'attachments'    => 'chat_attachments',
        'reactions'      => 'chat_reactions',
        'read_receipts'  => 'chat_read_receipts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Replace any model with your own extended version.
    | Your model MUST extend the corresponding package base model.
    */
    'models' => [
        'conversation' => \UnseenCodes\Chat\Models\Conversation::class,
        'participant'  => \UnseenCodes\Chat\Models\Participant::class,
        'message'      => \UnseenCodes\Chat\Models\Message::class,
        'attachment'   => \UnseenCodes\Chat\Models\Attachment::class,
        'reaction'     => \UnseenCodes\Chat\Models\Reaction::class,
        'read_receipt' => \UnseenCodes\Chat\Models\ReadReceipt::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    */
    'features' => [
        'attachments'      => true,
        'reactions'        => true,
        'read_receipts'    => true,
        'typing_indicator' => true,
        'group_chat'       => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    */
    'broadcasting' => [
        'enabled'  => env('CHAT_BROADCASTING_ENABLED', false),
        'driver'   => env('BROADCAST_DRIVER', 'reverb'),
        'presence' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'messages_per_page' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Attachments
    |--------------------------------------------------------------------------
    */
    'attachments' => [
        'disk'          => env('CHAT_ATTACHMENT_DISK', 'public'),
        'path'          => 'chat/attachments',
        'max_size_kb'   => 10240,
        'allowed_types' => ['image/*', 'application/pdf', 'video/mp4'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Messages
    |--------------------------------------------------------------------------
    */
    'messages' => [
        'max_length' => 5000,
        'soft_delete'=> true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto Migration
    |--------------------------------------------------------------------------
    */
    'auto_migrate' => false,

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'chat',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typing Indicator
    |--------------------------------------------------------------------------
    */
    'typing' => [
        'ttl_seconds' => 3,
        'cache_store' => env('CHAT_CACHE_STORE', 'file'),
    ],

];
