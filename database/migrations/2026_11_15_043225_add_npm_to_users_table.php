<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // NPM hanya untuk UNSIKA; mahasiswa UBP tidak memakai NPM (nullable).
            // Nilai NULL boleh berulang pada kolom unique.
            $table->string('npm', 30)->nullable()->unique()->after('email');
            $table->string('name')->nullable()->change();
            $table->dropColumn('faculty');
        });
    }
    
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('faculty')->nullable()->after('campus');
            $table->dropUnique(['npm']);
            $table->dropColumn('npm');
            $table->string('name')->nullable(false)->change();
        });
    }
};