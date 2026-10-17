@php
    use App\Enums\AppointmentStatus;
    $s = $appointment->status;
    $when = $appointment->starts_at->translatedFormat('D, j/m') . ' · ' . $appointment->starts_at->format('H:i');
    $text = "Olá, {$appointment->patient->guardian_name}! Confirmando o atendimento de {$appointment->patient->patient_name}: {$appointment->service->name}, "
        . $appointment->starts_at->translatedFormat('l, j/m') . ' às ' . $appointment->starts_at->format('H:i') . '.'
        . (\App\Models\Setting::get('clinic_address') ? ' Local: ' . \App\Models\Setting::get('clinic_address') . '.' : '');
    $wa = 'https://wa.me/' . $appointment->patient->whatsappNumber() . '?text=' . rawurlencode($text);
    $late = $s === AppointmentStatus::Confirmed && $appointment->ends_at->lessThan($now);
@endphp
@php($allowed = \App\Board\BoardMoves::allowed($appointment))
<div @class(['kcard', 'late' => $late, 'draggable' => $allowed]) @if ($allowed) draggable="true" @endif data-kind="agendamento" data-id="{{ $appointment->id }}" data-allowed="{{ implode(' ', $allowed) }}">
    <div class="when cap">{{ $when }}</div>
    <a class="who" href="{{ route('admin.patients.show', $appointment->patient) }}">{{ $appointment->patient->patient_name }}</a>
    <div class="meta">{{ $appointment->service->name }} · resp. {{ $appointment->patient->guardian_name }}</div>
    <div class="meta">
        @if ($appointment->patientPackage)
            Pacote · restam {{ $appointment->patientPackage->remaining() }}
        @else
            {{ \App\Support\Money::format($appointment->price_cents) }} · {{ $appointment->paid_at ? 'pago' : 'a receber' }}
        @endif
        @if ($late) · <strong>já passou</strong>@endif
    </div>
    @if ($appointment->request_note)<div class="meta">“{{ $appointment->request_note }}”</div>@endif
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
            <form class="inline" method="post" action="{{ route('admin.appointments.action', [$appointment, 'cancelar']) }}"
                  onsubmit="return confirm('Cancelar este agendamento?')">@csrf<button class="btn sm danger">Cancelar</button></form>
        @endif
        @if ($s === AppointmentStatus::NoShow)
            <a class="btn sm ghost" href="{{ route('admin.appointments.create', ['patient_id' => $appointment->patient_id, 'service_id' => $appointment->service_id]) }}">Remarcar</a>
        @endif
    </div>
</div>
