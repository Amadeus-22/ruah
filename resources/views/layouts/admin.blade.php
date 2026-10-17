<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Painel') · {{ \App\Models\Setting::get('clinic_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <script>try { if (localStorage.getItem('ruah-panel') === 'closed') document.documentElement.classList.add('panel-closed'); } catch (e) {}</script>
</head>
<body class="admin">
@php
    use App\Support\Icon;
    $user = auth()->user();
    $clinic = \App\Models\Setting::get('clinic_name');
    $groups = [
        'Atendimento' => [
            ['admin.dashboard', 'Quadro', 'board', ['admin.dashboard']],
            ['admin.agenda', 'Agenda do dia', 'calendar', ['admin.agenda']],
            ['admin.appointments.create', 'Novo horário', 'plus-circle', ['admin.appointments.*']],
            ['admin.patients.index', 'Pacientes', 'users', ['admin.patients.*']],
        ],
        'Consultório' => [
            ['admin.overview', 'Visão geral', 'chart', ['admin.overview']],
            ['admin.finance', 'Financeiro', 'money', ['admin.finance*']],
            ['admin.catalog', 'Serviços e pacotes', 'package', ['admin.catalog']],
            ['admin.settings', 'Configurações', 'settings', ['admin.settings']],
        ],
        'Conta' => [
            ['admin.profile', 'Meu perfil', 'user', ['admin.profile*']],
        ],
    ];
    $rail = ['admin.dashboard' => ['Quadro', 'board'], 'admin.agenda' => ['Agenda', 'calendar'], 'admin.patients.index' => ['Pacientes', 'users'], 'admin.overview' => ['Visão geral', 'chart'], 'admin.finance' => ['Financeiro', 'money'], 'admin.settings' => ['Configurações', 'settings']];
    $isOn = fn (array $patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p));
@endphp
<div class="app">
    {{-- Trilho de ícones: atalhos que ficam sempre à vista --}}
    <nav class="rail" aria-label="Atalhos">
        <a class="rail-logo" href="{{ route('admin.dashboard') }}" title="{{ $clinic }}">{!! Icon::svg('leaf') !!}</a>
        @foreach ($rail as $route => [$label, $icon])
            <a href="{{ route($route) }}" title="{{ $label }}" @class(['on' => request()->routeIs($route) || ($route === 'admin.patients.index' && request()->routeIs('admin.patients.*'))])>{!! Icon::svg($icon) !!}<span class="visually-hidden">{{ $label }}</span></a>
        @endforeach
        <div class="spacer"></div>
        <a href="{{ route('admin.profile') }}" title="Meu perfil" class="rail-avatar"><x-avatar :user="$user" :size="34" /></a>
    </nav>

    {{-- Menu completo, com seções --}}
    <aside class="panel" id="panel" aria-label="Menu do painel">
        <div class="panel-brand">
            <span class="panel-mark">{!! Icon::svg('leaf') !!}</span>
            <div><strong>{{ $clinic }}</strong><small>Painel do consultório</small></div>
        </div>
        @foreach ($groups as $group => $items)
            <div class="panel-group">{{ $group }}</div>
            @foreach ($items as [$route, $label, $icon, $patterns])
                <a href="{{ route($route) }}" @class(['on' => $isOn($patterns)])>{!! Icon::svg($icon) !!}<span>{{ $label }}</span></a>
            @endforeach
        @endforeach
        <a href="{{ route('public.index') }}" target="_blank" rel="noopener">{!! Icon::svg('external') !!}<span>Página dos pacientes</span></a>
        <div class="spacer"></div>
        <form method="post" action="{{ route('logout') }}">@csrf
            <button class="panel-link">{!! Icon::svg('logout') !!}<span>Sair</span></button>
        </form>
    </aside>
    <div class="panel-backdrop" data-panel-toggle></div>

    <div class="content">
        <header class="topbar">
            <button class="icon-btn" type="button" data-panel-toggle aria-controls="panel" aria-label="Mostrar ou esconder o menu">{!! Icon::svg('menu') !!}</button>
            <div class="crumbs"><span>Painel</span><span aria-hidden="true">›</span><strong>@yield('title', 'Painel')</strong></div>
            <div class="topbar-actions">
                <a class="icon-btn" href="{{ route('admin.appointments.create') }}" title="Novo horário">{!! Icon::svg('plus-circle') !!}</a>
                <a class="icon-btn" href="{{ route('admin.agenda') }}" title="Agenda do dia">{!! Icon::svg('calendar') !!}</a>
                <a class="icon-btn" href="{{ route('public.index') }}" target="_blank" rel="noopener" title="Página dos pacientes">{!! Icon::svg('external') !!}</a>

                <details class="account">
                    <summary aria-label="Menu da conta"><x-avatar :user="$user" :size="36" />{!! Icon::svg('chevron-down', 'caret') !!}</summary>
                    <div class="account-menu">
                        <div class="account-card">
                            <x-avatar :user="$user" :size="56" />
                            <div>
                                <strong>{{ $user->name }}</strong>
                                <small>{{ $user->email }}</small>
                                <span class="pill">{{ $user->title ?: 'Fisioterapeuta' }}</span>
                            </div>
                        </div>
                        <div class="account-clinic">{!! Icon::svg('leaf') !!}<span>{{ $clinic }}</span></div>
                        <a href="{{ route('admin.profile') }}">{!! Icon::svg('user') !!} Meu perfil</a>
                        <a href="{{ route('admin.overview') }}">{!! Icon::svg('chart') !!} Visão geral</a>
                        <a href="{{ route('admin.settings') }}">{!! Icon::svg('settings') !!} Configurações</a>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="danger-link">{!! Icon::svg('logout') !!} Sair da conta</button></form>
                    </div>
                </details>
            </div>
        </header>
        <main class="main">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>
<script>
    // Abre e fecha o menu; no celular ele desliza por cima da página.
    document.querySelectorAll('[data-panel-toggle]').forEach((el) => el.addEventListener('click', () => {
        const root = document.documentElement;
        if (matchMedia('(max-width: 900px)').matches) { root.classList.toggle('panel-open'); return; }
        root.classList.toggle('panel-closed');
        try { localStorage.setItem('ruah-panel', root.classList.contains('panel-closed') ? 'closed' : 'open'); } catch (e) {}
    }));
    // Fecha o menu da conta ao clicar fora.
    document.addEventListener('click', (ev) => {
        document.querySelectorAll('details.account[open]').forEach((d) => { if (!d.contains(ev.target)) d.removeAttribute('open'); });
    });
</script>
@stack('scripts')
</body>
</html>
