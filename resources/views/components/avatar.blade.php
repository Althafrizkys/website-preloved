@props(['user', 'size' => 'size-[120px]', 'text' => 'text-5xl'])

@if ($user->profile_photo)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo) }}" alt="{{ $user->name }}" class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <span class="{{ $size }} {{ $text }} flex shrink-0 items-center justify-center rounded-full bg-[#00523f] font-['Poppins'] font-semibold text-white" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
@endif