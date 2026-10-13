@extends('layouts.admin')
@section('title', 'Financeiro')
@section('content')
    @php($money = fn (int $cents) => \App\Support\Money::format($cents))
    <div class="page-head">
        <div>
            <h1>Financeiro</h1>
            <p class="muted small">O que entrou e o que falta entrar no mês: atendimentos avulsos e pacotes vendidos.</p>
        </div>
        <div class="row">
            <a class="btn sm quiet" href="{{ route('admin.finance', ['mes' => $month->subMonth()->format('Y-m')]) }}" aria-label="Mês anterior">←</a>
            <strong class="cap">{{ $month->translatedFormat('F \d\e Y') }}</strong>
            <a class="btn sm quiet" href="{{ route('admin.finance', ['mes' => $month->addMonth()->format('Y-m')]) }}" aria-label="Próximo mês">→</a>
            <a class="btn sm ghost" href="{{ route('admin.finance.export', ['mes' => $month->format('Y-m')]) }}">Baixar planilha</a>
        </div>
    </div>

    <div class="stats">
        <div class="stat" style="--c:#1d6b3a"><span>Recebido</span><strong>{{ $money($totals['received']) }}</strong></div>
        <div class="stat" style="--c:#b7791f"><span>A receber</span><strong>{{ $money($totals['due']) }}</strong><small>já atendido ou vendido</small></div>
        <div class="stat" style="--c:#2f6fd0"><span>Previsto</span><strong>{{ $money($totals['expected']) }}</strong><small>agendado, ainda por acontecer</small></div>
        <div class="stat" style="--c:#a3271f"><span>Pagos sem nota</span><strong>{{ $totals['without_invoice'] }}</strong><small>lançamentos</small></div>
    </div>

    <div class="row" style="margin:.9rem 0">
        @foreach ([null => 'Todos', 'a-receber' => 'A receber', 'sem-nota' => 'Pagos sem nota'] as $value => $label)
            <a @class(['btn', 'sm', 'quiet' => $filter != $value]) href="{{ route('admin.finance', array_filter(['mes' => $month->format('Y-m'), 'filtro' => $value])) }}">{{ $label }}</a>
        @endforeach
    </div>

    @forelse ($entries as $e)
        <form class="card" method="post" action="{{ route('admin.finance.update', [$e['kind'], $e['id']]) }}">
            @csrf @method('put')
            <div class="row between">
                <div>
                    <strong>{{ $e['date']->format('d/m') }}</strong> ·
                    <a href="{{ route('admin.patients.show', $e['patient_id']) }}">{{ $e['patient'] }}</a>
                    <span class="muted small">· {{ $e['description'] }}</span>
                </div>
                <div>
                    @if ($e['paid'])<span class="badge done">pago</span>
                    @elseif ($e['due'])<span class="badge requested">a receber</span>
                    @else<span class="badge confirmed">previsto</span>@endif
                    @if ($e['invoiced'])<span class="badge done">nota {{ $e['invoice'] }}</span>@endif
                </div>
            </div>
            <div class="row" style="margin-top:.6rem; align-items:flex-end">
                <div style="width:8rem">
                    <label for="price-{{ $e['kind'] }}-{{ $e['id'] }}">Valor</label>
                    <input type="text" id="price-{{ $e['kind'] }}-{{ $e['id'] }}" name="price" inputmode="decimal" value="{{ $e['has_price'] ? number_format($e['cents'] / 100, 2, ',', '') : '' }}">
                </div>
                <label class="check" style="padding-bottom:.6rem"><input type="checkbox" name="paid" value="1" @checked($e['paid'])> <span>Pago</span></label>
                <div style="width:10rem">
                    <label for="method-{{ $e['kind'] }}-{{ $e['id'] }}">Forma</label>
                    <select id="method-{{ $e['kind'] }}-{{ $e['id'] }}" name="payment_method">
                        <option value="">—</option>
                        @foreach ($methods as $value => $label)<option value="{{ $value }}" @selected($e['method'] === $value)>{{ $label }}</option>@endforeach
                    </select>
                </div>
                <div class="grow">
                    <label for="invoice-{{ $e['kind'] }}-{{ $e['id'] }}">Nº da nota fiscal</label>
                    <input type="text" id="invoice-{{ $e['kind'] }}-{{ $e['id'] }}" name="invoice_number" value="{{ $e['invoice'] }}" placeholder="vazio = ainda não emitida">
                </div>
                <button class="btn sm">Salvar</button>
            </div>
        </form>
    @empty
        <div class="card muted">Nenhum lançamento {{ $filter ? 'com esse filtro ' : '' }}neste mês.</div>
    @endforelse

    <p class="muted small">A nota fiscal é emitida no sistema da prefeitura; aqui você anota o número, para saber o que já saiu e o que falta.</p>
@endsection
