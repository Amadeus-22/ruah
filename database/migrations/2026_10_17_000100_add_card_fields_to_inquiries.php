<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O cartão do quadro ganha prioridade, serviço de interesse e período preferido,
        // além de anotações e um histórico de movimentos.
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('priority', 10)->default('normal')->after('stage');
            $table->foreignId('service_id')->nullable()->after('priority')->constrained()->nullOnDelete();
            $table->string('preferred_period', 10)->nullable()->after('service_id');
        });

        Schema::create('inquiry_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('body', 1000);
            $table->timestamps();
        });

        Schema::create('inquiry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('description', 200);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_events');
        Schema::dropIfExists('inquiry_notes');
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['priority', 'preferred_period']);
        });
    }
};
