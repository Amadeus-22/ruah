@props(['user', 'size' => 36])
{{-- Foto enviada > Gravatar > iniciais. O Gravatar some sozinho quando o e-mail não tem foto. --}}
<span {{ $attributes->merge(['class' => 'avatar']) }} style="--s: {{ $size }}px" aria-hidden="true">
    <span class="avatar-initials">{{ $user->initials() }}</span>
    @if ($user->avatarUrl())
        <img src="{{ $user->avatarUrl() }}" alt="">
    @else
        <img src="{{ $user->gravatarUrl($size * 2) }}" alt="" loading="lazy" onerror="this.remove()">
    @endif
</span>
