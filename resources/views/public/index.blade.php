@extends('layouts.public')
@section('title', 'Agendar')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/booking.css') }}?v={{ filemtime(public_path('css/pages/booking.css')) }}">
@endpush
@section('content')
<div class="page-booking">
    <header class="booking-head">
        <h1>Agendar atendimento</h1>
        <p class="muted">Escolha o atendimento. Em seguida você vê os horários livres e envia o pedido; a confirmação chega pelo WhatsApp.</p>
    </header>

    @forelse ($services as $service)
        <a class="card service" href="{{ route('public.slots', $service) }}">
            <span class="service-body">
                <h2 class="service-name">{{ $service->name }}</h2>
                @if ($service->description)<span class="muted small service-desc">{{ $service->description }}</span>@endif
                <span class="service-meta">
                    <span class="meta duration">{{ $service->duration_minutes }} minutos</span>
                    @if (\App\Models\Setting::bool('show_prices') && $service->suggested_price_cents)
                        <span class="meta price">{{ \App\Support\Money::format($service->suggested_price_cents) }}</span>
                    @endif
                </span>
            </span>
            <span class="btn service-cta">Ver horários</span>
        </a>
    @empty
        <div class="card">Nenhum atendimento disponível para agendamento no momento.</div>
    @endforelse
</div>
@endsection
