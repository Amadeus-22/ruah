@extends('layouts.admin')
@section('title', 'Quadro')
@section('content')
    <div class="page-head">
        <div>
            <h1>Quadro de atendimento</h1>
            <p class="muted small">Do primeiro contato ao atendimento realizado. Arraste os cartões entre as colunas, ou use os botões.</p>
        </div>
        <a class="btn sm" href="{{ route('admin.appointments.create') }}">Novo horário</a>
    </div>

    <div class="board">
        {{-- 1. Quer agendar --}}
        <section class="col" style="--c:#2f6fd0" aria-label="Quer agendar" data-col="novo">
            <div class="col-head">
                <div><strong>Quer agendar</strong><span class="hint">Chegou e ainda não tem horário</span></div>
                <span class="count">{{ $newInquiries->count() + $requested->count() }}</span>
            </div>
            <div class="col-body">
                <details class="kcard">
                    <summary>Adicionar contato</summary>
                    <form class="stack" method="post" action="{{ route('admin.inquiries.store') }}" style="margin-top:.6rem">
                        @csrf
                        <div><label for="i-name">Nome de quem chamou</label><input type="text" id="i-name" name="guardian_name" required></div>
                        <div><label for="i-patient">Paciente (se souber)</label><input type="text" id="i-patient" name="patient_name"></div>
                        <div><label for="i-phone">WhatsApp</label><input type="tel" id="i-phone" name="phone" inputmode="tel"></div>
                        <div><label for="i-channel">Veio por</label>
                            <select id="i-channel" name="channel">@foreach (\App\Models\Inquiry::CHANNELS as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
                        </div>
                        <div><label for="i-note">Anotação</label><input type="text" id="i-note" name="note" placeholder="Prefere manhã"></div>
                        <button class="btn sm">Adicionar</button>
                    </form>
                </details>

                @foreach ($requested as $appointment)
                    <div><span class="chip">pedido pela página</span></div>
                    @include('partials.kcard', ['appointment' => $appointment])
                @endforeach

                @foreach ($newInquiries as $inquiry)
                    @include('partials.inquiry', ['inquiry' => $inquiry])
                @endforeach

                @if ($requested->isEmpty() && $newInquiries->isEmpty())
                    <div class="empty">Ninguém esperando resposta sua.</div>
                @endif
            </div>
        </section>

        {{-- 2. Aguardando paciente --}}
        <section class="col" style="--c:#b7791f" aria-label="Aguardando paciente" data-col="aguardando">
            <div class="col-head">
                <div><strong>Aguardando paciente</strong><span class="hint">Você respondeu; falta ele decidir</span></div>
                <span class="count">{{ $waitingInquiries->count() }}</span>
            </div>
            <div class="col-body">
                @forelse ($waitingInquiries as $inquiry)
                    @include('partials.inquiry', ['inquiry' => $inquiry])
                @empty
                    <div class="empty">Nenhum contato aguardando resposta.</div>
                @endforelse
            </div>
        </section>

        {{-- 3. Agendado --}}
        <section class="col" style="--c:#1f7a6d" aria-label="Agendado" data-col="agendado">
            <div class="col-head">
                <div><strong>Agendado</strong><span class="hint">Horário confirmado</span></div>
                <span class="count">{{ $confirmed->count() }}</span>
            </div>
            <div class="col-body">
                @forelse ($confirmed as $appointment)
                    @include('partials.kcard', ['appointment' => $appointment])
                @empty
                    <div class="empty">Nenhum horário confirmado.</div>
                @endforelse
            </div>
        </section>

        {{-- 4. Realizado --}}
        <section class="col" style="--c:#1d6b3a" aria-label="Realizado" data-col="realizado">
            <div class="col-head">
                <div><strong>Realizado</strong><span class="hint">Últimos 7 dias</span></div>
                <span class="count">{{ $done->count() }}</span>
            </div>
            <div class="col-body">
                @forelse ($done as $appointment)
                    @include('partials.kcard', ['appointment' => $appointment])
                @empty
                    <div class="empty">Nenhum atendimento realizado na semana.</div>
                @endforelse
            </div>
        </section>

        {{-- 5. Faltou --}}
        <section class="col" style="--c:#a3271f" aria-label="Faltou" data-col="faltou">
            <div class="col-head">
                <div><strong>Faltou</strong><span class="hint">Últimos 7 dias</span></div>
                <span class="count">{{ $noShow->count() }}</span>
            </div>
            <div class="col-body">
                @forelse ($noShow as $appointment)
                    @include('partials.kcard', ['appointment' => $appointment])
                @empty
                    <div class="empty">Nenhuma falta na semana.</div>
                @endforelse
            </div>
        </section>
    </div>
    <div class="toast" role="status" aria-live="polite" hidden></div>
@endsection

@push('scripts')
<script>
(() => {
    const board = document.querySelector('.board');
    const toast = document.querySelector('.toast');
    const token = document.querySelector('meta[name=csrf-token]').content;
    let dragged = null;

    const say = (text, bad = false) => {
        toast.textContent = text; toast.classList.toggle('bad', bad); toast.hidden = false;
        clearTimeout(say.t); say.t = setTimeout(() => { toast.hidden = true; }, 3500);
    };
    const columns = () => board.querySelectorAll('.col[data-col]');
    const clear = () => columns().forEach((c) => c.classList.remove('can-drop', 'no-drop', 'over'));

    board.addEventListener('dragstart', (ev) => {
        const card = ev.target.closest('.kcard[draggable=true]');
        if (!card) return;
        dragged = card;
        ev.dataTransfer.effectAllowed = 'move';
        ev.dataTransfer.setData('text/plain', card.dataset.id);
        const allowed = card.dataset.allowed.split(' ');
        requestAnimationFrame(() => card.classList.add('dragging'));
        columns().forEach((c) => {
            if (c.contains(card)) return;
            c.classList.add(allowed.includes(c.dataset.col) ? 'can-drop' : 'no-drop');
        });
    });
    board.addEventListener('dragend', () => { dragged?.classList.remove('dragging'); dragged = null; clear(); });

    columns().forEach((col) => {
        col.addEventListener('dragover', (ev) => {
            if (!dragged || !col.classList.contains('can-drop')) return;
            ev.preventDefault(); col.classList.add('over');
        });
        col.addEventListener('dragleave', (ev) => { if (!col.contains(ev.relatedTarget)) col.classList.remove('over'); });
        col.addEventListener('drop', async (ev) => {
            ev.preventDefault();
            if (!dragged) return;
            const card = dragged;
            if (col.classList.contains('no-drop') || !col.classList.contains('can-drop')) return;
            card.classList.add('saving');
            col.querySelector('.col-body').append(card);
            clear();
            try {
                const res = await fetch(@json(route('admin.board.move')), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ kind: card.dataset.kind, id: Number(card.dataset.id), to: col.dataset.col }),
                });
                const body = await res.json().catch(() => ({}));
                if (!res.ok) { say(body.message || 'Não foi possível mover o cartão.', true); setTimeout(() => location.reload(), 1200); return; }
                if (body.redirect) { location.href = body.redirect; return; }
                say(body.message || 'Cartão movido.');
                setTimeout(() => location.reload(), 600);
            } catch (e) {
                say('Sem conexão. O cartão voltou para o lugar.', true); setTimeout(() => location.reload(), 1200);
            }
        });
    });
})();
</script>
@endpush
