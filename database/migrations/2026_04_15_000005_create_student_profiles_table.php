<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_room_id')->constrained('class_rooms');
            $table->string('nis')->nullable()->unique();
            $table->string('parent_whatsapp_number')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['class_room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
