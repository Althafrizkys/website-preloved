@php $size = $size ?? 'size-12'; @endphp
@if ($user?->profile_photo)
    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->profile_photo) }}" alt="{{ $user->name }}" class="{{ $size }} shrink-0 rounded-full object-cover">
@else
    <span class="{{ $size }} flex shrink-0 items-center justify-center rounded-full bg-[#e6f4f0] text-[14px] font-semibold text-[#236a5a]" aria-hidden="true">{{ mb_strtoupper(mb_substr($user?->name ?? '?', 0, 1)) }}</span>
@endif