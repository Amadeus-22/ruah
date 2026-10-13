@extends('layouts.admin')
@section('title', 'Agenda')
@section('content')
    <div class="row between">
        <h1>Agenda</h1>
        <a class="btn sm" href="{{ route('admin.appointments.create') }}">Novo horário</a>
    </div>
    <p class="muted small">Link para pacientes: <a href="{{ route('public.index') }}">{{ route('public.index') }}</a></p>

    @if ($pending->isNotEmpty())
        <h2>Pedidos aguardando confirmação ({{ $pending->count() }})</h2>
        @foreach ($pending as $appointment)
            @include('partials.appointment', ['appointment' => $appointment, 'showDate' => true])
        @endforeach
    @endif

    @if ($overdue->isNotEmpty())
        <h2>Já passaram: foi realizado ou faltou?</h2>
        @foreach ($overdue as $appointment)
            @include('partials.appointment', ['appointment' => $appointment, 'showDate' => true])
        @endforeach
    @endif

    @if ($endingPackages->isNotEmpty())
        <h2>Pacotes acabando</h2>
        <div class="card">
            @foreach ($endingPackages as $pp)
                <p><a href="{{ route('admin.patients.show', $pp->patient) }}">{{ $pp->patient->patient_name }}</a>
                    — {{ $pp->package->name }}: {{ $pp->remaining() === 1 ? 'resta 1 sessão' : 'restam '.$pp->remaining().' sessões' }}</p>
            @endforeach
        </div>
    @endif

    <h2>Próximos 8 dias</h2>
    @forelse ($upcoming as $date => $items)
        @php($day = \Carbon\CarbonImmutable::parse($date))
        <div class="day-title">
            {{ $day->isSameDay($today) ? 'Hoje' : ($day->isSameDay($today->addDay()) ? 'Amanhã' : $day->translatedFormat('l')) }},
            {{ $day->translatedFormat('j \d\e F') }}
        </div>
        @foreach ($items as $appointment)
            @include('partials.appointment', ['appointment' => $appointment])
        @endforeach
    @empty
        <div class="card muted">Nenhum atendimento confirmado nos próximos dias.</div>
    @endforelse
@endsection
