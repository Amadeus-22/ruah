<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Painel') · {{ \App\Models\Setting::get('clinic_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="top">
    <div class="wrap wide">
        <a class="name" href="{{ route('admin.dashboard') }}">{{ \App\Models\Setting::get('clinic_name') }}</a>
        <form class="inline" method="post" action="{{ route('logout') }}">@csrf<button class="btn sm ghost" style="color:#fff;border-color:#fff">Sair</button></form>
    </div>
</header>
<nav class="tabs" aria-label="Seções do painel">
    <div class="wrap wide">
        <a href="{{ route('admin.dashboard') }}" @class(['on' => request()->routeIs('admin.dashboard')])>Agenda</a>
        <a href="{{ route('admin.appointments.create') }}" @class(['on' => request()->routeIs('admin.appointments.*')])>Novo horário</a>
        <a href="{{ route('admin.patients.index') }}" @class(['on' => request()->routeIs('admin.patients.*')])>Pacientes</a>
        <a href="{{ route('admin.catalog') }}" @class(['on' => request()->routeIs('admin.catalog')])>Serviços e pacotes</a>
        <a href="{{ route('admin.settings') }}" @class(['on' => request()->routeIs('admin.settings')])>Configurações</a>
    </div>
</nav>
<main class="wrap wide">
    @include('partials.flash')
    @yield('content')
</main>
</body>
</html>
