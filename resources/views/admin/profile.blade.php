@extends('layouts.admin')
@section('title', 'Meu perfil')
@section('content')
    <div class="page-head">
        <div>
            <h1>Meu perfil</h1>
            <p class="muted small">Seu nome, foto e acesso ao painel.</p>
        </div>
    </div>

    <form class="profile-grid" method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
        @csrf @method('put')
        <section class="card stack">
            <h3>Dados</h3>
            <div class="row">
                <div class="grow"><label for="p-name">Nome</label><input type="text" id="p-name" name="name" value="{{ old('name', $user->name) }}" required></div>
                <div class="grow"><label for="p-title">Título</label><input type="text" id="p-title" name="title" value="{{ old('title', $user->title) }}" placeholder="Fisioterapeuta"></div>
            </div>
            <div><label for="p-email">E-mail de acesso</label><input type="email" id="p-email" name="email" value="{{ old('email', $user->email) }}" required></div>
            @error('email')<div class="field-error">{{ $message }}</div>@enderror

            <h3 style="margin-top:1.2rem">Trocar senha</h3>
            <p class="muted small">Deixe em branco para manter a atual.</p>
            <div class="row">
                <div class="grow"><label for="p-pass">Nova senha</label><input type="password" id="p-pass" name="password" autocomplete="new-password"></div>
                <div class="grow"><label for="p-pass2">Repita a senha</label><input type="password" id="p-pass2" name="password_confirmation" autocomplete="new-password"></div>
            </div>
            @error('password')<div class="field-error">{{ $message }}</div>@enderror
            <div><button class="btn">Salvar perfil</button></div>
        </section>

        <section class="card profile-photo">
            <h3>Foto</h3>
            <x-avatar :user="$user" :size="112" class="avatar-xl" />
            <label class="btn sm ghost" for="p-photo">{!! \App\Support\Icon::svg('camera') !!} Escolher foto</label>
            <input type="file" id="p-photo" name="photo" accept="image/jpeg,image/png,image/webp" class="visually-hidden" onchange="this.form.requestSubmit()">
            @error('photo')<div class="field-error">{{ $message }}</div>@enderror
            @if ($user->avatar_path)
                <label class="check small"><input type="checkbox" name="remove_photo" value="1" onchange="this.form.requestSubmit()"> <span>Remover foto</span></label>
            @else
                <p class="muted small">Sem foto enviada, o painel usa a do Gravatar ligada ao seu e-mail, ou as suas iniciais.</p>
            @endif
        </section>
    </form>
@endsection
