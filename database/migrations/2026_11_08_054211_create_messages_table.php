<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id('message_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->text('message_text');
            $table->dateTime('sent_at')->useCurrent();
            $table->dateTime('read_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
