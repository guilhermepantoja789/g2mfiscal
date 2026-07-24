<div
    x-show="spotlightOpen"
    x-cloak
    class="fixed inset-0 z-[60]"
    role="dialog"
    aria-modal="true"
    aria-label="Tour rápido"
>
    <div class="absolute inset-0 bg-slate-900/60" @click="skipSpotlight()"></div>

    <div class="relative mx-auto flex min-h-full max-w-lg items-center px-4 py-10">
        <div class="w-full rounded-xl border border-slate-200 bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-start gap-3">
                <div class="rounded-lg bg-brand-soft p-2 text-brand">
                    <x-icon name="light-bulb" class="h-5 w-5" />
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-wider text-brand">Tour rápido</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900" x-text="spotlightSteps[spotlightStep]?.title"></h2>
                </div>
            </div>

            <p class="text-sm leading-relaxed text-slate-600" x-text="spotlightSteps[spotlightStep]?.body"></p>

            <div class="mt-6 flex items-center justify-between gap-3">
                <button type="button" class="text-sm text-slate-500 hover:text-slate-700" @click="skipSpotlight()">
                    Pular
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-slate-400" x-text="(spotlightStep + 1) + ' / ' + spotlightSteps.length"></span>
                    <button
                        type="button"
                        class="rounded-lg bg-brand px-3 py-1.5 text-sm font-medium text-white hover:bg-brand-hover"
                        @click="nextSpotlight()"
                        x-text="spotlightStep === spotlightSteps.length - 1 ? 'Concluir' : 'Próximo'"
                    ></button>
                </div>
            </div>
        </div>
    </div>
</div>
