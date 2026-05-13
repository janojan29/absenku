<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->string('grade')->nullable()->after('name');
            $table->foreignId('academic_year_id')->nullable()->after('grade')
                ->constrained('academic_years')->nullOnDelete();
            $table->foreignId('homeroom_teacher_id')->nullable()->after('academic_year_id')
                ->constrained('teachers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('homeroom_teacher_id');
            $table->dropConstrainedForeignId('academic_year_id');
            $table->dropColumn(['grade']);
        });
    }
};
