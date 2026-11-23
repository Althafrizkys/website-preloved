<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */

public function up(): void
{
    DB::table('products')->where('condition', 'Seperti baru')->update(['condition' => 'Seperti Baru']);
    DB::table('products')->whereNotIn('condition', ['Baru', 'Seperti Baru'])->update(['condition' => 'Bekas']);
}

public function down(): void
{
    // Tidak dapat dibalikkan: nilai kondisi lama (Baik/Cukup/Ada kekurangan) sudah dilebur ke "Bekas".
}
};
