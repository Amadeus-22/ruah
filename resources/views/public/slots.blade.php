@extends('layouts.public')
@section('title', $service->name)
@section('content')
    <p class="small"><a href="{{ route('public.index') }}">← Outros atendimentos</a></p>
    <h1>{{ $service->name }}</h1>
    <p class="muted">{{ $service->duration_minutes }} minutos. Toque em um horário para continuar.</p>

    @forelse ($days as $entry)
        <div class="day-title">{{ $entry['day']->translatedFormat('l, j \d\e F') }}</div>
        <div class="slots">
            @foreach ($entry['slots'] as $slot)
                <a class="slot" href="{{ route('public.form', ['service' => $service, 'inicio' => $slot->format('Y-m-d\TH:i')]) }}">{{ $slot->format('H:i') }}</a>
            @endforeach
        </div>
    @empty
        <div class="card">Não há horários livres nos próximos dias. Fale com a gente pelo WhatsApp.</div>
    @endforelse
@endsection
