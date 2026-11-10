{{-- prepend: o CSS do layout vem antes do CSS que cada página empurra com @push('styles') --}}
@prepend('styles')
    <link rel="stylesheet" href="{{ asset('css/layout-public.css') }}?v={{ @filemtime(public_path('css/layout-public.css')) }}">
@endprepend
<!doctype html>
<html lang="pt-BR">
<head>
    @include('partials.head', ['defaultTitle' => 'Agendamento'])
</head>
<body class="public">
@php($clinic = \App\Models\Setting::get('clinic_name'))
<header class="top">
    <div class="wrap">
        <a class="name" href="{{ route('public.index') }}">
            <img class="top-logo" src="{{ asset('icons/logo-horizontal.png') }}" alt="{{ $clinic }}" width="600" height="194">
        </a>
    </div>
</header>
<main class="wrap site-main">
    @include('partials.flash')
    @yield('content')
</main>
<footer class="note">
    @if (\App\Models\Setting::get('clinic_address'))
        {{ \App\Models\Setting::get('clinic_address') }}
    @endif
    <div><a href="{{ route('public.privacy') }}">Privacidade</a> · <a href="{{ route('admin.dashboard') }}">Área da profissional</a></div>
</footer>
@stack('scripts')
</body>
</html>
