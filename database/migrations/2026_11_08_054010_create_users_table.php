<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('user_id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('campus', ['UNSIKA', 'UBP']);
            $table->string('faculty');
            $table->string('cohort');
            $table->string('profile_photo')->nullable();
            $table->string('social_media_url')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->dateTime('email_verified_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
