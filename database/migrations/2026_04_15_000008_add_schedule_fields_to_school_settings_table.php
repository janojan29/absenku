<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->time('check_in_start_time')->default('07:00:00')->after('radius_meters');
            $table->time('check_in_end_time')->default('08:00:00')->after('check_in_start_time');
            $table->unsignedSmallInteger('late_tolerance_minutes')->default(15)->after('check_in_end_time');
        });
    }

    public function down(): void
    {
        Schema::table('school_settings', function (Blueprint $table) {
            $table->dropColumn(['check_in_start_time', 'check_in_end_time', 'late_tolerance_minutes']);
        });
    }
};
