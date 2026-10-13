@extends('layouts.admin')
@section('title', 'Quadro')
@section('content')
    <div class="page-head">
        <div>
            <h1>Quadro de atendimento</h1>
            <p class="muted small">Do primeiro contato ao atendimento realizado. Cada cartão anda pelas colunas com os botões.</p>
        </div>
        <a class="btn sm" href="{{ route('admin.appointments.create') }}">Novo horário</a>
    </div>

    <div class="board">
        {{-- 1. Quer agendar --}}
        <section class="col" style="--c:#2f6fd0" aria-label="Quer agendar">
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
        <section class="col" style="--c:#b7791f" aria-label="Aguardando paciente">
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
        <section class="col" style="--c:#1f7a6d" aria-label="Agendado">
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
        <section class="col" style="--c:#1d6b3a" aria-label="Realizado">
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
        <section class="col" style="--c:#a3271f" aria-label="Faltou">
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
@endsection
