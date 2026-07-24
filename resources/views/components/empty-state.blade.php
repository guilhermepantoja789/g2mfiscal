@props([
    'title' => 'Nada por aqui',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-slate-200 bg-surface-raised px-6 py-10 text-center']) }}>
    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-brand-soft text-brand">
        <x-icon name="information-circle" class="h-5 w-5" />
    </div>
    <p class="text-sm font-semibold text-surface-ink">{{ $title }}</p>
    @if($description)
        <p class="mt-1 text-sm text-surface-muted">{{ $description }}</p>
    @endif
    @isset($actions)
        <div class="mt-4 flex justify-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
