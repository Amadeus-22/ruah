<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Quando o lembrete foi enviado pelo WhatsApp, para não mandar duas vezes.
        Schema::table('appointments', fn (Blueprint $table) => $table->timestamp('reminded_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn('reminded_at'));
    }
};
