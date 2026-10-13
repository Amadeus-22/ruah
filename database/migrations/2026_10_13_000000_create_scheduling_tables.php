<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes');
            // Valor sugerido: a cobrança é combinada caso a caso, então cada
            // agendamento guarda o seu próprio valor.
            $table->unsignedInteger('suggested_price_cents')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sessions');
            $table->unsignedInteger('price_cents')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            // Quem agenda costuma ser o responsável, não o paciente.
            $table->string('guardian_name');
            $table->string('patient_name');
            $table->string('phone', 20)->index();
            $table->date('birth_date')->nullable();
            // Observações de agenda, não clínicas: nada de diagnóstico ou evolução.
            $table->string('notes', 500)->nullable();
            $table->timestamps();
        });

        Schema::create('patient_packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sessions_total');
            $table->unsignedSmallInteger('sessions_used')->default(0);
            $table->unsignedInteger('price_cents')->nullable();
            $table->date('purchased_on');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('availability_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('weekday'); // 0 = domingo ... 6 = sábado
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        // Períodos ocupados fora do sistema: compromissos pessoais, férias. Hoje
        // são lançados à mão; a sincronização com o calendário vai gravar aqui.
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->string('reason')->nullable();
            $table->string('source', 20)->default('manual');
            $table->string('external_id')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_package_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at')->index();
            $table->string('status', 20)->default('requested')->index();
            $table->unsignedInteger('price_cents')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('request_note', 500)->nullable();
            // O responsável acompanha o pedido por este código, sem precisar de conta.
            $table->string('public_token', 40)->unique();
            $table->string('calendar_event_id')->nullable();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['settings', 'appointments', 'blocks', 'availability_rules', 'patient_packages', 'patients', 'packages', 'services'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
