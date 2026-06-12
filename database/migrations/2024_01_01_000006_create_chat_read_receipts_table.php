<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_read_receipts', function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamp('read_at')->useCurrent();
            $table->timestamps();

            $table->foreign('message_id')
                  ->references('id')
                  ->on('chat_messages')
                  ->cascadeOnDelete();

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->cascadeOnDelete();

            $table->unique(['message_id', 'user_id'], 'chat_read_receipts_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_read_receipts');
    }
};
