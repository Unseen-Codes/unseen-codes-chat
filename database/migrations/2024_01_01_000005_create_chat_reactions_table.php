<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.reactions', 'chat_reactions'), function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('emoji');
            $table->timestamps();

            $table->foreign('message_id')
                  ->references('id')
                  ->on(config('chat.table_names.messages', 'chat_messages'))
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unique(['message_id', 'user_id', 'emoji'], 'chat_reactions_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.reactions', 'chat_reactions'));
    }
};
