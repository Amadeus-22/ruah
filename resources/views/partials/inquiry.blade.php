@php($number = $inquiry->whatsappNumber())
<div class="kcard">
    <div class="who">{{ $inquiry->guardian_name }}</div>
    <div class="meta">
        @if ($inquiry->patient_name)paciente {{ $inquiry->patient_name }} · @endif
        {{ \App\Models\Inquiry::CHANNELS[$inquiry->channel] ?? $inquiry->channel }} · {{ $inquiry->created_at->diffForHumans() }}
    </div>
    @if ($inquiry->note)<div class="meta">“{{ $inquiry->note }}”</div>@endif
    <div class="actions">
        <a class="btn sm" href="{{ route('admin.appointments.create', ['inquiry_id' => $inquiry->id]) }}">Agendar</a>
        @if ($inquiry->stage === \App\Models\Inquiry::STAGE_NEW)
            <form class="inline" method="post" action="{{ route('admin.inquiries.move', [$inquiry, 'waiting']) }}">@csrf<button class="btn sm quiet">Já respondi</button></form>
        @else
            <form class="inline" method="post" action="{{ route('admin.inquiries.move', [$inquiry, 'new']) }}">@csrf<button class="btn sm quiet">Voltar</button></form>
        @endif
        @if ($number)<a class="btn sm ghost" href="https://wa.me/{{ $number }}" target="_blank" rel="noopener">WhatsApp</a>@endif
        <form class="inline" method="post" action="{{ route('admin.inquiries.close', $inquiry) }}" onsubmit="return confirm('Arquivar este contato?')">@csrf<button class="btn sm danger">Arquivar</button></form>
    </div>
</div>
