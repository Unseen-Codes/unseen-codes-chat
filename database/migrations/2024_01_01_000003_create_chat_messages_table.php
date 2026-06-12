<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->unsignedBigInteger('sender_id');
            $table->uuid('reply_to_id')->nullable();
            $table->text('body')->nullable();
            $table->string('type')->default('text'); // text | file | system
            $table->json('meta')->nullable();
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('conversation_id')
                  ->references('id')
                  ->on('chat_conversations')
                  ->cascadeOnDelete();

            $table->foreign('sender_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            // Self-referencing FK added after table exists — see migration 3b
            $table->index(['conversation_id', 'created_at'], 'chat_messages_conv_created_idx');
        });

        // Add self-referencing FK separately to avoid issues on some DB drivers
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->foreign('reply_to_id')
                  ->references('id')
                  ->on('chat_messages')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['reply_to_id']);
        });

        Schema::dropIfExists('chat_messages');
    }
};
