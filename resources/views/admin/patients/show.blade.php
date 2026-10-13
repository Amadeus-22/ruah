@extends('layouts.admin')
@section('title', $patient->patient_name)
@section('content')
    <p class="small"><a href="{{ route('admin.patients.index') }}">← Pacientes</a></p>
    <div class="row between">
        <h1>{{ $patient->patient_name }}</h1>
        <a class="btn sm" href="{{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}">Agendar</a>
    </div>

    <h2>Pacotes</h2>
    @forelse ($patient->packages as $pp)
        <div class="card row between">
            <div>
                <strong>{{ $pp->package->name }}</strong>
                <p class="small muted">Comprado em {{ $pp->purchased_on->format('d/m/Y') }} · {{ \App\Support\Money::format($pp->price_cents) }} · {{ $pp->paid_at ? 'pago' : 'a receber' }}</p>
            </div>
            <div><strong>{{ $pp->remaining() }}</strong> <span class="muted">de {{ $pp->sessions_total }} sessões restantes</span></div>
        </div>
    @empty
        <div class="card muted">Nenhum pacote.</div>
    @endforelse

    @if ($packages->isNotEmpty())
        <details class="card">
            <summary>Registrar venda de pacote</summary>
            <form class="row" method="post" action="{{ route('admin.patients.packages', $patient) }}" style="margin-top:.7rem">
                @csrf
                <div class="grow">
                    <label for="package_id">Pacote</label>
                    <select id="package_id" name="package_id">
                        @foreach ($packages as $package)
                            <option value="{{ $package->id }}">{{ $package->name }} — {{ $package->sessions }} sessões — {{ \App\Support\Money::format($package->price_cents) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grow">
                    <label for="pkg_price">Valor combinado (vazio usa o do pacote)</label>
                    <input type="text" id="pkg_price" name="price" inputmode="decimal">
                </div>
                <label class="check"><input type="checkbox" name="paid" value="1"> <span>Já foi pago</span></label>
                <button class="btn sm">Registrar</button>
            </form>
        </details>
    @endif

    <h2>Cadastro</h2>
    <form class="card stack" method="post" action="{{ route('admin.patients.update', $patient) }}">
        @csrf @method('put')
        <div class="row">
            <div class="grow"><label for="patient_name">Paciente</label><input type="text" id="patient_name" name="patient_name" value="{{ old('patient_name', $patient->patient_name) }}" required></div>
            <div class="grow"><label for="guardian_name">Responsável</label><input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $patient->guardian_name) }}" required></div>
        </div>
        <div class="row">
            <div class="grow"><label for="phone">WhatsApp</label><input type="tel" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" required></div>
            <div class="grow"><label for="birth_date">Nascimento do paciente</label><input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $patient->birth_date?->toDateString()) }}"></div>
        </div>
        <div>
            <label for="notes">Observações de agenda</label>
            <textarea id="notes" name="notes" maxlength="500">{{ old('notes', $patient->notes) }}</textarea>
            <p class="muted small">Só o que ajuda a agendar (preferência de horário, quem traz). Informações clínicas não ficam aqui.</p>
        </div>
        <div><button class="btn sm">Salvar</button></div>
    </form>

    <h2>Histórico</h2>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Data</th><th>Serviço</th><th>Situação</th><th>Valor</th></tr></thead>
            <tbody>
            @forelse ($patient->appointments as $appointment)
                <tr>
                    <td>{{ $appointment->starts_at->format('d/m/Y H:i') }}</td>
                    <td>{{ $appointment->service->name }}</td>
                    <td><span class="badge {{ $appointment->status->value }}">{{ $appointment->status->label() }}</span></td>
                    <td>{{ $appointment->patient_package_id ? 'Pacote' : \App\Support\Money::format($appointment->price_cents) }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Nenhum atendimento ainda.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
