@php
    /** @var array $nav */
    $groups = $nav['groups'] ?? [];
    $utility = $nav['utility'] ?? [];
@endphp

<aside
    data-spotlight="nav-rail"
    x-show="!$store.pdv.opera"
    class="hidden md:flex md:fixed md:inset-y-0 md:left-0 md:z-30 md:w-rail md:flex-col bg-slate-900 border-r border-slate-800"
    aria-label="Módulos"
>
    <div class="flex h-14 flex-shrink-0 items-center justify-center border-b border-slate-800">
        <span class="text-sm font-bold tracking-tight text-white">G2M</span>
    </div>

    <nav class="flex-1 overflow-y-auto py-3 space-y-1 px-2">
        @foreach($groups as $group)
            @php
                $isActive = $group['active'] ?? false;
                $direct = ! empty($group['href']) && count($group['items'] ?? []) <= 1;
                $short = trim(explode('—', str_replace(['Fiscal — ', 'Fiscal - '], '', $group['label']))[0]);
            @endphp
            @if($direct)
                <a
                    href="{{ $group['href'] }}"
                    @click="activeGroup = null"
                    class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 text-[10px] font-medium transition
                        {{ $isActive ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
                    title="{{ $group['label'] }}"
                >
                    <x-icon :name="$group['icon']" class="h-5 w-5 {{ ($group['accent'] ?? null) === 'brand' ? 'text-brand' : '' }}" />
                    <span class="truncate max-w-full leading-tight text-center">{{ $short }}</span>
                </a>
            @else
                <button
                    type="button"
                    @click="toggleGroup('{{ $group['id'] }}')"
                    class="w-full flex flex-col items-center gap-1 rounded-lg px-1 py-2 text-[10px] font-medium transition
                        {{ $isActive ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
                    :class="activeGroup === '{{ $group['id'] }}' ? 'ring-1 ring-brand/60 bg-slate-800 text-white' : ''"
                    title="{{ $group['label'] }}"
                >
                    <x-icon :name="$group['icon']" class="h-5 w-5 {{ ($group['accent'] ?? null) === 'brand' ? 'text-brand' : '' }}" />
                    <span class="truncate max-w-full leading-tight text-center">{{ $short }}</span>
                </button>
            @endif
        @endforeach
    </nav>

    <div class="flex-shrink-0 border-t border-slate-800 py-2 px-2 space-y-1">
        @foreach($utility as $u)
            <a
                href="{{ $u['href'] }}"
                class="flex flex-col items-center gap-1 rounded-lg px-1 py-2 text-[10px] font-medium transition
                    {{ ($u['active'] ?? false) ? 'bg-slate-800 text-white' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
                title="{{ $u['label'] }}"
            >
                <x-icon :name="$u['icon']" class="h-5 w-5" />
                <span class="truncate max-w-full leading-tight text-center">{{ strtok($u['label'], ' ') }}</span>
            </a>
        @endforeach
    </div>
</aside>
