<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.participants', 'chat_participants'), function (Blueprint $table) {
            $table->id();
            $table->uuid('conversation_id');
            $table->string('participantable_type');
            $table->unsignedBigInteger('participantable_id');
            $table->string('role')->default('member'); // owner | admin | member
            $table->timestamp('last_read_at')->nullable();
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')
                  ->references('id')
                  ->on(config('chat.table_names.conversations', 'chat_conversations'))
                  ->cascadeOnDelete();

            $table->index(['participantable_type', 'participantable_id']);
            $table->unique(['conversation_id', 'participantable_id', 'participantable_type'], 'chat_participants_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.participants', 'chat_participants'));
    }
};
