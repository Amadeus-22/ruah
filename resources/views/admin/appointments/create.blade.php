@extends('layouts.admin')
@section('title', 'Novo horário')
@section('content')
    <h1>Novo horário</h1>
    <p class="muted">Para lançar um atendimento combinado por mensagem. Ele já entra confirmado.</p>

    @if ($services->isEmpty())
        <div class="card">Cadastre um serviço primeiro, em <a href="{{ route('admin.catalog') }}">Serviços e pacotes</a>.</div>
    @else
        <form class="card row" method="get" action="{{ route('admin.appointments.create') }}">
            <div class="grow">
                <label for="service_id">Serviço</label>
                <select id="service_id" name="service_id" onchange="this.form.submit()">
                    @foreach ($services as $option)
                        <option value="{{ $option->id }}" @selected($option->id === $service->id)>{{ $option->name }} ({{ $option->duration_minutes }} min)</option>
                    @endforeach
                </select>
            </div>
            <div class="grow">
                <label for="day">Dia</label>
                <input type="date" id="day" name="day" value="{{ $day->toDateString() }}" onchange="this.form.submit()">
            </div>
            <div class="grow">
                <label for="patient_pick">Paciente já cadastrado</label>
                <select id="patient_pick" name="patient_id" onchange="this.form.submit()">
                    <option value="">Paciente novo</option>
                    @foreach ($patients as $option)
                        <option value="{{ $option->id }}" @selected($patient?->id === $option->id)>{{ $option->patient_name }} (resp. {{ $option->guardian_name }})</option>
                    @endforeach
                </select>
            </div>
            @if ($inquiry)<input type="hidden" name="inquiry_id" value="{{ $inquiry->id }}">@endif
            <noscript><button class="btn sm">Atualizar</button></noscript>
        </form>

        <form class="card stack" method="post" action="{{ route('admin.appointments.store') }}">
            @csrf
            <input type="hidden" name="service_id" value="{{ $service->id }}">
            @if ($patient)<input type="hidden" name="patient_id" value="{{ $patient->id }}">@endif
            @if ($inquiry)<input type="hidden" name="inquiry_id" value="{{ $inquiry->id }}">@endif

            <div>
                <label>Horário em <span class="cap">{{ $day->translatedFormat('l, j \d\e F') }}</span></label>
                @if ($slots === [])
                    <p class="muted">Sem horário livre neste dia. Confira os horários de atendimento e os bloqueios em Configurações.</p>
                @else
                    <div class="slots">
                        @foreach ($slots as $slot)
                            <label class="slot" style="cursor:pointer">
                                <input type="radio" name="start" value="{{ $slot->format('Y-m-d\TH:i') }}" required
                                       @checked(old('start') === $slot->format('Y-m-d\TH:i')) style="margin-right:.2rem">{{ $slot->format('H:i') }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>

            @if ($patient)
                <div>
                    <label>Paciente</label>
                    <p>{{ $patient->patient_name }} <span class="muted">· resp. {{ $patient->guardian_name }} · {{ $patient->phone }}</span></p>
                </div>
                @php($usable = $patient->packages->filter(fn ($pp) => $pp->remaining() > 0 && $pp->package->service_id === $service->id))
                @if ($usable->isNotEmpty())
                    <div>
                        <label for="patient_package_id">Usar sessão de pacote</label>
                        <select id="patient_package_id" name="patient_package_id">
                            <option value="">Não, atendimento avulso</option>
                            @foreach ($usable as $pp)
                                <option value="{{ $pp->id }}" selected>{{ $pp->package->name }} — restam {{ $pp->remaining() }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
            @else
                <div class="row">
                    <div class="grow">
                        <label for="guardian_name">Nome do responsável</label>
                        <input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $inquiry?->guardian_name) }}" required>
                    </div>
                    <div class="grow">
                        <label for="patient_name">Nome do paciente</label>
                        <input type="text" id="patient_name" name="patient_name" value="{{ old('patient_name', $inquiry?->patient_name) }}" required>
                    </div>
                    <div class="grow">
                        <label for="phone">WhatsApp, com DDD</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone', $inquiry?->phone) }}" required>
                    </div>
                </div>
            @endif

            <div style="max-width: 14rem">
                <label for="price">Valor combinado (avulso)</label>
                <input type="text" id="price" name="price" inputmode="decimal" value="{{ old('price', $service->suggested_price_cents ? number_format($service->suggested_price_cents / 100, 2, ',', '') : '') }}">
            </div>

            <button class="btn" @disabled($slots === [])>Agendar</button>
        </form>
    @endif
@endsection
