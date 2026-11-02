{{-- prepend: o CSS do layout vem antes do CSS que cada página empurra com @push('styles') --}}
@prepend('styles')
    <link rel="stylesheet" href="{{ asset('css/layout-admin.css') }}?v={{ @filemtime(public_path('css/layout-admin.css')) }}">
@endprepend
<!doctype html>
<html lang="pt-BR">
<head>
    @include('partials.head', ['defaultTitle' => 'Painel'])
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
    $rail = [
        ['admin.dashboard', 'Quadro', 'board', ['admin.dashboard']],
        ['admin.agenda', 'Agenda', 'calendar', ['admin.agenda']],
        ['admin.patients.index', 'Pacientes', 'users', ['admin.patients.*']],
        ['admin.overview', 'Visão geral', 'chart', ['admin.overview']],
        ['admin.finance', 'Financeiro', 'money', ['admin.finance*']],
        ['admin.settings', 'Configurações', 'settings', ['admin.settings']],
    ];
    // Barra inferior do celular: os destinos mais usados; "Mais" abre o menu completo.
    $bottom = [
        ['admin.dashboard', 'Quadro', 'board', ['admin.dashboard']],
        ['admin.agenda', 'Agenda', 'calendar', ['admin.agenda']],
        ['admin.appointments.create', 'Novo', 'plus-circle', ['admin.appointments.*'], 'Novo horário'],
        ['admin.patients.index', 'Pacientes', 'users', ['admin.patients.*']],
    ];
    $isOn = fn (array $patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p));
    $moreOn = ! collect($bottom)->contains(fn ($b) => $isOn($b[3]));
    $symbol = asset('icons/symbol-128.png');
@endphp
<div class="app">
    {{-- Trilho de ícones (tela grande): atalhos que ficam sempre à vista --}}
    <nav class="rail" aria-label="Atalhos">
        <a class="rail-logo" href="{{ route('admin.dashboard') }}" title="{{ $clinic }}"><img src="{{ $symbol }}" alt="{{ $clinic }}" width="40" height="40"></a>
        @foreach ($rail as [$route, $label, $icon, $patterns])
            <a href="{{ route($route) }}" title="{{ $label }}" aria-label="{{ $label }}" @class(['on' => $isOn($patterns)]) @if ($isOn($patterns)) aria-current="page" @endif>{!! Icon::svg($icon) !!}</a>
        @endforeach
        <div class="spacer"></div>
        <a href="{{ route('admin.profile') }}" title="Meu perfil" aria-label="Meu perfil" class="rail-avatar"><x-avatar :user="$user" :size="36" /></a>
    </nav>

    {{-- Menu completo, com seções. Tela grande: painel lateral recolhível. Celular: folha que sobe de baixo ("Mais"). --}}
    <aside class="panel" id="panel" aria-label="Menu do painel">
        <div class="panel-brand">
            <img class="panel-logo" src="{{ asset('icons/logo-horizontal.png') }}" alt="{{ $clinic }}" width="600" height="194">
            <small>Painel do consultório</small>
            <button class="panel-close" type="button" data-panel-close aria-label="Fechar o menu">{!! Icon::svg('x-circle') !!}</button>
        </div>
        @foreach ($groups as $group => $items)
            <div class="panel-group">{{ $group }}</div>
            @foreach ($items as [$route, $label, $icon, $patterns])
                <a href="{{ route($route) }}" @class(['on' => $isOn($patterns)]) @if ($isOn($patterns)) aria-current="page" @endif>{!! Icon::svg($icon) !!}<span>{{ $label }}</span></a>
            @endforeach
        @endforeach
        <a href="{{ route('public.index') }}" target="_blank" rel="noopener">{!! Icon::svg('external') !!}<span>Página dos pacientes</span></a>
        <div class="spacer"></div>
        <form method="post" action="{{ route('logout') }}">@csrf
            <button class="panel-link">{!! Icon::svg('logout') !!}<span>Sair</span></button>
        </form>
    </aside>
    <div class="panel-backdrop" data-panel-close></div>

    <div class="content">
        <header class="topbar">
            <button class="icon-btn panel-toggle" type="button" data-panel-toggle aria-controls="panel" aria-label="Mostrar ou esconder o menu">{!! Icon::svg('menu') !!}</button>
            <a class="topbar-brand" href="{{ route('admin.dashboard') }}">
                <img src="{{ $symbol }}" alt="" width="36" height="36">
                <span>{{ $clinic }}</span>
            </a>
            <div class="crumbs"><span>Painel</span><span aria-hidden="true">›</span><strong>@yield('title', 'Painel')</strong></div>
            <div class="topbar-actions">
                <a class="icon-btn" href="{{ route('admin.appointments.create') }}" title="Novo horário" aria-label="Novo horário">{!! Icon::svg('plus-circle') !!}</a>
                <a class="icon-btn" href="{{ route('admin.agenda') }}" title="Agenda do dia" aria-label="Agenda do dia">{!! Icon::svg('calendar') !!}</a>
                <a class="icon-btn" href="{{ route('public.index') }}" target="_blank" rel="noopener" title="Página dos pacientes" aria-label="Página dos pacientes">{!! Icon::svg('external') !!}</a>

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
                        <div class="account-clinic"><img src="{{ $symbol }}" alt="" width="20" height="20"><span>{{ $clinic }}</span></div>
                        <a href="{{ route('admin.profile') }}">{!! Icon::svg('user') !!} Meu perfil</a>
                        <a href="{{ route('admin.overview') }}">{!! Icon::svg('chart') !!} Visão geral</a>
                        <a href="{{ route('admin.settings') }}">{!! Icon::svg('settings') !!} Configurações</a>
                        <form method="post" action="{{ route('logout') }}">@csrf<button class="danger-link">{!! Icon::svg('logout') !!} Sair da conta</button></form>
                    </div>
                </details>
            </div>
        </header>
        <main class="main" id="conteudo">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>

    {{-- Navegação inferior (celular) --}}
    <nav class="bottomnav" aria-label="Navegação principal">
        @foreach ($bottom as $item)
            @php([$route, $label, $icon, $patterns] = $item)
            <a href="{{ route($route) }}" @class(['on' => $isOn($patterns), 'bottomnav-cta' => $icon === 'plus-circle']) @isset($item[4]) aria-label="{{ $item[4] }}" @endisset @if ($isOn($patterns)) aria-current="page" @endif>
                <span class="bottomnav-icon">{!! Icon::svg($icon) !!}</span><span class="bottomnav-label">{{ $label }}</span>
            </a>
        @endforeach
        <button type="button" @class(['on' => $moreOn]) data-panel-toggle aria-controls="panel" aria-expanded="false">
            <span class="bottomnav-icon">{!! Icon::svg('menu') !!}</span><span class="bottomnav-label">Mais</span>
        </button>
    </nav>
