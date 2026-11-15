<?php

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
        Schema::table('users', function (Blueprint $table) {
            // Peran akun: 'user' (pembeli sekaligus penjual) atau 'admin'.
            $table->enum('role', ['user', 'admin'])->default('user')->after('password');

            // Admin bukan mahasiswa, jadi kampus dan angkatan boleh kosong.
            $table->enum('campus', ['UNSIKA', 'UBP'])->nullable()->change();
            $table->string('cohort')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
            $table->enum('campus', ['UNSIKA', 'UBP'])->nullable(false)->change();
            $table->string('cohort')->nullable(false)->change();
        });
    }
};