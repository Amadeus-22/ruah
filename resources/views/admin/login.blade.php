@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/login.css') }}?v={{ filemtime(public_path('css/pages/login.css')) }}">
@endpush
@section('title', 'Entrar')
<!doctype html>
<html lang="pt-BR">
<head>
    @include('partials.head', ['defaultTitle' => 'Entrar'])
</head>
<body class="page-login">
@php($clinic = \App\Models\Setting::get('clinic_name'))
<main class="wrap narrow">
    <img class="login-logo" src="{{ asset('icons/logo-full.png') }}" alt="{{ $clinic }}" width="480" height="392">
    <h1>Entrar no painel</h1>
    <p class="muted">{{ $clinic }}</p>
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
