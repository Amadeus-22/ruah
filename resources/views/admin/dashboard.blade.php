@extends('layouts.admin')
@section('title', 'Agenda do dia')
@section('content')
    <div class="row between">
        <h1>Agenda do dia</h1>
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

    @if ($reminders->isNotEmpty())
        <h2>Lembretes para enviar ({{ $reminders->count() }})</h2>
        <div class="card">
            @foreach ($reminders as $appointment)
                <div class="row between" style="padding:.35rem 0">
                    <span><strong>{{ $appointment->patient->patient_name }}</strong>
                        <span class="muted small">{{ $appointment->starts_at->isToday() ? 'hoje' : 'amanhã' }} às {{ $appointment->starts_at->format('H:i') }} · resp. {{ $appointment->patient->guardian_name }}</span></span>
                    <form class="inline" method="post" target="_blank" action="{{ route('admin.appointments.remind', $appointment) }}">@csrf<button class="btn sm">Enviar pelo WhatsApp</button></form>
                </div>
            @endforeach
            <p class="muted small">O WhatsApp abre com a mensagem pronta; é só tocar em enviar. Recarregue esta página para o lembrete sair da lista.</p>
        </div>
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
