<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · {{ \App\Models\Setting::get('clinic_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<main class="wrap" style="max-width: 420px; padding-top: 3rem">
    <h1>Entrar no painel</h1>
    <p class="muted">{{ \App\Models\Setting::get('clinic_name') }}</p>
    @include('partials.flash')
    <form class="card stack" method="post" action="{{ route('login') }}">
        @csrf
        <div>
            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>
        </div>
        <div>
            <label for="password">Senha</label>
            <input type="password" id="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="btn block">Entrar</button>
    </form>
</main>
</body>
</html>
