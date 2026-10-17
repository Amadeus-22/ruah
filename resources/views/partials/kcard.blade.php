@php
    use App\Enums\AppointmentStatus;
    use App\Support\Icon;
    $s = $appointment->status;
    $when = $appointment->starts_at->translatedFormat('D, j/m') . ' · ' . $appointment->starts_at->format('H:i');
    $text = "Olá, {$appointment->patient->guardian_name}! Confirmando o atendimento de {$appointment->patient->patient_name}: {$appointment->service->name}, "
        . $appointment->starts_at->translatedFormat('l, j/m') . ' às ' . $appointment->starts_at->format('H:i') . '.'
        . (\App\Models\Setting::get('clinic_address') ? ' Local: ' . \App\Models\Setting::get('clinic_address') . '.' : '');
    $wa = 'https://wa.me/' . $appointment->patient->whatsappNumber() . '?text=' . rawurlencode($text);
    $late = $s === AppointmentStatus::Confirmed && $appointment->ends_at->lessThan($now);
    [$badge, $tone] = match (true) {
        $s === AppointmentStatus::Requested => ['Pedido', 'blue'],
        $late => ['Já passou', 'warn'],
        $s === AppointmentStatus::Confirmed => ['Confirmado', 'brand'],
        $s === AppointmentStatus::Done => ['Realizado', 'ok'],
        default => ['Faltou', 'bad'],
    };
    $allowed = \App\Board\BoardMoves::allowed($appointment);
@endphp
<article @class(['kcard', 'late' => $late, 'draggable' => $allowed]) @if ($allowed) draggable="true" @endif data-kind="agendamento" data-id="{{ $appointment->id }}" data-allowed="{{ implode(' ', $allowed) }}">
    <div class="kcard-head">
        <div class="kcard-titles">
            <span class="kcard-kicker cap">{!! Icon::svg('clock') !!} {{ $when }}</span>
            <a class="kcard-title" href="{{ route('admin.patients.show', $appointment->patient) }}">{{ $appointment->patient->patient_name }}</a>
        </div>
        <span class="tag tag-{{ $tone }}">{{ $badge }}</span>
    </div>
    <p class="kcard-desc">{{ $appointment->service->name }} · resp. {{ $appointment->patient->guardian_name }}</p>
    <div class="chips">
        @if ($appointment->patientPackage)
            <span class="chip">{!! Icon::svg('package') !!} Pacote · restam {{ $appointment->patientPackage->remaining() }}</span>
        @else
            <span class="chip">{!! Icon::svg('money') !!} {{ \App\Support\Money::format($appointment->price_cents) }}</span>
            <span @class(['chip', 'chip-ok' => $appointment->paid_at, 'chip-warn' => ! $appointment->paid_at])>{{ $appointment->paid_at ? 'pago' : 'a receber' }}</span>
        @endif
    </div>
    @if ($appointment->request_note)<p class="kcard-note">{!! Icon::svg('note') !!} “{{ $appointment->request_note }}”</p>@endif

    <div class="kcard-foot">
        <div class="kcard-actions">
            @if ($s === AppointmentStatus::Requested)
                <form method="post" action="{{ route('admin.appointments.action', [$appointment, 'confirmar']) }}">@csrf<button class="btn sm">{!! Icon::svg('check') !!} Confirmar</button></form>
            @elseif ($s === AppointmentStatus::Confirmed && $late)
                <form method="post" action="{{ route('admin.appointments.action', [$appointment, 'realizado']) }}">@csrf<button class="btn sm">{!! Icon::svg('check') !!} Realizado</button></form>
                <form method="post" action="{{ route('admin.appointments.action', [$appointment, 'faltou']) }}">@csrf<button class="btn sm quiet">Faltou</button></form>
            @elseif ($s === AppointmentStatus::NoShow)
                <a class="btn sm ghost" href="{{ route('admin.appointments.create', ['patient_id' => $appointment->patient_id, 'service_id' => $appointment->service_id]) }}">{!! Icon::svg('repeat') !!} Remarcar</a>
            @endif
            @if (in_array($s, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true))
                <a class="icon-chip wa" href="{{ $wa }}" target="_blank" rel="noopener" title="Mensagem no WhatsApp">{!! Icon::svg('whatsapp') !!}</a>
            @endif
        </div>
        <details class="more">
            <summary aria-label="Mais ações">{!! Icon::svg('dots') !!}</summary>
            <div class="more-menu">
                <a href="{{ route('admin.patients.show', $appointment->patient) }}">{!! Icon::svg('user') !!} Abrir paciente</a>
                <a href="{{ $wa }}" target="_blank" rel="noopener">{!! Icon::svg('whatsapp') !!} WhatsApp</a>
                @if ($s === AppointmentStatus::Confirmed && ! $late)
                    <span class="more-hint">{!! Icon::svg('clock') !!} Realizado e falta ficam disponíveis depois do horário</span>
                @endif
                @if (in_array($s, [AppointmentStatus::Requested, AppointmentStatus::Confirmed], true))
                    <form method="post" action="{{ route('admin.appointments.action', [$appointment, 'cancelar']) }}" onsubmit="return confirm('Cancelar este agendamento?')">@csrf
                        <button class="danger">{!! Icon::svg('trash') !!} Cancelar agendamento</button></form>
                @endif
            </div>
        </details>
    </div>
</article>
