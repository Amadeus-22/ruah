@extends('layouts.admin')
@section('title', 'Quadro')
@section('content')
    @php
        use App\Support\Icon;
        $columns = [
            'novo' => ['Quer agendar', 'Chegou e ainda não tem horário', 'inbox', '#2f6fd0', $newInquiries->count() + $requested->count(), 'Ninguém esperando resposta', 'Contatos novos e pedidos da página aparecem aqui.'],
            'aguardando' => ['Aguardando paciente', 'Você respondeu; falta ele decidir', 'hourglass', '#b7791f', $waitingInquiries->count(), 'Nenhum contato aguardando', 'Arraste para cá quem já recebeu as opções de horário.'],
            'agendado' => ['Agendado', 'Horário confirmado', 'calendar', '#1f7a6d', $confirmed->count(), 'Nenhum horário confirmado', 'Os atendimentos marcados aparecem aqui.'],
            'realizado' => ['Realizado', 'Últimos 7 dias', 'check', '#1d6b3a', $done->count(), 'Nada realizado na semana', 'Arraste para cá o atendimento que aconteceu.'],
            'faltou' => ['Faltou', 'Últimos 7 dias', 'x-circle', '#a3271f', $noShow->count(), 'Nenhuma falta na semana', 'Faltas ficam aqui para remarcar.'],
        ];
    @endphp

    <header class="work-head">
        <div class="work-title">
            <span class="work-title-icon">{!! Icon::svg('board') !!}</span>
            <div>
                <h1>Quadro de atendimento</h1>
                <p>Do primeiro contato ao atendimento realizado. Arraste os cartões entre as colunas.</p>
            </div>
        </div>
        <nav class="view-switch" aria-label="Outras visões">
            <a class="is-active" href="{{ route('admin.dashboard') }}" aria-current="page">{!! Icon::svg('board') !!} Quadro</a>
            <a href="{{ route('admin.agenda') }}">{!! Icon::svg('calendar') !!} Agenda</a>
            <a href="{{ route('admin.overview') }}">{!! Icon::svg('chart') !!} Visão geral</a>
        </nav>
    </header>

    <div class="work-toolbar">
        <label class="board-search" for="board-search">
            {!! Icon::svg('search') !!}
            <input type="search" id="board-search" placeholder="Buscar paciente, responsável, serviço..." autocomplete="off">
        </label>
        <div class="work-toolbar-actions">
            <button class="btn" type="button" data-new-card>{!! Icon::svg('plus-circle') !!} Novo cartão</button>
            <a class="btn ghost" href="{{ route('admin.appointments.create') }}">{!! Icon::svg('calendar') !!} Novo horário</a>
        </div>
    </div>

    <section class="status-summary" aria-label="Resumo por coluna">
        @foreach ($columns as $key => [$title, $sub, $icon, $color, $count])
            <a class="status-card" href="#col-{{ $key }}" data-summary="{{ $key }}" style="--c: {{ $color }}">
                <span class="status-card-icon">{!! Icon::svg($icon) !!}</span>
                <div><span class="status-card-label">{{ $title }}</span><strong>{{ $count }}</strong><small>{{ $sub }}</small></div>
            </a>
        @endforeach
    </section>

    <div class="board">
        @foreach ($columns as $key => [$title, $sub, $icon, $color, $count, $emptyTitle, $emptyText])
            <section class="col" id="col-{{ $key }}" style="--c: {{ $color }}" aria-label="{{ $title }}" data-col="{{ $key }}">
                <div class="col-head">
                    <span class="col-icon">{!! Icon::svg($icon) !!}</span>
                    <div class="col-title"><strong>{{ $title }}</strong><span class="hint">{{ $sub }}</span></div>
                    <span class="count" data-count>{{ $count }}</span>
                </div>
                <div class="col-body">
                    @if ($key === 'novo')
                        @foreach ($requested as $appointment)
                            @include('partials.kcard', ['appointment' => $appointment])
                        @endforeach
                        @foreach ($newInquiries as $inquiry)
                            @include('partials.inquiry', ['inquiry' => $inquiry])
                        @endforeach
                    @elseif ($key === 'aguardando')
                        @foreach ($waitingInquiries as $inquiry)
                            @include('partials.inquiry', ['inquiry' => $inquiry])
                        @endforeach
                    @else
                        @foreach (['agendado' => $confirmed, 'realizado' => $done, 'faltou' => $noShow][$key] as $appointment)
                            @include('partials.kcard', ['appointment' => $appointment])
                        @endforeach
                    @endif
                    <div class="col-empty">
                            <span class="col-empty-icon">{!! Icon::svg($icon) !!}</span>
                            <strong>{{ $emptyTitle }}</strong>
                            <span>{{ $emptyText }}</span>
                        </div>
                </div>
                @if ($key === 'novo')
                    <button class="col-add" type="button" data-new-card="new">{!! Icon::svg('plus-circle') !!} Novo cartão</button>
                @elseif ($key === 'aguardando')
                    <button class="col-add" type="button" data-new-card="waiting">{!! Icon::svg('plus-circle') !!} Novo cartão</button>
                @elseif ($key === 'agendado')
                    <a class="col-add" href="{{ route('admin.appointments.create') }}">{!! Icon::svg('plus-circle') !!} Novo horário</a>
                @endif
            </section>
        @endforeach
    </div>

    <dialog class="modal modal-lg" id="card-modal" aria-label="Cartão do quadro">
        <div class="modal-body" data-new-card-template hidden>@include('partials.card-modal', ['inquiry' => null])</div>
        <div class="modal-body" data-modal-content></div>
    </dialog>

    <div class="toast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
