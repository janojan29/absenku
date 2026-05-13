<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_profile_id')->constrained('student_profiles')->cascadeOnDelete();
            $table->enum('type', ['not_attend', 'early_leave']);
            $table->date('date');
            $table->text('reason');

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('reviewed_at')->nullable();
            $table->string('attachment')->nullable();

            $table->timestamps();

            $table->index(['date', 'type', 'status']);
            $table->index(['student_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_permissions');
    }
};
