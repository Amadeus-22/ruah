@extends('layouts.admin')
@section('title', 'Pacientes')
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/patients.css') }}?v={{ filemtime(public_path('css/pages/patients.css')) }}">
@endpush
@section('content')
<div class="page-patients">
    <div class="page-head">
        <h1>Pacientes</h1>
        <span class="muted small">{{ $patients->count() }} {{ $patients->count() === 1 ? 'paciente' : 'pacientes' }}</span>
    </div>
    <form class="row search mb-4" method="get" role="search">
        <div class="grow"><input type="search" name="q" value="{{ $search }}" placeholder="Buscar por nome ou telefone" aria-label="Buscar paciente"></div>
        <button class="btn">Buscar</button>
    </form>

    @if ($patients->isEmpty())
        <div class="empty">Nenhum paciente encontrado.</div>
    @else
        <div class="card patient-list">
            <div class="patient-row patient-head" aria-hidden="true">
                <span>Paciente</span><span>Responsável</span><span>Telefone</span><span>Atendimentos</span>
            </div>
            <ul>
                @foreach ($patients as $patient)
                    <li>
                        <a class="patient-row" href="{{ route('admin.patients.show', $patient) }}">
                            <span class="who">
                                <span class="avatar" aria-hidden="true"><span class="avatar-initials">{{ mb_strtoupper(mb_substr($patient->patient_name, 0, 1)) }}</span></span>
                                <strong>{{ $patient->patient_name }}</strong>
                            </span>
                            <span class="guardian"><span class="lbl">Resp.</span> {{ $patient->guardian_name }}</span>
                            <span class="phone">{{ $patient->phone }}</span>
                            <span class="count"><strong>{{ $patient->appointments_count }}</strong> <span class="lbl">{{ $patient->appointments_count === 1 ? 'atendimento' : 'atendimentos' }}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@endsection