<script>
(() => {
    const board = document.querySelector('.board');
    const toast = document.querySelector('.toast');
    const token = document.querySelector('meta[name=csrf-token]').content;
    let dragged = null;

    // Janela do cartão: criar a partir do modelo, editar carregando do servidor.
    const modal = document.getElementById('card-modal');
    const content = modal.querySelector('[data-modal-content]');
    const template = modal.querySelector('[data-new-card-template]');
    const wire = () => {
        content.querySelectorAll('[data-close-dialog]').forEach((b) => b.addEventListener('click', () => modal.close()));
        content.querySelectorAll('[data-tab]').forEach((b) => b.addEventListener('click', () => {
            content.querySelectorAll('[data-tab]').forEach((t) => t.classList.toggle('is-active', t === b));
            content.querySelectorAll('[data-pane]').forEach((p) => { p.hidden = p.dataset.pane !== b.dataset.tab; });
        }));
        const noteForm = content.querySelector('[data-note-form]');
        noteForm?.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const res = await fetch(noteForm.action, { method: 'POST', headers: { 'X-CSRF-TOKEN': token, 'Accept': 'text/html' }, body: new FormData(noteForm) });
            if (res.ok) { content.innerHTML = await res.text(); wire(); content.querySelector('[data-note-form] input')?.focus(); }
        });
    };
    document.querySelectorAll('[data-new-card]').forEach((b) => b.addEventListener('click', () => {
        content.innerHTML = template.innerHTML;
        if (b.dataset.newCard) content.querySelector('#c-stage').value = b.dataset.newCard;
        wire(); modal.showModal(); content.querySelector('#c-name').focus();
    }));
    document.addEventListener('click', async (ev) => {
        const b = ev.target.closest('[data-edit-card]');
        if (!b) return;
        ev.preventDefault();
        const res = await fetch(b.dataset.editCard, { headers: { 'Accept': 'text/html' } });
        if (!res.ok) return;
        content.innerHTML = await res.text(); wire(); modal.showModal();
    });
    modal.addEventListener('click', (ev) => { if (ev.target === modal) modal.close(); });
    template.remove();

    // Menus "mais ações": só um aberto por vez.
    document.addEventListener('click', (ev) => {
        document.querySelectorAll('details.more[open]').forEach((m) => { if (!m.contains(ev.target)) m.removeAttribute('open'); });
    });

    // Busca: esconde os cartões que não batem com o texto.
    const search = document.getElementById('board-search');
    const norm = (t) => t.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase();
    search.addEventListener('input', () => {
        const q = norm(search.value.trim());
        board.querySelectorAll('.kcard').forEach((c) => { c.hidden = q !== '' && !norm(c.textContent).includes(q); });
    });

    const say = (text, bad = false) => {
        toast.textContent = text; toast.classList.toggle('bad', bad); toast.hidden = false;
        clearTimeout(say.t); say.t = setTimeout(() => { toast.hidden = true; }, 3200);
    };
    const columns = [...board.querySelectorAll('.col[data-col]')];
    const clear = () => columns.forEach((c) => c.classList.remove('can-drop', 'no-drop', 'over'));
    const recount = () => columns.forEach((c) => {
        const n = c.querySelectorAll('.kcard').length;
        c.querySelector('[data-count]').textContent = n;
        const tile = document.querySelector(`[data-summary="${c.dataset.col}"] strong`);
        if (tile) tile.textContent = n;
    });
    const placeholder = document.createElement('div');
    placeholder.className = 'drop-slot';
    let origin = null;

    // Onde o cartão entraria: antes do primeiro cartão cujo meio fica abaixo do mouse.
    const slotBefore = (body, y) => [...body.querySelectorAll('.kcard:not(.dragging)')]
        .find((c) => { const r = c.getBoundingClientRect(); return y < r.top + r.height / 2; }) || null;

    board.addEventListener('dragstart', (ev) => {
        const card = ev.target.closest('.kcard[draggable=true]');
        if (!card) return;
        dragged = card;
        origin = { parent: card.parentNode, next: card.nextSibling };
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', card.dataset.id);
        placeholder.style.height = card.offsetHeight + 'px';
        const allowed = card.dataset.allowed.split(' ');
        requestAnimationFrame(() => card.classList.add('dragging'));
        columns.forEach((c) => {
            if (c.contains(card)) return;
            c.classList.add(allowed.includes(c.dataset.col) ? 'can-drop' : 'no-drop');
        });
    });
    board.addEventListener('dragend', () => {
        dragged?.classList.remove('dragging'); dragged = null; placeholder.remove(); clear();
    });

    columns.forEach((col) => {
        const body = col.querySelector('.col-body');
        col.addEventListener('dragover', (ev) => {
            if (!dragged || !col.classList.contains('can-drop')) return;
            ev.preventDefault();
            ev.dataTransfer.dropEffect = 'move';
            columns.forEach((c) => c.classList.toggle('over', c === col));
            const before = slotBefore(body, ev.clientY);
            if (placeholder.parentNode !== body || placeholder.nextSibling !== before) body.insertBefore(placeholder, before);
        });
        col.addEventListener('dragleave', (ev) => {
            if (!col.contains(ev.relatedTarget)) { col.classList.remove('over'); if (placeholder.parentNode === body) placeholder.remove(); }
        });
        col.addEventListener('drop', async (ev) => {
            ev.preventDefault();
            if (!dragged || !col.classList.contains('can-drop')) return;
            const card = dragged;
            const back = origin;
            body.insertBefore(card, placeholder.parentNode === body ? placeholder : body.firstChild);
            placeholder.remove();
            card.classList.remove('dragging');
            card.classList.add('saving', 'landed');
            clear(); recount();
            const undo = (msg) => { back.parent.insertBefore(card, back.next); card.classList.remove('saving'); recount(); say(msg, true); };
            try {
                const res = await fetch(@json(route('admin.board.move')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ kind: card.dataset.kind, id: Number(card.dataset.id), to: col.dataset.col }),
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok) { undo(data.message || 'Não foi possível mover o cartão.'); return; }
                if (data.redirect) { location.href = data.redirect; return; }
                if (data.html) {
                    const fresh = document.createRange().createContextualFragment(data.html).firstElementChild;
                    fresh.classList.add('landed');
                    card.replaceWith(fresh);
                }
                say(data.message || 'Cartão movido.');
            } catch (e) {
                undo('Sem conexão. O cartão voltou para o lugar.');
            }
        });
    });
})();
</script>
@endpush
