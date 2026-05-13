<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('class_rooms', 'jurusan')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                $table->string('jurusan', 100)->nullable()->after('name');
            });
        }

        // Backward compatibility for old unique(name) schema.
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropUnique('class_rooms_name_unique');
        });

        Schema::table('class_rooms', function (Blueprint $table) {
            $table->unique(['name', 'jurusan'], 'class_rooms_name_jurusan_unique');
        });
    }

    public function down(): void
    {
        Schema::table('class_rooms', function (Blueprint $table) {
            $table->dropUnique('class_rooms_name_jurusan_unique');
        });

        Schema::table('class_rooms', function (Blueprint $table) {
            $table->unique('name');
        });

        if (Schema::hasColumn('class_rooms', 'jurusan')) {
            Schema::table('class_rooms', function (Blueprint $table) {
                $table->dropColumn('jurusan');
            });
        }
    }
};
