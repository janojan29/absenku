<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('student_profile_id')->nullable()->after('user_id')
                ->constrained('student_profiles')->nullOnDelete();
            $table->text('notes')->nullable()->after('status');

            $table->unique(['student_profile_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['student_profile_id', 'date']);
            $table->dropConstrainedForeignId('student_profile_id');
            $table->dropColumn(['notes']);
        });
    }
};
