<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_cria_o_login_com_a_senha_digitada(): void
    {
        $this->artisan('ruah:usuario', ['email' => 'pamela@exemplo.com', 'nome' => 'Pamela'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'uma-senha-forte-123')
            ->expectsQuestion('Repita a senha', 'uma-senha-forte-123')
            ->assertExitCode(0);

        $user = User::query()->where('email', 'pamela@exemplo.com')->firstOrFail();
        $this->assertTrue(Hash::check('uma-senha-forte-123', $user->password));
    }

    public function test_recusa_senha_curta_ou_diferente(): void
    {
        $this->artisan('ruah:usuario', ['email' => 'pamela@exemplo.com', 'nome' => 'Pamela'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'curta')
            ->assertExitCode(1);

        $this->artisan('ruah:usuario', ['email' => 'pamela@exemplo.com', 'nome' => 'Pamela'])
            ->expectsQuestion('Senha (mínimo 12 caracteres)', 'uma-senha-forte-123')
            ->expectsQuestion('Repita a senha', 'outra-senha-forte-123')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'pamela@exemplo.com']);
    }
}
