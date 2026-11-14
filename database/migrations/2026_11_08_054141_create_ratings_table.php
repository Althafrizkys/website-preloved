<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ratings', function (Blueprint $table) {
            $table->id('rating_id');
            $table->foreignId('transaction_id')->constrained('transactions', 'transaction_id')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('reviewed_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->tinyInteger('stars');
            $table->text('review')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
