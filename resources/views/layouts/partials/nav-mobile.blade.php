@php
    /** @var array $nav */
    $mobilePrimary = $nav['mobile_primary'] ?? [];
    $groups = $nav['groups'] ?? [];
    $utility = $nav['utility'] ?? [];
@endphp

<nav
    data-spotlight="nav-mobile"
    class="fixed inset-x-0 bottom-0 z-30 md:hidden border-t border-slate-200 bg-white/95 backdrop-blur pb-[env(safe-area-inset-bottom)]"
    aria-label="Navegação principal"
>
    <div class="flex items-stretch justify-around px-1">
        @foreach($mobilePrimary as $group)
            @php
                $href = $group['href'] ?? ($group['items'][0]['href'] ?? '#');
                $isActive = $group['active'] ?? false;
                $short = trim(explode('—', str_replace('Fiscal — ', '', $group['label']))[0]);
            @endphp
            <a
                href="{{ $href }}"
                class="flex flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-medium
                    {{ $isActive ? 'text-brand' : 'text-slate-500' }}"
            >
                <x-icon :name="$group['icon']" class="h-5 w-5 {{ $isActive ? 'text-brand' : 'text-slate-400' }}" />
                <span class="truncate max-w-[4.5rem]">{{ $short }}</span>
            </a>
        @endforeach

        <button
            type="button"
            @click="mobileMore = true"
            class="flex flex-1 flex-col items-center gap-0.5 px-1 py-2 text-[10px] font-medium text-slate-500"
        >
            <x-icon name="ellipsis-horizontal" class="h-5 w-5 text-slate-400" />
            <span>Mais</span>
        </button>
    </div>
</nav>

{{-- Mobile sheet --}}
<div
    x-show="mobileMore"
    x-cloak
    class="fixed inset-0 z-40 md:hidden"
    role="dialog"
    aria-modal="true"
    aria-label="Mais opções"
>
    <div
        class="absolute inset-0 bg-slate-900/50"
        x-show="mobileMore"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="mobileMore = false"
    ></div>

    <div
        class="absolute inset-x-0 bottom-0 max-h-[85vh] overflow-y-auto rounded-t-2xl bg-white shadow-xl"
        x-show="mobileMore"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="translate-y-full"
        x-transition:enter-end="translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="translate-y-0"
        x-transition:leave-end="translate-y-full"
        @click.away="mobileMore = false"
    >
        <div class="sticky top-0 flex items-center justify-between border-b border-slate-100 bg-white px-4 py-3">
            <p class="text-sm font-semibold text-slate-900">Navegação</p>
            <button type="button" @click="mobileMore = false" class="rounded-md p-1 text-slate-400 hover:bg-slate-100">
                <x-icon name="x-mark" class="h-5 w-5" />
            </button>
        </div>

        <div class="px-2 py-3 space-y-4 pb-8">
            @foreach($groups as $group)
                <div>
                    <p class="px-2 mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">{{ $group['label'] }}</p>
                    <div class="space-y-0.5">
                        @foreach($group['items'] as $item)
                            <a
                                href="{{ $item['href'] }}"
                                @click="mobileMore = false"
                                class="flex items-center gap-2.5 rounded-md px-2.5 py-2.5 text-sm font-medium
                                    {{ ($item['active'] ?? false) ? 'bg-brand-soft text-brand' : 'text-slate-700 hover:bg-slate-50' }}"
                            >
                                <x-icon :name="$item['icon']" class="h-4 w-4 text-slate-400" />
                                <span class="flex-1">{{ $item['label'] }}</span>
                                @if(!empty($item['badge']))
                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-500">{{ $item['badge'] }}</span>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="border-t border-slate-100 pt-3 space-y-0.5">
                @foreach($utility as $u)
                    <a
                        href="{{ $u['href'] }}"
                        @click="mobileMore = false"
                        class="flex items-center gap-2.5 rounded-md px-2.5 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    >
                        <x-icon :name="$u['icon']" class="h-4 w-4 text-slate-400" />
                        <span>{{ $u['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
