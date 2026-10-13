@extends('layouts.admin')
@section('title', 'Configurações')
@section('content')
    <h1>Configurações</h1>

    <h2>Horários de atendimento</h2>
    <div class="card">
        @foreach (\App\Models\AvailabilityRule::WEEKDAYS as $weekday => $name)
            <div class="row" style="padding:.3rem 0; border-bottom:1px solid var(--line)">
                <strong style="width:5.5rem">{{ $name }}</strong>
                @forelse ($rules[$weekday] ?? [] as $rule)
                    <form class="inline" method="post" action="{{ route('admin.rules.remove', $rule) }}">
                        @csrf @method('delete')
                        <span class="badge confirmed">{{ substr($rule->starts_at, 0, 5) }}–{{ substr($rule->ends_at, 0, 5) }}
                            <button style="border:0;background:none;color:inherit;cursor:pointer" aria-label="Remover {{ $name }} {{ substr($rule->starts_at, 0, 5) }}">✕</button></span>
                    </form>
                @empty
                    <span class="muted small">não atende</span>
                @endforelse
            </div>
        @endforeach
        <form class="row" method="post" action="{{ route('admin.rules.add') }}" style="margin-top:.8rem">
            @csrf
            <div class="grow">
                <label for="weekday">Dia</label>
                <select id="weekday" name="weekday">@foreach (\App\Models\AvailabilityRule::WEEKDAYS as $weekday => $name)<option value="{{ $weekday }}" @selected($weekday === 1)>{{ $name }}</option>@endforeach</select>
            </div>
            <div style="width:8rem"><label for="r-start">Das</label><input type="time" id="r-start" name="starts_at" value="08:00" required></div>
            <div style="width:8rem"><label for="r-end">Até</label><input type="time" id="r-end" name="ends_at" value="12:00" required></div>
            <button class="btn sm">Adicionar</button>
        </form>
    </div>

    <h2>Bloqueios</h2>
    <p class="muted small">Compromissos pessoais, folgas, férias. Enquanto a agenda do celular não está ligada ao sistema, é aqui que você fecha um horário.</p>
    <div class="card">
        @forelse ($blocks as $block)
            <div class="row between" style="padding:.3rem 0">
                <span>{{ $block->starts_at->format('d/m H:i') }} até {{ $block->ends_at->format('d/m H:i') }} <span class="muted">{{ $block->reason }}</span></span>
                <form class="inline" method="post" action="{{ route('admin.blocks.remove', $block) }}">@csrf @method('delete')<button class="btn sm quiet">Remover</button></form>
            </div>
        @empty
            <p class="muted">Nenhum bloqueio futuro.</p>
        @endforelse
        <form class="row" method="post" action="{{ route('admin.blocks.add') }}" style="margin-top:.8rem">
            @csrf
            <div class="grow"><label for="b-start">Início</label><input type="datetime-local" id="b-start" name="starts_at" required></div>
            <div class="grow"><label for="b-end">Fim</label><input type="datetime-local" id="b-end" name="ends_at" required></div>
            <div class="grow"><label for="b-reason">Motivo (opcional)</label><input type="text" id="b-reason" name="reason"></div>
            <button class="btn sm">Bloquear</button>
        </form>
    </div>

    <h2>Regras da agenda</h2>
    <form class="card stack" method="post" action="{{ route('admin.settings.save') }}">
        @csrf
        <div class="row">
            <div class="grow"><label for="clinic_name">Nome do consultório</label><input type="text" id="clinic_name" name="clinic_name" value="{{ old('clinic_name', $settings['clinic_name']) }}" required></div>
            <div class="grow"><label for="clinic_address">Endereço</label><input type="text" id="clinic_address" name="clinic_address" value="{{ old('clinic_address', $settings['clinic_address']) }}"></div>
        </div>
        <div class="row">
            <div class="grow"><label for="slot_step_minutes">Horários começam a cada (minutos)</label><input type="number" id="slot_step_minutes" name="slot_step_minutes" value="{{ old('slot_step_minutes', $settings['slot_step_minutes']) }}" min="5" required></div>
            <div class="grow"><label for="buffer_minutes">Intervalo entre pacientes (minutos)</label><input type="number" id="buffer_minutes" name="buffer_minutes" value="{{ old('buffer_minutes', $settings['buffer_minutes']) }}" min="0" required></div>
        </div>
        <div class="row">
            <div class="grow"><label for="min_notice_hours">Antecedência mínima para pedir horário (horas)</label><input type="number" id="min_notice_hours" name="min_notice_hours" value="{{ old('min_notice_hours', $settings['min_notice_hours']) }}" min="0" required></div>
            <div class="grow"><label for="max_days_ahead">Mostrar horários até quantos dias à frente</label><input type="number" id="max_days_ahead" name="max_days_ahead" value="{{ old('max_days_ahead', $settings['max_days_ahead']) }}" min="1" required></div>
        </div>
        <label class="check"><input type="checkbox" name="show_prices" value="1" @checked($settings['show_prices'] === '1')> <span>Mostrar os valores na página de agendamento</span></label>
        <label class="check"><input type="checkbox" name="no_show_uses_session" value="1" @checked($settings['no_show_uses_session'] === '1')> <span>Falta desconta uma sessão do pacote</span></label>
        <div><button class="btn sm">Salvar</button></div>
    </form>
@endsection
