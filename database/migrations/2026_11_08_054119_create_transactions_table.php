<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            $table->foreignId('buyer_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('seller_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignId('meeting_point_id')->nullable()
                  ->constrained('campus_meeting_points', 'meeting_point_id')->nullOnDelete();
            $table->decimal('total_price', 12, 2);
            $table->decimal('agreed_discount', 12, 2)->default(0);
            $table->enum('payment_method', ['cod'])->default('cod');
            $table->enum('transaction_status', ['menunggu', 'dijadwalkan', 'selesai', 'dibatalkan'])
                  ->default('menunggu');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
