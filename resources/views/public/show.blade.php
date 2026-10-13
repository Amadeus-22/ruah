@extends('layouts.public')
@section('title', 'Seu pedido')
@section('content')
    @php($status = $appointment->status)
    @if ($status === \App\Enums\AppointmentStatus::Requested)
        <h1>Pedido enviado</h1>
        <p class="muted">Recebemos o seu pedido. Você recebe a confirmação pelo WhatsApp.</p>
    @elseif ($status === \App\Enums\AppointmentStatus::Canceled)
        <h1>Agendamento cancelado</h1>
    @else
        <h1>Horário confirmado</h1>
    @endif

    <div class="card">
        <p><span class="badge {{ $status->value }}">{{ $status->label() }}</span></p>
        <h3>{{ $appointment->service->name }}</h3>
        <p class="cap">{{ $appointment->starts_at->translatedFormat('l, j \d\e F') }}, às {{ $appointment->starts_at->format('H:i') }}</p>
        <p class="muted">Paciente: {{ $appointment->patient->patient_name }}</p>
        @if (\App\Models\Setting::get('clinic_address'))
            <p class="muted">Local: {{ \App\Models\Setting::get('clinic_address') }}</p>
        @endif
    </div>
    <p class="muted small">Guarde este endereço para acompanhar o pedido. Para remarcar ou cancelar, fale com a gente pelo WhatsApp.</p>
@endsection
