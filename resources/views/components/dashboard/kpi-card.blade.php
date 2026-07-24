@props([
    'label',
    'value',
    'border' => 'blue',
    'hint' => null,
])

@php
    $borderClass = match ($border) {
        'green', 'emerald' => 'border-emerald-500',
        'yellow', 'amber' => 'border-amber-400',
        'red', 'rose' => 'border-rose-500',
        'indigo' => 'border-indigo-500',
        'teal', 'brand' => 'border-brand',
        'slate' => 'border-slate-400',
        default => 'border-blue-500',
    };
@endphp

<div {{ $attributes->merge(['class' => "bg-surface-raised overflow-hidden shadow-sm rounded-lg border border-slate-100 border-l-4 {$borderClass}"]) }}>
    <div class="p-5">
        <dl>
            <dt class="text-sm font-medium text-surface-muted truncate">{{ $label }}</dt>
            <dd class="mt-1 text-2xl font-bold text-surface-ink">{{ $value }}</dd>
            @if($hint)
                <dd class="mt-1 text-xs text-slate-400">{{ $hint }}</dd>
            @endif
        </dl>
    </div>
</div>
