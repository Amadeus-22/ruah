@extends('layouts.admin')
@section('title', 'Serviços e pacotes')
@section('content')
    @php($money = fn (?int $cents) => $cents !== null ? number_format($cents / 100, 2, ',', '') : '')
    <h1>Serviços e pacotes</h1>
    <p class="muted">Os valores são sugestões: em cada agendamento você pode combinar outro.</p>

    <h2>Serviços</h2>
    @foreach ($services as $service)
        <form class="card row" method="post" action="{{ route('admin.services.save', $service) }}">
            @csrf
            <div class="grow"><label for="s-name-{{ $service->id }}">Nome</label><input type="text" id="s-name-{{ $service->id }}" name="name" value="{{ $service->name }}" required></div>
            <div style="width:7rem"><label for="s-dur-{{ $service->id }}">Minutos</label><input type="number" id="s-dur-{{ $service->id }}" name="duration_minutes" value="{{ $service->duration_minutes }}" min="5" required></div>
            <div style="width:8rem"><label for="s-price-{{ $service->id }}">Valor</label><input type="text" id="s-price-{{ $service->id }}" name="price" inputmode="decimal" value="{{ $money($service->suggested_price_cents) }}"></div>
            <div class="grow"><label for="s-desc-{{ $service->id }}">Descrição (aparece na página)</label><input type="text" id="s-desc-{{ $service->id }}" name="description" value="{{ $service->description }}"></div>
            <input type="hidden" name="active" value="0">
            <label class="check"><input type="checkbox" name="active" value="1" @checked($service->active)> <span>Disponível</span></label>
            <button class="btn sm">Salvar</button>
        </form>
    @endforeach
    <details class="card" @if($services->isEmpty()) open @endif>
        <summary>Adicionar serviço</summary>
        <form class="row" method="post" action="{{ route('admin.services.save') }}" style="margin-top:.7rem">
            @csrf
            <div class="grow"><label for="s-name">Nome</label><input type="text" id="s-name" name="name" required></div>
            <div style="width:7rem"><label for="s-dur">Minutos</label><input type="number" id="s-dur" name="duration_minutes" min="5" value="50" required></div>
            <div style="width:8rem"><label for="s-price">Valor</label><input type="text" id="s-price" name="price" inputmode="decimal"></div>
            <div class="grow"><label for="s-desc">Descrição</label><input type="text" id="s-desc" name="description"></div>
            <button class="btn sm">Adicionar</button>
        </form>
    </details>

    <h2>Pacotes</h2>
    @foreach ($packages as $package)
        <form class="card row" method="post" action="{{ route('admin.packages.save', $package) }}">
            @csrf
            <div class="grow"><label for="p-name-{{ $package->id }}">Nome</label><input type="text" id="p-name-{{ $package->id }}" name="name" value="{{ $package->name }}" required></div>
            <div class="grow">
                <label for="p-svc-{{ $package->id }}">Serviço</label>
                <select id="p-svc-{{ $package->id }}" name="service_id">
                    @foreach ($services as $service)<option value="{{ $service->id }}" @selected($service->id === $package->service_id)>{{ $service->name }}</option>@endforeach
                </select>
            </div>
            <div style="width:7rem"><label for="p-n-{{ $package->id }}">Sessões</label><input type="number" id="p-n-{{ $package->id }}" name="sessions" value="{{ $package->sessions }}" min="1" required></div>
            <div style="width:8rem"><label for="p-price-{{ $package->id }}">Valor</label><input type="text" id="p-price-{{ $package->id }}" name="price" inputmode="decimal" value="{{ $money($package->price_cents) }}"></div>
            <input type="hidden" name="active" value="0">
            <label class="check"><input type="checkbox" name="active" value="1" @checked($package->active)> <span>À venda</span></label>
            <button class="btn sm">Salvar</button>
        </form>
    @endforeach
    @if ($services->isNotEmpty())
        <details class="card">
            <summary>Adicionar pacote</summary>
            <form class="row" method="post" action="{{ route('admin.packages.save') }}" style="margin-top:.7rem">
                @csrf
                <div class="grow"><label for="p-name">Nome</label><input type="text" id="p-name" name="name" placeholder="10 sessões" required></div>
                <div class="grow">
                    <label for="p-svc">Serviço</label>
                    <select id="p-svc" name="service_id">@foreach ($services as $service)<option value="{{ $service->id }}">{{ $service->name }}</option>@endforeach</select>
                </div>
                <div style="width:7rem"><label for="p-n">Sessões</label><input type="number" id="p-n" name="sessions" min="1" value="10" required></div>
                <div style="width:8rem"><label for="p-price">Valor</label><input type="text" id="p-price" name="price" inputmode="decimal"></div>
                <button class="btn sm">Adicionar</button>
            </form>
        </details>
    @endif
@endsection