</div>
<script>
    (() => {
        const root = document.documentElement;
        const mobile = matchMedia('(max-width: 899.98px)');
        const sheetToggles = document.querySelectorAll('.bottomnav [data-panel-toggle]');
        const setSheet = (open) => {
            root.classList.toggle('panel-open', open);
            sheetToggles.forEach((b) => b.setAttribute('aria-expanded', String(open)));
            if (open) document.querySelector('#panel a, #panel button')?.focus({ preventScroll: true });
        };
        // Tela grande: recolhe o painel lateral e lembra a escolha. Celular: abre a folha "Mais".
        document.querySelectorAll('[data-panel-toggle]').forEach((el) => el.addEventListener('click', () => {
            if (mobile.matches) { setSheet(!root.classList.contains('panel-open')); return; }
            root.classList.toggle('panel-closed');
            try { localStorage.setItem('ruah-panel', root.classList.contains('panel-closed') ? 'closed' : 'open'); } catch (e) {}
        }));
        document.querySelectorAll('[data-panel-close]').forEach((el) => el.addEventListener('click', () => setSheet(false)));
        document.addEventListener('keydown', (ev) => {
            if (ev.key !== 'Escape') return;
            if (root.classList.contains('panel-open')) setSheet(false);
            document.querySelectorAll('details.account[open]').forEach((d) => d.removeAttribute('open'));
        });
        mobile.addEventListener('change', () => setSheet(false));
        // Fecha o menu da conta ao clicar fora.
        document.addEventListener('click', (ev) => {
            document.querySelectorAll('details.account[open]').forEach((d) => { if (!d.contains(ev.target)) d.removeAttribute('open'); });
        });
    })();
</script>
@stack('scripts')
</body>
</html>
