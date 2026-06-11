<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('chat.table_names.attachments', 'chat_attachments'), function (Blueprint $table) {
            $table->id();
            $table->uuid('message_id');
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->foreign('message_id')
                  ->references('id')
                  ->on(config('chat.table_names.messages', 'chat_messages'))
                  ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('chat.table_names.attachments', 'chat_attachments'));
    }
};
