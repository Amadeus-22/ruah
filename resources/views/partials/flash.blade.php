@if (session('ok'))
    <div class="flash ok" role="status">{{ session('ok') }}</div>
@endif
@if (session('error'))
    <div class="flash error" role="alert">{{ session('error') }}</div>
@endif
@if ($errors->any())
    <div class="flash error" role="alert">
        @foreach ($errors->all() as $message)
            <div>{{ $message }}</div>
        @endforeach
    </div>
@endif
