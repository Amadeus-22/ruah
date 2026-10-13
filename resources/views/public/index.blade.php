@extends('layouts.public')
@section('title', 'Agendar')
@section('content')
    <h1>Agendar atendimento</h1>
    <p class="muted">Escolha o atendimento. Em seguida você vê os horários livres e envia o pedido; a confirmação chega pelo WhatsApp.</p>

    @forelse ($services as $service)
        <a class="card service" href="{{ route('public.slots', $service) }}">
            <span>
                <h3>{{ $service->name }}</h3>
                @if ($service->description)<p class="muted small">{{ $service->description }}</p>@endif
                <p class="muted small">{{ $service->duration_minutes }} minutos
                    @if (\App\Models\Setting::bool('show_prices') && $service->suggested_price_cents)
                        · {{ \App\Support\Money::format($service->suggested_price_cents) }}
                    @endif
                </p>
            </span>
            <span class="btn sm">Ver horários</span>
        </a>
    @empty
        <div class="card">Nenhum atendimento disponível para agendamento no momento.</div>
    @endforelse
@endsection
