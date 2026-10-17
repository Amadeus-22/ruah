@php
    use App\Support\Icon;
    $number = $inquiry->whatsappNumber();
    $channelIcon = ['whatsapp' => 'whatsapp', 'instagram' => 'instagram'][$inquiry->channel] ?? 'note';
@endphp
<article class="kcard draggable" draggable="true" data-kind="contato" data-id="{{ $inquiry->id }}" data-allowed="{{ implode(' ', \App\Board\BoardMoves::allowed($inquiry)) }}">
    <div class="kcard-head">
        <div class="kcard-titles">
            <span class="kcard-kicker">{!! Icon::svg($channelIcon) !!} {{ \App\Models\Inquiry::CHANNELS[$inquiry->channel] ?? $inquiry->channel }} · {{ $inquiry->created_at->diffForHumans() }}</span>
            <a class="kcard-title" href="#" data-edit-card="{{ route('admin.inquiries.edit', $inquiry) }}">{{ $inquiry->guardian_name }}</a>
        </div>
        @if ($inquiry->priority === 'alta')<span class="tag tag-bad">Prioridade</span>@else<span class="tag tag-blue">Contato</span>@endif
    </div>
    @if ($inquiry->patient_name)<p class="kcard-desc">Paciente: {{ $inquiry->patient_name }}</p>@endif
    @if ($inquiry->service || $inquiry->preferred_period || ($inquiry->notes_count ?? 0))
        <div class="chips">
            @if ($inquiry->service)<span class="chip">{!! Icon::svg('stethoscope') !!} {{ $inquiry->service->name }}</span>@endif
            @if ($inquiry->preferred_period)<span class="chip">{!! Icon::svg('clock') !!} {{ \App\Models\Inquiry::PERIODS[$inquiry->preferred_period] ?? '' }}</span>@endif
            @if ($inquiry->notes_count ?? 0)<span class="chip">{!! Icon::svg('note') !!} {{ $inquiry->notes_count }}</span>@endif
        </div>
    @endif
    @if ($inquiry->note)<p class="kcard-note">{!! Icon::svg('note') !!} “{{ $inquiry->note }}”</p>@endif

    <div class="kcard-foot">
        <div class="kcard-actions">
            <a class="btn sm" href="{{ route('admin.appointments.create', ['inquiry_id' => $inquiry->id]) }}">{!! Icon::svg('calendar') !!} Agendar</a>
            @if ($number)<a class="icon-chip wa" href="https://wa.me/{{ $number }}" target="_blank" rel="noopener" title="Mensagem no WhatsApp">{!! Icon::svg('whatsapp') !!}</a>@endif
        </div>
        <details class="more">
            <summary aria-label="Mais ações">{!! Icon::svg('dots') !!}</summary>
            <div class="more-menu">
                <a href="#" data-edit-card="{{ route('admin.inquiries.edit', $inquiry) }}">{!! Icon::svg('note') !!} Abrir cartão</a>
                @if ($inquiry->stage === \App\Models\Inquiry::STAGE_NEW)
                    <form method="post" action="{{ route('admin.inquiries.move', [$inquiry, 'waiting']) }}">@csrf<button>{!! Icon::svg('arrow-right') !!} Já respondi</button></form>
                @else
                    <form method="post" action="{{ route('admin.inquiries.move', [$inquiry, 'new']) }}">@csrf<button>{!! Icon::svg('arrow-left') !!} Voltar para "Quer agendar"</button></form>
                @endif
                <form method="post" action="{{ route('admin.inquiries.close', $inquiry) }}" onsubmit="return confirm('Arquivar este contato?')">@csrf<button class="danger">{!! Icon::svg('archive') !!} Arquivar</button></form>
            </div>
        </details>
    </div>
</article>
