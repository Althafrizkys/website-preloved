<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('stock')->default(1);
            $table->boolean('is_negotiable')->default(true);
        });

        DB::table('products')
            ->where('status', 'terjual')
            ->update(['stock' => 0]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock', 'is_negotiable']);
        });
    }
};
