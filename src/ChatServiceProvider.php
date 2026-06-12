<?php

namespace UnseenCodes\Chat;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Services\ChatManager;
use UnseenCodes\Chat\Services\MessageService;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chat.php', 'chat');

        $this->app->singleton(ChatManagerContract::class, ChatManager::class);
        $this->app->singleton(MessageServiceContract::class, MessageService::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'chat');

        if (config('chat.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/chat.php');
        }

        // Works with Livewire v3 and v4 — class-based registration
        // The view is the single Blade file in resources/views/livewire/chat-box.blade.php
        Livewire::component('chat-box', \UnseenCodes\Chat\Livewire\ChatBox::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/chat.php' => config_path('chat.php'),
            ], ['chat-config', 'chat']);

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], ['chat-migrations', 'chat']);

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/chat'),
            ], ['chat-views', 'chat']);
        }
    }
}
