@extends('layouts.public')
@section('title', 'Seus dados')
@section('content')
    <p class="small"><a href="{{ route('public.slots', $service) }}">← Trocar horário</a></p>
    <h1>Confirme seus dados</h1>
    <div class="card">
        <h3>{{ $service->name }}</h3>
        <p class="cap">{{ $start->translatedFormat('l, j \d\e F') }}, às {{ $start->format('H:i') }}</p>
    </div>

    <form class="card stack" method="post" action="{{ route('public.store', $service) }}">
        @csrf
        <input type="hidden" name="inicio" value="{{ $start->format('Y-m-d\TH:i') }}">
        <div>
            <label for="guardian_name">Nome do responsável</label>
            <input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}" autocomplete="name" required>
        </div>
        <div>
            <label for="patient_name">Nome do paciente</label>
            <input type="text" id="patient_name" name="patient_name" value="{{ old('patient_name') }}" required>
            <p class="muted small">Se o atendimento é para você, repita o seu nome.</p>
        </div>
        <div>
            <label for="phone">WhatsApp, com DDD</label>
            <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" placeholder="(21) 99999-9999" required>
        </div>
        <div>
            <label for="note">Observação (opcional)</label>
            <textarea id="note" name="note" maxlength="500">{{ old('note') }}</textarea>
        </div>
        <label class="check">
            <input type="checkbox" name="consent" value="1" @checked(old('consent')) required>
            <span>Concordo que estes dados sejam usados para marcar e confirmar o atendimento. <a href="{{ route('public.privacy') }}" target="_blank" rel="noopener">Como os dados são usados</a></span>
        </label>
        <button class="btn block">Enviar pedido de horário</button>
        <p class="muted small">O horário fica reservado e é confirmado pela profissional.</p>
    </form>
@endsection
