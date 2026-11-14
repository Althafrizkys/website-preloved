<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wishlist_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->cascadeOnDelete();
            $table->string('item_name');
            $table->decimal('budget', 12, 2);
            $table->dateTime('expired_at');
            $table->enum('status', ['aktif', 'terpenuhi', 'kedaluwarsa'])->default('aktif');
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_requests');
    }
};
