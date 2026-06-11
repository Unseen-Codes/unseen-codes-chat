<?php

namespace UnseenCodes\Chat;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use UnseenCodes\Chat\Contracts\ChatManagerContract;
use UnseenCodes\Chat\Contracts\MessageServiceContract;
use UnseenCodes\Chat\Livewire\ChatBox;
use UnseenCodes\Chat\Services\ChatManager;
use UnseenCodes\Chat\Services\MessageService;

class ChatServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/chat.php', 'chat');

        $this->app->singleton(ChatManagerContract::class, ChatManager::class);
        $this->app->singleton(MessageServiceContract::class, MessageService::class);

        $this->app->alias(ChatManagerContract::class, 'chat.manager');
        $this->app->alias(MessageServiceContract::class, 'chat.messages');
    }

    public function boot(): void
    {
        $this->registerPublishables();
        $this->registerMigrations();
        $this->registerRoutes();
        $this->registerLivewireComponents();
        $this->registerViews();
    }

    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../config/chat.php' => config_path('chat.php'),
        ], 'chat-config');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/chat'),
        ], 'chat-views');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'chat-migrations');

        $this->publishes([
            __DIR__ . '/../resources/css/chat.css' => public_path('vendor/chat/chat.css'),
        ], 'chat-assets');
    }

    protected function registerMigrations(): void
    {
        if (config('chat.auto_migrate', false)) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerRoutes(): void
    {
        if (config('chat.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../routes/chat.php');
        }
    }

    protected function registerLivewireComponents(): void
    {
        Livewire::component('chat-box', ChatBox::class);
    }

    protected function registerViews(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'chat');
    }
}
