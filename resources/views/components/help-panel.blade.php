@props([
    'id',
    'title' => 'Como usar esta tela',
    'open' => false,
])

<div
    data-spotlight="help-panel"
    x-data="{
        open: (() => {
            const key = 'help:{{ $id }}';
            const stored = localStorage.getItem(key);
            if (stored === '0') return false;
            if (stored === '1') return true;
            return {{ $open ? 'true' : 'false' }};
        })(),
        toggle() {
            this.open = !this.open;
            localStorage.setItem('help:{{ $id }}', this.open ? '1' : '0');
        }
    }"
    {{ $attributes->merge(['class' => 'rounded-lg border border-brand/20 bg-brand-soft/60']) }}
>
    <button
        type="button"
        @click="toggle()"
        class="flex w-full items-center gap-2 px-3 py-2.5 text-left text-sm font-medium text-brand"
    >
        <x-icon name="information-circle" class="h-4 w-4 flex-shrink-0" />
        <span class="flex-1">{{ $title }}</span>
        <x-icon name="chevron-down" class="h-4 w-4 transition" x-bind:class="open ? 'rotate-180' : ''" />
    </button>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="border-t border-brand/15 px-3 py-3 text-sm text-slate-700"
    >
        {{ $slot }}
    </div>
</div>
