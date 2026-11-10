@extends('layouts.public')
@section('title', 'Seu pedido')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/booking.css') }}?v={{ filemtime(public_path('css/pages/booking.css')) }}">
@endpush
@section('content')
<div class="page-booking">
    @php($status = $appointment->status)
    <header class="booking-head">
        @if ($status === \App\Enums\AppointmentStatus::Requested)
            <h1>Pedido enviado</h1>
            <p class="muted">Recebemos o seu pedido. Você recebe a confirmação pelo WhatsApp.</p>
        @elseif ($status === \App\Enums\AppointmentStatus::Canceled)
            <h1>Agendamento cancelado</h1>
        @else
            <h1>Horário confirmado</h1>
        @endif
    </header>

    <div class="card summary">
        <p class="mt-0"><span class="badge {{ $status->value }}">{{ $status->label() }}</span></p>
        <h2 class="service-name">{{ $appointment->service->name }}</h2>
        <p class="when cap">{{ $appointment->starts_at->translatedFormat('l, j \d\e F') }}, às {{ $appointment->starts_at->format('H:i') }}</p>
        <p class="muted">Paciente: {{ $appointment->patient->patient_name }}</p>
        @if (\App\Models\Setting::get('clinic_address'))
            <p class="muted">Local: {{ \App\Models\Setting::get('clinic_address') }}</p>
        @endif
    </div>
    <p class="muted small">Guarde este endereço para acompanhar o pedido. Para remarcar ou cancelar, fale com a gente pelo WhatsApp.</p>
</div>
@endsection
