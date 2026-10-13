<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Painel') · {{ \App\Models\Setting::get('clinic_name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
@php
    $icon = fn (string $d) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $nav = [
        ['admin.dashboard', 'Quadro', '<rect x="3" y="4" width="5" height="16" rx="1.5"/><rect x="10" y="4" width="5" height="10" rx="1.5"/><rect x="17" y="4" width="4" height="13" rx="1.5"/>'],
        ['admin.agenda', 'Agenda do dia', '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>'],
        ['admin.appointments.create', 'Novo horário', '<circle cx="12" cy="12" r="9"/><path d="M12 8v8M8 12h8"/>'],
        ['admin.patients.index', 'Pacientes', '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.6-3.4 3.2-5.5 6.5-5.5s5.900 2.100 6.500 5.500M16 5.200a3.500 3.500 0 0 1 0 6.600M18.500 14.800c1.700.800 2.800 2.600 3 5.200"/>'],
        ['admin.finance', 'Financeiro', '<path d="M12 3v18M16.500 7.500c-.700-1.300-2.300-2-4.500-2-2.700 0-4.500 1.200-4.500 3.200 0 4.600 9.500 1.900 9.500 6.600 0 2-1.900 3.200-5 3.200-2.500 0-4.200-.800-5-2.300"/>'],
        ['admin.catalog', 'Serviços e pacotes', '<path d="M4 7h16M4 12h16M4 17h10"/>'],
        ['admin.settings', 'Configurações', '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3M4.900 4.900l2.100 2.100M17 17l2.100 2.100M19.100 4.900 17 7M7 17l-2.100 2.100"/>'],
    ];
@endphp
<div class="shell">
    <aside class="side" aria-label="Menu do painel">
        <div class="brand">{{ \App\Models\Setting::get('clinic_name') }}</div>
        <div class="group">Atendimento</div>
        @foreach (array_slice($nav, 0, 4) as [$route, $label, $path])
            <a href="{{ route($route) }}" @class(['on' => request()->routeIs($route) || ($route === 'admin.patients.index' && request()->routeIs('admin.patients.*'))])>{!! $icon($path) !!}<span>{{ $label }}</span></a>
        @endforeach
        <div class="group">Consultório</div>
        @foreach (array_slice($nav, 4) as [$route, $label, $path])
            <a href="{{ route($route) }}" @class(['on' => request()->routeIs($route)])>{!! $icon($path) !!}<span>{{ $label }}</span></a>
        @endforeach
        <a href="{{ route('public.index') }}" target="_blank" rel="noopener">{!! $icon('<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>') !!}<span>Página dos pacientes</span></a>
        <div class="spacer"></div>
        <form method="post" action="{{ route('logout') }}">@csrf
            <button class="link">{!! $icon('<path d="M10 4H5a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h5M15 8l4 4-4 4M19 12H9"/>') !!}<span>Sair</span></button>
        </form>
    </aside>
    <main class="main">
        @include('partials.flash')
        @yield('content')
    </main>
</div>
</body>
</html>
