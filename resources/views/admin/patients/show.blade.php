@extends('layouts.admin')
@section('title', $patient->patient_name)
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/patients.css') }}?v={{ filemtime(public_path('css/pages/patients.css')) }}">
@endpush
@section('content')
<div class="page-patients">
    <p class="small mt-0 mb-2"><a class="back" href="{{ route('admin.patients.index') }}">← Pacientes</a></p>
    <div class="page-head">
        <div>
            <h1>{{ $patient->patient_name }}</h1>
            <p class="muted small mb-0">Resp. {{ $patient->guardian_name }} · {{ $patient->phone }}</p>
        </div>
        <a class="btn" href="{{ route('admin.appointments.create', ['patient_id' => $patient->id]) }}">Agendar</a>
    </div>

    <div class="patient-cols">
        <section>
            <h2 class="section-label">Pacotes</h2>
            @forelse ($patient->packages as $pp)
                <div class="card package">
                    <div>
                        <strong>{{ $pp->package->name }}</strong>
                        <p class="small muted">Comprado em {{ $pp->purchased_on->format('d/m/Y') }} · {{ \App\Support\Money::format($pp->price_cents) }} · {{ $pp->paid_at ? 'pago' : 'a receber' }}</p>
                    </div>
                    <div class="remaining"><strong>{{ $pp->remaining() }}</strong> <span class="muted small">de {{ $pp->sessions_total }} sessões restantes</span></div>
                </div>
            @empty
                <div class="card muted">Nenhum pacote.</div>
            @endforelse

            @if ($packages->isNotEmpty())
                <details class="card">
                    <summary>Registrar venda de pacote</summary>
                    <form class="stack mt-3" method="post" action="{{ route('admin.patients.packages', $patient) }}">
                        @csrf
                        <div>
                            <label for="package_id">Pacote</label>
                            <select id="package_id" name="package_id">
                                @foreach ($packages as $package)
                                    <option value="{{ $package->id }}">{{ $package->name }} — {{ $package->sessions }} sessões — {{ \App\Support\Money::format($package->price_cents) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="pkg_price">Valor combinado (vazio usa o do pacote)</label>
                            <input type="text" id="pkg_price" name="price" inputmode="decimal">
                        </div>
                        <label class="check"><input type="checkbox" name="paid" value="1"> <span>Já foi pago</span></label>
                        <button class="btn sm">Registrar</button>
                    </form>
                </details>
            @endif

            <h2 class="section-label">Cadastro</h2>
            <form class="card stack" method="post" action="{{ route('admin.patients.update', $patient) }}">
                @csrf @method('put')
                <div class="form-grid two">
                    <div><label for="patient_name">Paciente</label><input type="text" id="patient_name" name="patient_name" value="{{ old('patient_name', $patient->patient_name) }}" required></div>
                    <div><label for="guardian_name">Responsável</label><input type="text" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $patient->guardian_name) }}" required></div>
                    <div><label for="phone">WhatsApp</label><input type="tel" id="phone" name="phone" value="{{ old('phone', $patient->phone) }}" required></div>
                    <div><label for="birth_date">Nascimento do paciente</label><input type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $patient->birth_date?->toDateString()) }}"></div>
                </div>
                <div>
                    <label for="notes">Observações de agenda</label>
                    <textarea id="notes" name="notes" maxlength="500">{{ old('notes', $patient->notes) }}</textarea>
                    <p class="muted small">Só o que ajuda a agendar (preferência de horário, quem traz). Informações clínicas não ficam aqui.</p>
                </div>
                <div><button class="btn">Salvar</button></div>
            </form>
        </section>

        <section>
            <h2 class="section-label">Histórico</h2>
            <div class="card history">
                @forelse ($patient->appointments as $appointment)
                    <div class="visit">
                        <div class="visit-main">
                            <strong>{{ $appointment->starts_at->format('d/m/Y H:i') }}</strong>
                            <span class="muted small">{{ $appointment->service->name }}</span>
                        </div>
                        <div class="visit-side">
                            <span class="badge {{ $appointment->status->value }}">{{ $appointment->status->label() }}</span>
                            <span class="small">{{ $appointment->patient_package_id ? 'Pacote' : \App\Support\Money::format($appointment->price_cents) }}</span>
                        </div>
                    </div>
                @empty
                    <p class="muted my-0">Nenhum atendimento ainda.</p>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
