@props(['rating' => 0, 'size' => 'size-[18px]'])

<div class="flex gap-0.5" role="img" aria-label="Rating {{ number_format((float) $rating, 1) }} dari 5">
    @for ($i = 1; $i <= 5; $i++)
        <svg class="{{ $size }} {{ $i <= round($rating) ? 'text-[#d4a72c]' : 'text-[#d6dee2]' }}" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M12 17.27 18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
        </svg>
    @endfor
</div>