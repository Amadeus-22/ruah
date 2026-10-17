@extends('layouts.admin')
@section('title', 'Visão geral')
@section('content')
    @php
        use App\Support\Icon;
        $money = fn (int $cents) => \App\Support\Money::format($cents);
        $pct = fn (?float $v) => $v === null ? '—' : number_format($v * 100, 0, ',', '.').'%';
        $max = max(1, $trend->max(fn ($t) => $t['received'] + $t['due']));
    @endphp
    <div class="page-head">
        <div>
            <h1>Visão geral</h1>
            <p class="muted small">Como o consultório está indo no mês, e a tendência dos últimos seis.</p>
        </div>
        <div class="month-nav">
            <a class="icon-btn" href="{{ route('admin.overview', ['mes' => $month->subMonth()->format('Y-m')]) }}" aria-label="Mês anterior">‹</a>
            <strong class="cap">{{ $month->translatedFormat('F \d\e Y') }}</strong>
            <a class="icon-btn" href="{{ route('admin.overview', ['mes' => $month->addMonth()->format('Y-m')]) }}" aria-label="Próximo mês">›</a>
        </div>
    </div>

    <section class="kpis" aria-label="Indicadores do mês">
        <div class="kpi kpi-main">
            <span class="kpi-icon">{!! Icon::svg('money') !!}</span>
            <span class="kpi-label">Recebido</span>
            <strong>{{ $money($kpis['received']) }}</strong>
            @if ($kpis['received_change'] !== null)
                <small @class(['delta', 'up' => $kpis['received_change'] >= 0, 'down' => $kpis['received_change'] < 0])>
                    {{ $kpis['received_change'] >= 0 ? '▲' : '▼' }} {{ $pct(abs($kpis['received_change'])) }} sobre o mês anterior
                </small>
            @else
                <small class="muted">sem base no mês anterior</small>
            @endif
        </div>
        <div class="kpi"><span class="kpi-icon warn">{!! Icon::svg('clock') !!}</span><span class="kpi-label">A receber</span><strong>{{ $money($kpis['due']) }}</strong><small class="muted">já atendido ou vendido</small></div>
        <div class="kpi"><span class="kpi-icon blue">{!! Icon::svg('calendar') !!}</span><span class="kpi-label">Previsto</span><strong>{{ $money($kpis['expected']) }}</strong><small class="muted">agendado no mês</small></div>
        <div class="kpi"><span class="kpi-icon">{!! Icon::svg('trend-up') !!}</span><span class="kpi-label">Ticket médio</span><strong>{{ $money($kpis['ticket']) }}</strong><small class="muted">por lançamento pago</small></div>
        <div class="kpi"><span class="kpi-icon">{!! Icon::svg('check') !!}</span><span class="kpi-label">Atendimentos</span><strong>{{ $kpis['done'] }}</strong><small class="muted">{{ $kpis['patients'] }} {{ $kpis['patients'] === 1 ? 'paciente' : 'pacientes' }}</small></div>
        <div class="kpi"><span class="kpi-icon bad">{!! Icon::svg('alert') !!}</span><span class="kpi-label">Faltas</span><strong>{{ $pct($kpis['no_show_rate']) }}</strong><small class="muted">dos atendimentos marcados</small></div>
        <div class="kpi"><span class="kpi-icon">{!! Icon::svg('package') !!}</span><span class="kpi-label">Sessões de pacote</span><strong>{{ $kpis['package_sessions_left'] }}</strong><small class="muted">ainda a usar</small></div>
    </section>

    <div class="dash-grid">
        <section class="card chart-card">
            <div class="chart-head">
                <div><h3>Últimos seis meses</h3><p class="muted small">Recebido e a receber por mês.</p></div>
                <div class="legend"><span><i class="sw sw-brand"></i>Recebido</span><span><i class="sw sw-warn"></i>A receber</span></div>
            </div>
            <div class="bars" role="img" aria-label="Recebido e a receber nos últimos seis meses">
                @foreach ($trend as $t)
                    <div class="bar-col">
                        <div class="bar-stack" title="{{ $t['label'] }}: {{ $money($t['received']) }} recebido, {{ $money($t['due']) }} a receber">
                            <span class="bar due" style="height: {{ round($t['due'] / $max * 100, 1) }}%"></span>
                            <span class="bar received" style="height: {{ round($t['received'] / $max * 100, 1) }}%"></span>
                        </div>
                        <span class="bar-value">{{ $t['received'] > 0 ? 'R$ '.number_format($t['received'] / 100, 0, ',', '.') : '' }}</span>
                        <span class="bar-label cap">{{ rtrim($t['label'], '.') }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="card">
            <h3>Como recebeu</h3>
            <p class="muted small">Pagamentos do mês por forma.</p>
            @php($top = max(1, $byMethod->max('cents')))
            @forelse ($byMethod as $m)
                <div class="hbar"><span>{{ $m['label'] }}</span><div class="hbar-track"><i style="width: {{ round($m['cents'] / $top * 100, 1) }}%"></i></div><strong>{{ $money($m['cents']) }}</strong></div>
            @empty
                <div class="empty">Nenhum pagamento registrado no mês.</div>
            @endforelse
        </section>

        <section class="card">
            <h3>Por serviço</h3>
            <p class="muted small">O que mais gerou receita no mês.</p>
            @php($top = max(1, $byService->max('cents')))
            @forelse ($byService as $sv)
                <div class="hbar"><span>{{ $sv['label'] }} <small class="muted">· {{ $sv['count'] }}</small></span><div class="hbar-track"><i class="alt" style="width: {{ round($sv['cents'] / $top * 100, 1) }}%"></i></div><strong>{{ $money($sv['cents']) }}</strong></div>
            @empty
                <div class="empty">Nada lançado no mês.</div>
            @endforelse
        </section>

        <section class="card">
            <div class="row between"><h3>Quem falta pagar</h3><a class="small" href="{{ route('admin.finance', ['mes' => $month->format('Y-m'), 'filtro' => 'a-receber']) }}">Ver no financeiro</a></div>
            @forelse ($owing as $o)
                <div class="owing">
                    <div><strong>{{ $o['patient'] }}</strong><span class="muted small"> · {{ $o['description'] }} · {{ $o['date']->format('d/m') }}</span></div>
                    <strong>{{ $money($o['cents']) }}</strong>
                </div>
            @empty
                <div class="empty">Ninguém devendo neste mês.</div>
            @endforelse
        </section>
    </div>
@endsection
