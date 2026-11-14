<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id');
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('categories', 'category_id')->cascadeOnDelete();
            $table->string('product_name');
            $table->text('description');
            $table->decimal('price', 12, 2);
            $table->string('condition');
            $table->string('size')->nullable();
            $table->string('usage_period')->nullable();
            $table->text('defects')->nullable();
            $table->enum('status', ['tersedia', 'terjual'])->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
