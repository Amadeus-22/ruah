@extends('layouts.admin')
@section('title', 'Pacientes')
@section('content')
    <h1>Pacientes</h1>
    <form class="row" method="get" style="margin: .6rem 0 1rem">
        <div class="grow"><input type="text" name="q" value="{{ $search }}" placeholder="Buscar por nome ou telefone" aria-label="Buscar paciente"></div>
        <button class="btn sm">Buscar</button>
    </form>
    <div class="card table-wrap">
        <table>
            <thead><tr><th>Paciente</th><th>Responsável</th><th>Telefone</th><th>Atendimentos</th></tr></thead>
            <tbody>
            @forelse ($patients as $patient)
                <tr>
                    <td><a href="{{ route('admin.patients.show', $patient) }}">{{ $patient->patient_name }}</a></td>
                    <td>{{ $patient->guardian_name }}</td>
                    <td>{{ $patient->phone }}</td>
                    <td>{{ $patient->appointments_count }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Nenhum paciente encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
@endsection
