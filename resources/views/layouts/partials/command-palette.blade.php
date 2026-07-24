@php
    $commandItems = $nav['command_items'] ?? [];
@endphp

<div
    x-show="cmdOpen"
    x-cloak
    class="fixed inset-0 z-50"
    role="dialog"
    aria-modal="true"
    aria-label="Busca rápida"
    @keydown.escape.window="cmdOpen = false"
>
    <div
        class="absolute inset-0 bg-slate-900/50"
        x-show="cmdOpen"
        x-transition.opacity
        @click="cmdOpen = false"
    ></div>

    <div class="relative mx-auto mt-[12vh] w-full max-w-lg px-4">
        <div
            data-spotlight="cmdk"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-2xl"
            x-show="cmdOpen"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            @click.outside="cmdOpen = false"
        >
            <div class="flex items-center gap-2 border-b border-slate-100 px-3">
                <x-icon name="search" class="h-5 w-5 text-slate-400" />
                <input
                    type="search"
                    x-ref="cmdInput"
                    x-model="cmdQuery"
                    @keydown.down.prevent="cmdMove(1)"
                    @keydown.up.prevent="cmdMove(-1)"
                    @keydown.enter.prevent="cmdGo()"
                    placeholder="Ir para…"
                    class="w-full border-0 bg-transparent py-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:ring-0"
                    autocomplete="off"
                />
                <kbd class="hidden sm:inline rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">esc</kbd>
            </div>

            <ul class="max-h-72 overflow-y-auto py-2" role="listbox">
                <template x-for="(item, index) in cmdFiltered" :key="item.href">
                    <li>
                        <a
                            :href="item.href"
                            @click="cmdOpen = false"
                            @mouseenter="cmdIndex = index"
                            class="flex items-center gap-3 px-3 py-2.5 text-sm"
                            :class="cmdIndex === index ? 'bg-brand-soft text-brand' : 'text-slate-700 hover:bg-slate-50'"
                            role="option"
                            :aria-selected="cmdIndex === index"
                        >
                            <span class="flex-1 truncate font-medium" x-text="item.label"></span>
                            <span class="text-xs text-slate-400 truncate" x-text="item.group"></span>
                        </a>
                    </li>
                </template>
                <li x-show="cmdFiltered.length === 0" class="px-3 py-6 text-center text-sm text-slate-500">
                    Nenhum destino encontrado.
                </li>
            </ul>
        </div>
    </div>
</div>

<script type="application/json" id="g2m-nav-commands">@json($commandItems)</script>
