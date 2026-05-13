<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_logs', function (Blueprint $table) {
            $table->id();
            $table->string('provider')->default('unknown');
            $table->string('to');
            $table->text('message');
            $table->string('status')->default('queued'); // queued|sent|failed
            $table->text('error')->nullable();
            $table->nullableMorphs('related');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['to']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_logs');
    }
};
