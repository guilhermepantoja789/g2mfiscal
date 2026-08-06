{{-- Wizard fiscal LC116 → NBS → IndOp → CST (campos sempre no DOM) --}}
@php
    $wizardPadraoMun = $padraoMunicipal ?? ($isManaus ? '100' : '');
    $wizardTribNac = old('codigo_tributacao_nacional', isset($servico) ? $servico->codigo_tributacao_nacional : '');
    $wizardTribMun = old('codigo_tributacao_municipal', isset($servico) ? $servico->codigo_tributacao_municipal : $wizardPadraoMun);
    $wizardNbs = old('codigo_nbs', isset($servico) ? $servico->codigo_nbs : '');
    $wizardIndOp = old('c_ind_op', isset($servico) ? ($servico->c_ind_op ?? '100301') : '100301');
    $wizardCst = old('cst_ibscbs', isset($servico) ? ($servico->cst_ibscbs ?? '000') : '000');
    $wizardClass = old('c_class_trib', isset($servico) ? ($servico->c_class_trib ?? '000001') : '000001');
    $wizardPair = $wizardCst.'|'.$wizardClass;
    $wizardFin = old('fin_nfse', isset($servico) ? ($servico->fin_nfse ?? '0') : '0');
@endphp

<div
    class="space-y-4"
    x-data="servicoFiscalWizard(@js([
        'isManaus' => (bool) ($isManaus ?? false),
        'correlacoes' => $correlacoesJson ?? [],
        'nbsMap' => $nbsMap ?? [],
        'initialStep' => $errors->any() ? 4 : 1,
        'codigoNbs' => $wizardNbs,
        'cIndOp' => (string) $wizardIndOp,
        'classPair' => $wizardPair,
        'cst' => (string) $wizardCst,
        'cClassTrib' => (string) $wizardClass,
    ]))"
    x-init="init('{{ preg_replace('/\D/', '', (string) $wizardTribNac) }}')"
