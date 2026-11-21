<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('user_id')->constrained('products', 'product_id')->nullOnDelete();
            $table->foreignId('negotiation_id')->nullable()->after('product_id')->constrained('negotiations', 'negotiation_id')->nullOnDelete();
            $table->string('role')->default('penjual')->after('notification_type');
            $table->unsignedInteger('offered_price')->nullable()->after('message');
            $table->unsignedInteger('original_price')->nullable()->after('offered_price');
        });
    }

    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropForeign(['negotiation_id']);
            $table->dropColumn(['product_id', 'negotiation_id', 'role', 'offered_price', 'original_price']);
        });
    }
};
