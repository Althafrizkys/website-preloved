<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('subtotal_price', 12, 2)->nullable()->after('meeting_point_id');
            $table->decimal('service_fee', 12, 2)->default(2000)->after('agreed_discount');
            $table->text('buyer_notes')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['subtotal_price', 'service_fee', 'buyer_notes']);
        });
    }
};
