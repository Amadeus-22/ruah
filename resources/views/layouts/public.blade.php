<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Agendamento') · {{ \App\Models\Setting::get('clinic_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="top">
    <div class="wrap"><a class="name" href="{{ route('public.index') }}">{{ \App\Models\Setting::get('clinic_name') }}</a></div>
</header>
<main class="wrap">
    @include('partials.flash')
    @yield('content')
</main>
<footer class="note">
    @if (\App\Models\Setting::get('clinic_address'))
        {{ \App\Models\Setting::get('clinic_address') }}
    @endif
    <div><a href="{{ route('public.privacy') }}">Privacidade</a> · <a href="{{ route('admin.dashboard') }}">Área da profissional</a></div>
</footer>
</body>
</html>
