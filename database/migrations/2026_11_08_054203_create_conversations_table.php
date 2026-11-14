<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id('conversation_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
