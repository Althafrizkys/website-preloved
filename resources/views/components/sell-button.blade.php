@props([
    'label' => 'Mulai Jual',
    'class' => '',
])

@auth
    @if (auth()->user()->isAdmin())
        <a href="{{ route('admin.dashboard') }}" {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</a>
    @else
        <a href="{{ route('products.create') }}" {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</a>
    @endif
@else
    <a href="{{ route('register') }}" {{ $attributes->merge(['class' => $class]) }}>{{ $label }}</a>
@endauth
