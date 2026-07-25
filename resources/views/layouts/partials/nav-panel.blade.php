@php
    /** @var array $nav */
    $groups = $nav['groups'] ?? [];
@endphp

<aside
    x-show="activeGroup && !$store.pdv.opera"
    x-cloak
    x-transition:enter="transition ease-out duration-150"
    x-transition:enter-start="opacity-0 -translate-x-2"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 -translate-x-2"
    class="hidden md:flex md:fixed md:inset-y-0 md:left-rail md:z-20 md:w-panel md:flex-col bg-white border-r border-slate-200 shadow-sm"
    aria-label="Navegação do módulo"
>
    @foreach($groups as $group)
        <div x-show="activeGroup === '{{ $group['id'] }}'" class="flex h-full flex-col" x-cloak>
            <div class="flex h-14 flex-shrink-0 items-center justify-between gap-2 border-b border-slate-200 px-4">
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900 truncate">{{ $group['label'] }}</p>
                </div>
                <button
                    type="button"
                    @click="activeGroup = null"
                    class="rounded-md p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                    title="Recolher"
                >
                    <x-icon name="x-mark" class="h-4 w-4" />
                </button>
            </div>

            <nav class="flex-1 overflow-y-auto py-3 px-2 space-y-0.5">
                @foreach($group['items'] as $item)
                    <a
                        href="{{ $item['href'] }}"
                        class="flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition
                            {{ ($item['nested'] ?? false) ? 'pl-5 text-slate-500' : 'text-slate-700' }}
                            {{ ($item['active'] ?? false) ? 'bg-brand-soft text-brand' : 'hover:bg-slate-50 hover:text-slate-900' }}"
                    >
                        <x-icon :name="$item['icon']" class="h-4 w-4 flex-shrink-0 {{ ($item['active'] ?? false) ? 'text-brand' : 'text-slate-400' }}" />
                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                        @if(!empty($item['badge']))
                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-500">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>

            @if(!empty($group['hint']))
                <p class="border-t border-slate-100 px-4 py-3 text-xs text-slate-500">{{ $group['hint'] }}</p>
            @endif
        </div>
    @endforeach
</aside>
