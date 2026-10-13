@php
    use App\Enums\AppointmentStatus;
    use App\Support\Money;
    $s = $appointment->status;
    $when = $appointment->starts_at->translatedFormat('l, j/m') . ' às ' . $appointment->starts_at->format('H:i');
    $text = match ($s) {
        AppointmentStatus::Requested, AppointmentStatus::Confirmed => "Olá, {$appointment->patient->guardian_name}! Confirmando o atendimento de {$appointment->patient->patient_name}: {$appointment->service->name}, {$when}."
            . (\App\Models\Setting::get('clinic_address') ? ' Local: ' . \App\Models\Setting::get('clinic_address') . '.' : ''),
        default => "Olá, {$appointment->patient->guardian_name}!",
    };
    $wa = 'https://wa.me/' . $appointment->patient->whatsappNumber() . '?text=' . rawurlencode($text);
@endphp
<div class="card appt">
    <div class="time">
        {{ $appointment->starts_at->format('H:i') }}
        @isset($showDate)<div class="muted small">{{ $appointment->starts_at->format('d/m') }}</div>@endisset
    </div>
    <div class="body">
        <div class="row between">
            <a href="{{ route('admin.patients.show', $appointment->patient) }}"><strong>{{ $appointment->patient->patient_name }}</strong></a>
            <span class="badge {{ $s->value }}">{{ $s->label() }}</span>
        </div>
        <p class="muted small">
            {{ $appointment->service->name }} · {{ $appointment->service->duration_minutes }} min ·
            resp. {{ $appointment->patient->guardian_name }}
        </p>
        <p class="small">
            @if ($appointment->patientPackage)
                Pacote: {{ $appointment->patientPackage->package->name }}
                (restam {{ $appointment->patientPackage->remaining() }} de {{ $appointment->patientPackage->sessions_total }})
            @else
                {{ Money::format($appointment->price_cents) }} · {{ $appointment->paid_at ? 'pago' : 'a receber' }}
            @endif
        </p>
        @if ($appointment->request_note)<p class="small muted">“{{ $appointment->request_note }}”</p>@endif

        <div class="actions">
            @if ($s === AppointmentStatus::Requested)
                <form class="inline" method="post" action="{{ route('admin.appointments.action', [$appointment, 'confirmar']) }}">@csrf<button class="btn sm">Confirmar</button></form>
            @endif
            @if ($s === AppointmentStatus::Confirmed)
                <form class="inline" method="post" action="{{ route('admin.appointments.action', [$appointment, 'realizado']) }}">@csrf<button class="btn sm">Realizado</button></form>
                <form class="inline" method="post" action="{{ route('admin.appointments.action', [$appointment, 'faltou']) }}">@csrf<button class="btn sm quiet">Faltou</button></form>
            @endif
            @if (in_array($s, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true))
                <a class="btn sm ghost" href="{{ $wa }}" target="_blank" rel="noopener">WhatsApp</a>
            @endif
            @if (! in_array($s, [AppointmentStatus::Done, AppointmentStatus::Canceled], true))
                <form class="inline" method="post" action="{{ route('admin.appointments.action', [$appointment, 'cancelar']) }}"
                      onsubmit="return confirm('Cancelar este agendamento?')">@csrf<button class="btn sm danger">Cancelar</button></form>
            @endif
        </div>

        @unless ($appointment->patientPackage || $s === AppointmentStatus::Canceled)
            <details style="margin-top:.5rem">
                <summary class="small">Valor e pagamento</summary>
                <form class="row" method="post" action="{{ route('admin.appointments.update', $appointment) }}" style="margin-top:.5rem">
                    @csrf @method('put')
                    <div class="grow">
                        <label for="price-{{ $appointment->id }}">Valor combinado</label>
                        <input type="text" id="price-{{ $appointment->id }}" name="price" inputmode="decimal"
                               value="{{ $appointment->price_cents !== null ? number_format($appointment->price_cents / 100, 2, ',', '') : '' }}" placeholder="150,00">
                    </div>
                    <label class="check"><input type="checkbox" name="paid" value="1" @checked($appointment->paid_at)> <span>Já foi pago</span></label>
                    <button class="btn sm">Salvar</button>
                </form>
            </details>
        @endunless
    </div>
</div>
