<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campus_meeting_points', function (Blueprint $table) {
            $table->id('meeting_point_id');
            $table->enum('campus', ['UNSIKA', 'UBP']);
            $table->string('point_name');
            $table->text('address_description');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campus_meeting_points');
    }
};
