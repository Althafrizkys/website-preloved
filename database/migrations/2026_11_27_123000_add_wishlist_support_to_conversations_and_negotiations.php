<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('wishlist_request_id')
                ->nullable()
                ->after('product_id')
                ->constrained('wishlist_requests', 'request_id')
                ->cascadeOnDelete();
            $table->unique(
                ['wishlist_request_id', 'buyer_id', 'seller_id'],
                'conversations_wishlist_participants_unique',
            );
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('conversation_id')
                ->constrained('products', 'product_id')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('conversations')
            ->whereNotNull('wishlist_request_id')
            ->delete();

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropUnique('conversations_wishlist_participants_unique');
            $table->dropForeign(['wishlist_request_id']);
            $table->dropColumn('wishlist_request_id');
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });
    }
};
