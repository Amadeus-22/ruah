<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Como foi pago e se a nota fiscal já saiu. A nota é emitida fora do
        // sistema; aqui fica só o registro, para ela saber o que falta.
        foreach (['appointments', 'patient_packages'] as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('payment_method', 20)->nullable();
                $table->string('invoice_number', 40)->nullable();
                $table->timestamp('invoiced_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['appointments', 'patient_packages'] as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['payment_method', 'invoice_number', 'invoiced_at']));
        }
    }
};
