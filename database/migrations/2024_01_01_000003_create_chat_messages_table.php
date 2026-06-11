<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.messages', 'chat_messages'), function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->uuid('reply_to_id')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->default('text'); // text | image | file | system
            $table->json('meta')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')
                  ->references('id')
                  ->on(config('chat.table_names.conversations', 'chat_conversations'))
                  ->cascadeOnDelete();

            $table->foreign('sender_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->foreign('reply_to_id')
                  ->references('id')
                  ->on(config('chat.table_names.messages', 'chat_messages'))
                  ->nullOnDelete();

            $table->index(['conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.messages', 'chat_messages'));
    }
};
