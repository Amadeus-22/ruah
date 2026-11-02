@extends('layouts.public')
@section('title', $service->name)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/booking.css') }}?v={{ filemtime(public_path('css/pages/booking.css')) }}">
@endpush
@section('content')
<div class="page-booking">
    <p class="small mt-0"><a class="back" href="{{ route('public.index') }}">← Outros atendimentos</a></p>
    <header class="booking-head">
        <h1>{{ $service->name }}</h1>
        <p class="muted">{{ $service->duration_minutes }} minutos. Toque em um horário para continuar.</p>
    </header>

    @forelse ($days as $entry)
        <section class="day">
            <h2 class="section-label cap">{{ $entry['day']->translatedFormat('l, j \d\e F') }}</h2>
            <div class="slot-grid">
                @foreach ($entry['slots'] as $slot)
                    <a class="slot-tile" href="{{ route('public.form', ['service' => $service, 'inicio' => $slot->format('Y-m-d\TH:i')]) }}">{{ $slot->format('H:i') }}</a>
                @endforeach
            </div>
        </section>
    @empty
        <div class="card">Não há horários livres nos próximos dias. Fale com a gente pelo WhatsApp.</div>
    @endforelse
</div>
@endsection
