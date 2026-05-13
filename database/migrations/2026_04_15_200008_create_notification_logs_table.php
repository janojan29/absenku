<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->string('type');
            $table->text('message');
            $table->string('wa_number');
            $table->enum('status', ['sent', 'failed'])->default('sent');
            $table->json('response_payload')->nullable();
            $table->timestamps();

            $table->index(['student_profile_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
