{{-- Janela do cartão do quadro: criar (sem $inquiry) ou editar. --}}
@php
    use App\Models\Inquiry;
    use App\Support\Icon;
    $editing = isset($inquiry) && $inquiry;
    $tab = $tab ?? 'geral';
    $v = fn (string $field, $default = null) => old($field, $editing ? $inquiry->{$field} : $default);
@endphp
<div class="modal-top">
    <h2>{!! Icon::svg('note') !!} {{ $editing ? $inquiry->guardian_name : 'Novo cartão' }}</h2>
    <button class="icon-btn" type="button" data-close-dialog aria-label="Fechar">{!! Icon::svg('x-circle') !!}</button>
</div>

<div class="tabs" role="tablist">
    <button type="button" role="tab" data-tab="geral" @class(['is-active' => $tab === 'geral'])>Geral</button>
    <button type="button" role="tab" data-tab="notas" @class(['is-active' => $tab === 'notas']) @disabled(! $editing)>Anotações @if ($editing && $inquiry->notes->count())<span class="tab-count">{{ $inquiry->notes->count() }}</span>@endif</button>
    <button type="button" role="tab" data-tab="historico" @class(['is-active' => $tab === 'historico']) @disabled(! $editing)>Histórico</button>
</div>

<section class="tab-pane" data-pane="geral" @if ($tab !== 'geral') hidden @endif>
    <form method="post" action="{{ $editing ? route('admin.inquiries.update', $inquiry) : route('admin.inquiries.store') }}" id="card-form">
        @csrf @if ($editing) @method('put') @endif
        <div class="form-grid">
            <div class="span-2"><label for="c-name">Quem chamou</label><input type="text" id="c-name" name="guardian_name" value="{{ $v('guardian_name') }}" required placeholder="Nome do responsável ou do paciente"></div>
            <div class="span-2"><label for="c-patient">Paciente</label><input type="text" id="c-patient" name="patient_name" value="{{ $v('patient_name') }}" placeholder="Se for outra pessoa, como um filho"></div>
            <div class="span-2"><label for="c-phone">WhatsApp</label><input type="tel" id="c-phone" name="phone" value="{{ $v('phone') }}" inputmode="tel" placeholder="(21) 99999-9999"></div>
            <div><label for="c-channel">Veio por</label>
                <select id="c-channel" name="channel">@foreach (Inquiry::CHANNELS as $key => $label)<option value="{{ $key }}" @selected($v('channel', 'whatsapp') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="c-stage">Coluna</label>
                <select id="c-stage" name="stage">@foreach (Inquiry::STAGES as $key => $label)<option value="{{ $key }}" @selected($v('stage', Inquiry::STAGE_NEW) === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="span-2"><label for="c-service">Interesse</label>
                <select id="c-service" name="service_id"><option value="">Ainda não sabe</option>@foreach ($services as $service)<option value="{{ $service->id }}" @selected((string) $v('service_id') === (string) $service->id)>{{ $service->name }}</option>@endforeach</select></div>
            <div><label for="c-period">Prefere</label>
                <select id="c-period" name="preferred_period"><option value="">Não disse</option>@foreach (Inquiry::PERIODS as $key => $label)<option value="{{ $key }}" @selected($v('preferred_period') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div><label for="c-priority">Prioridade</label>
                <select id="c-priority" name="priority">@foreach (Inquiry::PRIORITIES as $key => $label)<option value="{{ $key }}" @selected($v('priority', 'normal') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div class="span-4"><label for="c-note">Resumo</label><textarea id="c-note" name="note" maxlength="500" placeholder="O que a pessoa pediu, queixa principal, como chegou até você">{{ $v('note') }}</textarea></div>
        </div>
        @unless ($editing)
            <div class="info-box">{!! Icon::svg('clock') !!}<div><strong>Anotações e histórico</strong><span>Salve o cartão para registrar conversas e acompanhar cada movimento dele.</span></div></div>
        @endunless
    </form>
</section>

@if ($editing)
    <section class="tab-pane" data-pane="notas" @if ($tab !== 'notas') hidden @endif>
        <form class="note-form" data-note-form action="{{ route('admin.inquiries.notes', $inquiry) }}">
            <input type="text" name="body" maxlength="1000" placeholder="Ex.: mandei os horários de terça; ela vai ver com o marido" required aria-label="Nova anotação">
            <button class="btn">{!! Icon::svg('check') !!} Anotar</button>
        </form>
        <div class="timeline">
            @forelse ($inquiry->notes as $note)
                <div class="timeline-item"><span class="dot"></span><div><p>{{ $note->body }}</p><small>{{ $note->user?->name ?? 'Painel' }} · {{ $note->created_at->format('d/m H:i') }}</small></div></div>
            @empty
                <div class="empty">Nenhuma anotação ainda.</div>
            @endforelse
        </div>
    </section>

    <section class="tab-pane" data-pane="historico" @if ($tab !== 'historico') hidden @endif>
        <div class="timeline">
            @forelse ($inquiry->events as $event)
                <div class="timeline-item"><span class="dot muted-dot"></span><div><p>{{ $event->description }}</p><small>{{ $event->created_at->format('d/m H:i') }}</small></div></div>
            @empty
                <div class="empty">Sem movimentos registrados.</div>
            @endforelse
        </div>
    </section>
@endif

<div class="modal-foot">
    @if ($editing)<span class="muted small">Criado {{ $inquiry->created_at->diffForHumans() }}</span>@endif
    <button class="btn quiet" type="button" data-close-dialog>Fechar</button>
    <button class="btn" type="submit" form="card-form">{!! Icon::svg('check') !!} Salvar</button>
</div>
