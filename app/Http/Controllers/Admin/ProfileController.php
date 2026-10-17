<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Nome, título, e-mail, foto e senha de quem usa o painel. */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.profile', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'remove_photo' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [], ['photo' => 'foto', 'password' => 'senha']);

        $changes = ['name' => $data['name'], 'title' => $data['title'] ?? null, 'email' => $data['email']];

        if ($request->hasFile('photo') || $request->boolean('remove_photo')) {
            if ($user->avatar_path) {
                Storage::disk('local')->delete($user->avatar_path);
            }
            $changes['avatar_path'] = $request->hasFile('photo')
                ? $request->file('photo')->store('avatars', 'local')
                : null;
        }
        if (! empty($data['password'])) {
            $changes['password'] = $data['password'];
        }

        $user->update($changes);

        return back()->with('ok', 'Perfil atualizado.');
    }

    /** A foto fica fora da pasta pública; só quem está logado a vê. */
    public function photo(Request $request): StreamedResponse
    {
        $path = $request->user()->avatar_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }
}
