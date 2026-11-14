<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negotiations', function (Blueprint $table) {
            $table->id('negotiation_id');
            $table->foreignId('conversation_id')->constrained('conversations', 'conversation_id')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users', 'user_id')->cascadeOnDelete();
            // tawaran sebelumnya (kalau ini tawaran balik), boleh kosong
            $table->foreignId('parent_offer_id')->nullable()
                  ->constrained('negotiations', 'negotiation_id')->nullOnDelete();
            $table->decimal('offered_price', 12, 2);
            $table->integer('quantity')->default(1);
            $table->enum('status', ['menunggu', 'diterima', 'ditolak', 'tawaran_balik'])->default('menunggu');
            $table->dateTime('created_at')->useCurrent();
            $table->dateTime('responded_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negotiations');
    }
};
