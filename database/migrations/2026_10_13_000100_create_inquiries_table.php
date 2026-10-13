<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Contatos em triagem: alguém chamou no WhatsApp ou no Instagram e ainda
        // não tem horário. Quando vira agendamento, o contato sai do quadro.
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('guardian_name');
            $table->string('patient_name')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('channel', 20)->default('whatsapp');
            $table->string('note', 500)->nullable();
            $table->string('stage', 20)->default('new')->index(); // new | waiting
            $table->timestamp('closed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
