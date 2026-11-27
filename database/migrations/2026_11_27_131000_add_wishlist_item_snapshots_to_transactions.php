<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('negotiations', function (Blueprint $table) {
            $table->string('offered_item_name')->nullable()->after('product_id');
        });

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->nullable()->change();
            $table->foreignId('wishlist_request_id')
                ->nullable()
                ->after('product_id')
                ->constrained('wishlist_requests', 'request_id')
                ->nullOnDelete();
            $table->string('item_name_snapshot')->nullable()->after('wishlist_request_id');
            $table->foreignId('negotiation_id')
                ->nullable()
                ->after('item_name_snapshot')
                ->constrained('negotiations', 'negotiation_id')
                ->nullOnDelete();
            $table->unique('negotiation_id', 'transaction_items_negotiation_unique');
        });
    }

    public function down(): void
    {
        if (DB::table('transaction_items')->whereNull('product_id')->exists()) {
            throw new RuntimeException('Cannot roll back wishlist snapshots while snapshot transaction items exist.');
        }

        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropUnique('transaction_items_negotiation_unique');
            $table->dropForeign(['negotiation_id']);
            $table->dropForeign(['wishlist_request_id']);
            $table->dropColumn(['negotiation_id', 'item_name_snapshot', 'wishlist_request_id']);
            $table->unsignedBigInteger('product_id')->nullable(false)->change();
        });

        Schema::table('negotiations', function (Blueprint $table) {
            $table->dropColumn('offered_item_name');
        });
    }
};
