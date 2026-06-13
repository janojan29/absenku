<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->float('check_in_accuracy')->nullable()->after('check_in_distance_meters');
            $table->float('check_out_accuracy')->nullable()->after('check_out_distance_meters');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['check_in_accuracy', 'check_out_accuracy']);
        });
    }
};
