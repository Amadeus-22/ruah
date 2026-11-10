<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

// Cria ou atualiza o login do painel. A senha é pedida no terminal, nunca vai para o histórico do shell.
Artisan::command('ruah:usuario {email} {nome}', function (string $email, string $nome) {
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('E-mail inválido.');

        return 1;
    }

    $senha = $this->secret('Senha (mínimo 12 caracteres)');
    if (! is_string($senha) || mb_strlen($senha) < 12) {
        $this->error('A senha precisa ter pelo menos 12 caracteres.');

        return 1;
    }
    if ($senha !== $this->secret('Repita a senha')) {
        $this->error('As senhas não conferem.');

        return 1;
    }

    $user = User::query()->updateOrCreate(['email' => $email], ['name' => $nome, 'password' => Hash::make($senha)]);
    $this->info(($user->wasRecentlyCreated ? 'Usuário criado: ' : 'Senha atualizada: ').$email);

    return 0;
})->purpose('Cria ou atualiza o login do painel');
