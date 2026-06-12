<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_participants', function (Blueprint $table) {
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
                  ->on('chat_conversations')
                  ->cascadeOnDelete();

            $table->index(
                ['participantable_type', 'participantable_id'],
                'chat_participants_morphable_idx'
            );

            $table->unique(
                ['conversation_id', 'participantable_type', 'participantable_id'],
                'chat_participants_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_participants');
    }
};
