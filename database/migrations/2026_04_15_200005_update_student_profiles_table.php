<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->string('parent_name')->nullable()->after('nis');
            $table->string('parent_phone_wa')->nullable()->after('parent_name');
            $table->string('photo')->nullable()->after('parent_phone_wa');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['parent_name', 'parent_phone_wa', 'photo']);
        });
    }
};
