{{-- Cabeçalho comum aos dois layouts. Cada layout passa $defaultTitle; páginas definem @section('title').
     CSS de layout e de página entra por @push('styles'), depois dos estilos base. --}}
@php
    // ?v= muda quando o arquivo muda, para o webview não ficar com CSS velho em cache.
    $v = fn (string $path) => asset($path).(is_file(public_path($path)) ? '?v='.filemtime(public_path($path)) : '');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>@yield('title', $defaultTitle ?? 'Ruah') · {{ \App\Models\Setting::get('clinic_name') }}</title>
<meta name="theme-color" content="#517664">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Ruah">
<link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('icons/favicon-32.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('icons/apple-touch-icon.png') }}">
<link rel="stylesheet" href="{{ $v('css/fonts.css') }}">
<link rel="stylesheet" href="{{ $v('css/tokens.css') }}">
<link rel="stylesheet" href="{{ $v('css/base.css') }}">
<link rel="stylesheet" href="{{ $v('css/components.css') }}">
@stack('styles')
