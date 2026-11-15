<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_responses', function (Blueprint $table) {
            $table->id('response_id');
            $table->foreignId('request_id')->constrained('wishlist_requests', 'request_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()
                  ->constrained('products', 'product_id')->nullOnDelete();
            $table->text('response_message');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_responses');
    }
};
