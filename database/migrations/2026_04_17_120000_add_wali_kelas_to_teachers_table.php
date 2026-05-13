<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->string('wali_kelas')->nullable()->after('subject');
        });
    }

    public function down(): void
    {
        Schema::table('teachers', function (Blueprint $table) {
            $table->dropColumn('wali_kelas');
        });
    }
};
