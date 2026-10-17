<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Foto e título que aparecem no menu do perfil.
        Schema::table('users', function (Blueprint $table) {
            $table->string('title', 80)->nullable()->after('name');
            $table->string('avatar_path')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['title', 'avatar_path']));
    }
};
