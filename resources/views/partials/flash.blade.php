{{-- Mensagens de retorno. Só classes de base/components; o ícone leva tamanho por atributo. --}}
@php($flashIcon = fn (string $name) => str_replace('<svg', '<svg width="20" height="20"', \App\Support\Icon::svg($name)))
@if (session('ok'))
    <div class="flash ok row items-start" role="status">{!! $flashIcon('check') !!}<span class="grow">{{ session('ok') }}</span></div>
@endif
@if (session('error'))
    <div class="flash error row items-start" role="alert">{!! $flashIcon('alert') !!}<span class="grow">{{ session('error') }}</span></div>
@endif
@if ($errors->any())
    <div class="flash error row items-start" role="alert">
        {!! $flashIcon('alert') !!}
        <div class="grow">
            @foreach ($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    </div>
@endif
