@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between']) }}>
    <div class="min-w-0 space-y-1">
        <h2 class="text-xl font-semibold tracking-tight text-slate-900 sm:text-2xl">{{ $title }}</h2>
        @if($subtitle)
            <p class="text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
        @isset($help)
            <div class="pt-2">
                {{ $help }}
            </div>
        @endisset
    </div>
    @isset($actions)
        <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