>
    <x-help-panel id="servico-ibscbs" title="Reforma tributária: o que mudou no cadastro" :open="true">
        <ul class="list-disc list-inside space-y-1 text-sm">
            <li><strong>cTribNac</strong> (LC 116, 6 dígitos) continua identificando o ISS — não é o NBS.</li>
            <li><strong>cNBS</strong> (9 dígitos) é obrigatório na DPS quando há grupo IBS/CBS. Sem ele a SEFIN rejeita.</li>
            <li><strong>cIndOp / CST / cClassTrib</strong> classificam a operação IBS/CBS. Sugestões vêm do Anexo VIII (orientação; confirme com seu contador).</li>
            @if($isManaus ?? false)
                <li class="text-amber-800"><strong>Manaus:</strong> mantenha o código municipal <strong>100</strong> (evita RNG6110).</li>
            @endif
            <li class="text-slate-500">Calendário NFS-e IBS/CBS (regime normal): Grupo 1 a partir de out/2026 e Grupo 2 a partir de dez/2026 (Ato Conjunto RFB/CGIBS). Simples/MEI: campos IBS/CBS em 2027 — mantenha NBS já preenchido.</li>
        </ul>
    </x-help-panel>

    {{-- Step indicator --}}
    <div class="flex flex-wrap gap-2 text-xs font-medium">
        <template x-for="s in [1,2,3,4]" :key="s">
            <button type="button" @click="step = s"
                    class="px-3 py-1.5 rounded-full border transition"
                    :class="step === s ? 'bg-indigo-600 text-white border-indigo-600' : (step > s ? 'bg-indigo-50 text-indigo-800 border-indigo-200' : 'bg-white text-gray-500 border-gray-200')">
                <span x-text="s + '. ' + labels[s]"></span>
            </button>
        </template>
    </div>

    {{-- Passo 1: LC116 + municipal --}}
    <div x-show="step === 1" class="bg-gray-50 p-4 rounded-md border border-gray-200 space-y-4">
        <h3 class="text-sm font-bold text-gray-800">1. Código LC 116 e municipal</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-bold text-gray-700">Cód. Tributação Nacional (cTribNac) *</label>
                <input type="text" list="lista-nacional" name="codigo_tributacao_nacional" x-ref="tribNac"
                       value="{{ $wizardTribNac }}" required
                       @change="onTribNacChange($event.target.value)"
                       @blur="onTribNacChange($event.target.value)"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                       placeholder="Ex: 010601">
                <datalist id="lista-nacional">
                    @foreach($codigosNacionais as $item)
                        <option value="{{ $item->codigo }}">{{ $item->item_lc116 }} - {{ \Illuminate\Support\Str::limit($item->descricao, 60) }}</option>
                    @endforeach
                </datalist>
                <p class="text-xs text-gray-500 mt-1">Lista LC 116 / tributação nacional (6 dígitos).</p>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700">Cód. Tributação Municipal *</label>
                <input type="text" name="codigo_tributacao_municipal" value="{{ $wizardTribMun }}" required
                       class="mt-1 block w-full rounded-md shadow-sm {{ ($isManaus ?? false) ? 'border-yellow-400 bg-yellow-50 font-bold text-yellow-800' : 'border-gray-300' }}">
                @if($isManaus ?? false)
                    <p class="text-xs text-yellow-700 mt-1 font-bold">Manaus: use 100</p>
                @endif
            </div>
        </div>
        <div class="flex justify-end">
            <button type="button" @click="go(2)" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Continuar → NBS</button>
        </div>
    </div>

    {{-- Passo 2: NBS --}}
    <div x-show="step === 2" x-cloak class="bg-gray-50 p-4 rounded-md border border-gray-200 space-y-4">
        <h3 class="text-sm font-bold text-gray-800">2. Código NBS (cNBS)</h3>
        <p class="text-xs text-gray-600">Obrigatório com IBS/CBS. Sugestões do Anexo VIII para o cTribNac informado:</p>
        <div x-show="sugestoesNbs.length" class="space-y-1 max-h-40 overflow-y-auto border border-indigo-100 rounded bg-white p-2">
            <template x-for="s in sugestoesNbs" :key="s.codigo">
                <button type="button" @click="aplicarNbs(s)"
                        class="block w-full text-left text-xs px-2 py-1.5 rounded hover:bg-indigo-50"
                        :class="codigoNbsDigits === s.codigo ? 'bg-indigo-100 font-bold' : ''">
                    <span class="font-mono" x-text="s.codigo"></span>
                    <span class="text-gray-500" x-text="' — ' + (nbsLabel(s.codigo) || s.escopo)"></span>
                </button>
            </template>
        </div>
        <p x-show="!sugestoesNbs.length" class="text-xs text-amber-700">Nenhuma correlação para este cTribNac — informe o NBS manualmente (9 dígitos).</p>
        <div>
            <label class="block text-sm font-bold text-gray-700">Código NBS *</label>
            <input type="text" name="codigo_nbs" list="lista-nbs" x-model="codigoNbs" required maxlength="12"
                   placeholder="115011000 ou 1.1501.10.00"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            <datalist id="lista-nbs">
                @foreach($nbsCodes as $nbs)
                    <option value="{{ $nbs->codigo }}">{{ \Illuminate\Support\Str::limit($nbs->descricao, 70) }}</option>
                @endforeach
            </datalist>
            @error('codigo_nbs')
                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
            @enderror
        </div>
        <div class="flex justify-between">
            <button type="button" @click="step = 1" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">← Voltar</button>
            <button type="button" @click="go(3)" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Continuar → IndOp</button>
        </div>
    </div>

    {{-- Passo 3: IndOp --}}
    <div x-show="step === 3" x-cloak class="bg-indigo-50 p-4 rounded-md border border-indigo-200 space-y-4">
        <h3 class="text-sm font-bold text-indigo-900">3. Indicador da operação (cIndOp)</h3>
        <input type="hidden" name="fin_nfse" value="{{ $wizardFin }}">
        <div>
            <label class="block text-sm font-bold text-gray-700">cIndOp *</label>
            <select name="c_ind_op" x-model="cIndOp" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                @foreach($indOps as $indOp)
                    <option value="{{ $indOp->codigo }}" {{ $wizardIndOp == $indOp->codigo ? 'selected' : '' }}
                            :data-sugerido="sugeridosIndOp.includes('{{ $indOp->codigo }}')">
                        {{ $indOp->codigo }} — {{ \Illuminate\Support\Str::limit($indOp->descricao, 70) }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-indigo-700 mt-1" x-show="sugeridosIndOp.length">
                Sugeridos para este serviço: <span class="font-mono" x-text="sugeridosIndOp.join(', ')"></span>
            </p>
        </div>
        <div class="flex justify-between">
            <button type="button" @click="step = 2" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">← Voltar</button>
            <button type="button" @click="go(4)" class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">Continuar → CST</button>
        </div>
    </div>

    {{-- Passo 4: CST / ClassTrib + revisão --}}
    <div x-show="step === 4" x-cloak class="bg-indigo-50 p-4 rounded-md border border-indigo-200 space-y-4">
        <h3 class="text-sm font-bold text-indigo-900">4. CST / Classificação tributária</h3>
        <div>
            <label class="block text-sm font-bold text-gray-700">CST / cClassTrib *</label>
            <select name="class_trib_pair" id="class_trib_pair" x-model="classPair" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                    @change="syncClassPair()">
                <optgroup label="Destaques (Manaus / TI / comuns)">
                    @foreach($classTribs->where('destaque', true) as $ct)
                        @php $pair = $ct->cst.'|'.$ct->c_class_trib; @endphp
                        <option value="{{ $pair }}" {{ $wizardPair === $pair ? 'selected' : '' }}>
                            {{ $ct->cst }}/{{ $ct->c_class_trib }} — {{ \Illuminate\Support\Str::limit($ct->descricao, 70) }}
                        </option>
                    @endforeach
                </optgroup>
                <optgroup label="Tabela completa">
                    @foreach($classTribs->where('destaque', false) as $ct)
                        @php $pair = $ct->cst.'|'.$ct->c_class_trib; @endphp
                        <option value="{{ $pair }}" {{ $wizardPair === $pair ? 'selected' : '' }}>
                            {{ $ct->cst }}/{{ $ct->c_class_trib }} — {{ \Illuminate\Support\Str::limit($ct->descricao, 70) }}
                        </option>
                    @endforeach
                </optgroup>
            </select>
            <input type="hidden" name="cst_ibscbs" id="cst_ibscbs" :value="cst">
            <input type="hidden" name="c_class_trib" id="c_class_trib" :value="cClassTrib">
        </div>
        <div class="rounded border border-indigo-100 bg-white p-3 text-xs text-gray-700 space-y-1">
            <p class="font-bold text-gray-900">Revisão</p>
            <p>cTribNac: <span class="font-mono" x-text="tribNacDigits || '—'"></span></p>
            <p>cNBS: <span class="font-mono" x-text="codigoNbsDigits || '—'"></span></p>
            <p>cIndOp: <span class="font-mono" x-text="cIndOp || '—'"></span></p>
            <p>CST/cClassTrib: <span class="font-mono" x-text="(cst || '—') + '/' + (cClassTrib || '—')"></span></p>
        </div>
        <div class="flex justify-start">
            <button type="button" @click="step = 3" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">← Voltar</button>
        </div>
    </div>
</div>

<script>
function servicoFiscalWizard(cfg) {
    return {
        step: cfg.initialStep || 1,
        labels: { 1: 'LC 116', 2: 'NBS', 3: 'IndOp', 4: 'CST' },
        correlacoes: cfg.correlacoes || {},
        nbsMap: cfg.nbsMap || {},
        tribNacDigits: '',
        codigoNbs: cfg.codigoNbs || '',
        cIndOp: cfg.cIndOp || '100301',
        classPair: cfg.classPair || '000|000001',
        cst: cfg.cst || '000',
        cClassTrib: cfg.cClassTrib || '000001',
        sugestoesNbs: [],
        sugeridosIndOp: [],
        get codigoNbsDigits() {
            return (this.codigoNbs || '').replace(/\D/g, '');
        },
        init(digits) {
            this.onTribNacChange(digits || '');
            this.syncClassPair();
        },
        nbsLabel(code) {
            return this.nbsMap[code] || '';
        },
        onTribNacChange(raw) {
            const digits = String(raw || '').replace(/\D/g, '');
            const key = digits.length <= 6 ? digits.padStart(6, '0') : digits.slice(0, 6);
            this.tribNacDigits = key;
            const rows = this.correlacoes[key] || this.correlacoes[digits] || [];
            this.sugestoesNbs = rows;
            this.sugeridosIndOp = [...new Set(rows.map(r => r.c_ind_op))];
        },
        aplicarNbs(s) {
            this.codigoNbs = s.codigo;
            if (s.c_ind_op) this.cIndOp = s.c_ind_op;
            if (s.cst && s.c_class_trib) {
                this.classPair = s.cst + '|' + s.c_class_trib;
                this.syncClassPair();
            }
        },
        syncClassPair() {
            const p = (this.classPair || '').split('|');
            this.cst = p[0] || '';
            this.cClassTrib = p[1] || '';
        },
        go(n) {
            this.step = n;
        },
    };
}
</script>
