<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_media', function (Blueprint $table) {
            $table->id('media_id');
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->enum('media_type', ['foto', 'video']);
            $table->string('media_url');
            $table->integer('file_size');
            $table->integer('display_order')->default(1);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_media');
    }
};
