<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status')->default('present')->after('date'); // present|late|absent|leave
            $table->unsignedSmallInteger('late_minutes')->nullable()->after('status');

            $table->index(['date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropIndex(['date', 'status']);
            $table->dropColumn(['status', 'late_minutes']);
        });
    }
};
