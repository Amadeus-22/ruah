@extends('layouts.public')
@section('title', 'Privacidade')
@section('content')
    <h1>Como seus dados são usados</h1>
    <div class="card stack">
        <div>
            <h3>O que é pedido</h3>
            <p>Nome do responsável, nome do paciente, WhatsApp e, se você quiser, uma observação sobre o horário.</p>
        </div>
        <div>
            <h3>Para que serve</h3>
            <p>Só para marcar, confirmar e lembrar o atendimento, e para o controle de pagamentos do consultório. Os dados não são vendidos nem usados para propaganda.</p>
        </div>
        <div>
            <h3>O que não é guardado aqui</h3>
            <p>Este sistema não guarda prontuário, diagnóstico nem informações de saúde. Por isso, não escreva esse tipo de informação no campo de observação.</p>
        </div>
        <div>
            <h3>Quem tem acesso</h3>
            <p>Apenas a profissional responsável por {{ \App\Models\Setting::get('clinic_name') }}.</p>
        </div>
        <div>
            <h3>Seus direitos</h3>
            <p>Você pode pedir para ver, corrigir ou apagar seus dados a qualquer momento, falando diretamente com a profissional.</p>
        </div>
    </div>
    <p class="small"><a href="{{ route('public.index') }}">← Voltar para o agendamento</a></p>
@endsection
