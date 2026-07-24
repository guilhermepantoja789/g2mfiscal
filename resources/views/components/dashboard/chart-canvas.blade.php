@props([
    'id',
    'title' => null,
    'height' => 'h-72',
])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow rounded-lg p-6']) }}>
    @if($title)
        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">{{ $title }}</h3>
    @endif
    <div class="relative {{ $height }} w-full">
        <canvas id="{{ $id }}"></canvas>
    </div>
    {{ $slot }}
</div>
