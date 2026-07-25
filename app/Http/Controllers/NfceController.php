<?php

namespace App\Http\Controllers;

use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;
use App\Jobs\EmitirNfceJob;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\ModuloDashboardService;
use App\Services\Fiscal\NfceDanfeService;
use App\Services\Fiscal\NfceEmitRequest;
use App\Services\Fiscal\RawNativeNfceIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class NfceController extends Controller
{
    public function dashboard(Request $request, ModuloDashboardService $dashboards)
    {
        $empresa = $this->empresaAtiva();
        [$inicio, $fim] = $dashboards->resolvePeriod($request);
        $stats = $dashboards->nfce($empresa->id, $inicio, $fim);

        return view('nfces.dashboard', compact('empresa', 'stats', 'inicio', 'fim'));
    }

    public function index()
    {
        $empresa = $this->empresaAtiva();
        $nfces = Nfce::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->paginate(20);

        return view('nfces.index', compact('empresa', 'nfces'));
    }

    public function create()
    {
        $empresa = $this->empresaAtiva();

        return view('nfces.create', compact('empresa'));
    }

    public function store(Request $request, RawNativeNfceIssuer $issuer)
    {
        $empresa = $this->empresaAtiva();

        $validated = $request->validate([
            'itens' => 'required|array|min:1|max:100',
            'itens.*.descricao' => 'required|string|max:120',
            'itens.*.ncm' => 'required|string|max:8',
            'itens.*.cfop' => 'required|in:5102,5405',
            'itens.*.csosn' => 'required|in:102,500',
            'itens.*.unidade' => 'required|string|max:6',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.valor_unitario' => 'required|numeric|min:0.01',
            'pagamentos' => 'required|array|min:1',
            'pagamentos.*.t_pag' => 'required|in:01,03,04,17',
            'pagamentos.*.v_pag' => 'required|numeric|min:0.01',
            'pagamentos.*.v_troco' => 'nullable|numeric|min:0',
            't_pag' => 'nullable|in:01,03,04,17',
            'v_troco' => 'nullable|numeric|min:0',
            'dest_doc' => 'nullable|string|max:18',
            'dest_nome' => 'nullable|string|max:120',
        ]);

        $itensPayload = [];
        $total = 0.0;
        foreach ($validated['itens'] as $item) {
            $qtd = (float) $item['quantidade'];
            $vu = (float) $item['valor_unitario'];
            $total = round($total + ($qtd * $vu), 2);
            $itensPayload[] = [
                'descricao' => $item['descricao'],
                'ncm' => preg_replace('/\D/', '', $item['ncm']),
                'cfop' => $item['cfop'],
                'csosn' => $item['csosn'],
                'unidade' => $item['unidade'],
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
                'pis_cst' => '49',
                'cofins_cst' => '49',
            ];
        }

        $pagamentosPayload = [];
        $somaPag = 0.0;
        foreach ($validated['pagamentos'] as $idx => $pag) {
            $tPag = $pag['t_pag'];
            $vPag = round((float) $pag['v_pag'], 2);
            $vTroco = isset($pag['v_troco']) ? round((float) $pag['v_troco'], 2) : null;
            if ($vTroco !== null && $vTroco > 0 && $tPag !== '01') {
                return back()->withErrors(['pagamentos' => 'Troco só é permitido em dinheiro.'])->withInput();
            }
            $somaPag = round($somaPag + $vPag, 2);
            $pagamentosPayload[] = [
                't_pag' => $tPag,
                'v_pag' => $vPag,
                'v_troco' => $tPag === '01' ? $vTroco : null,
            ];
        }

        // No NFC-e, dinheiro com troco: v_pag da linha inclui o troco; a soma dos v_pag
        // (sem troco embutido nas outras) deve cobrir o total. Aceitamos soma == total
        // ou, se houver troco em dinheiro, soma_valores_liquidos == total.
        $trocoTotal = 0.0;
        foreach ($pagamentosPayload as $p) {
            if (($p['t_pag'] ?? '') === '01' && ($p['v_troco'] ?? 0) > 0) {
                $trocoTotal = round($trocoTotal + (float) $p['v_troco'], 2);
            }
        }
        $somaLiquida = round($somaPag - $trocoTotal, 2);
        if (abs($somaLiquida - $total) > 0.01 && abs($somaPag - $total) > 0.01) {
            return back()->withErrors([
                'pagamentos' => sprintf(
                    'A soma dos pagamentos (R$ %.2f) deve ser igual ao total (R$ %.2f).',
                    $somaLiquida,
                    $total
                ),
            ])->withInput();
        }

        [$numero, $serie, $ambiente] = $issuer->reservarNumero($empresa);

        $emContingencia = (bool) $empresa->nfce_contingencia;
        $tpEmis = $emContingencia ? 9 : 1;

        $payload = [
            'itens' => $itensPayload,
            'pagamentos' => $pagamentosPayload,
            'dest_doc' => $validated['dest_doc'] ? preg_replace('/\D/', '', $validated['dest_doc']) : null,
            'dest_nome' => $validated['dest_nome'] ?? null,
            'natureza' => 'VENDA',
            'x_just_contingencia' => $emContingencia
                ? ($empresa->nfce_contingencia_motivo ?: 'Falha de comunicacao com a SEFAZ')
                : null,
        ];

        $nfce = Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => $numero,
            'serie' => $serie,
            'ambiente' => $ambiente,
            'tp_emis' => $tpEmis,
            'status' => 'processando',
            'payload' => $payload,
            'valor_total' => $total,
            'destinatario_doc' => $payload['dest_doc'],
            'destinatario_nome' => $payload['dest_nome'],
        ]);

        EmitirNfceJob::dispatch($nfce);

        $msg = $emContingencia
            ? 'NFC-e em contingência: será gerada offline e transmitida quando a SEFAZ voltar.'
            : 'NFC-e enviada para processamento assíncrono.';

        return redirect()->route('nfces.show', $nfce->id)->with('success', $msg);
    }

    public function show(int $id)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->with('documentoComercial')->findOrFail($id);

        return view('nfces.show', compact('empresa', 'nfce'));
    }

    public function imprimir(int $id, NfceDanfeService $danfe)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        if (! $nfce->podeImprimirDanfe()) {
            return back()->with('error', 'DANFE disponível apenas para notas autorizadas, em contingência ou canceladas.');
        }

        try {
            return $danfe->download($nfce);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancelar(Request $request, int $id, RawNativeNfceIssuer $issuer, DocumentoOrchestrator $orchestrator)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        $validated = $request->validate([
            'motivo' => 'required|string|min:15|max:255',
        ]);

        try {
            $result = $issuer->cancelar($nfce, $validated['motivo']);

            $nfce->refresh();
            if ($nfce->documento_comercial_id) {
                $doc = DocumentoComercial::query()->find($nfce->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalCancelado($doc, $validated['motivo']);
                }
            }

            return redirect()->route('nfces.show', $nfce->id)
                ->with('success', "Cancelamento homologado [{$result->cStat}]: {$result->xMotivo}");
        } catch (SefazRejectionException $e) {
            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', "Cancelamento rejeitado [{$e->cStat}]: {$e->xMotivo}");
        } catch (\Throwable $e) {
            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', $e->getMessage());
        }
    }

    public function transmitir(int $id)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        if (! $nfce->isPendenteTransmissao()) {
            return back()->with('error', 'Somente NFC-e em contingência (pendente_transmissao) pode ser retransmitida.');
        }

        \App\Jobs\TransmitirNfceContingenciaJob::dispatch($nfce);

        return redirect()->route('nfces.show', $nfce->id)
            ->with('success', 'Transmissão de contingência enfileirada.');
    }

    public function recuperarDuplicidade(int $id, RawNativeNfceIssuer $issuer, \App\Services\Erp\DocumentoOrchestrator $orchestrator)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        try {
            $result = $issuer->recuperarDuplicidade($nfce);
            $nfce = $nfce->fresh();

            if ($nfce->documento_comercial_id && $nfce->status === 'autorizada') {
                $doc = \App\Models\DocumentoComercial::find($nfce->documento_comercial_id);
                if ($doc) {
                    $orchestrator->onFiscalAutorizado($doc);
                }
            }

            return redirect()->route('nfces.show', $nfce->id)
                ->with('success', "Duplicidade recuperada [{$result->cStat}]: chave {$result->chave}");
        } catch (SefazRejectionException $e) {
            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', "Recuperação rejeitada [{$e->cStat}]: {$e->xMotivo}");
        } catch (\Throwable $e) {
            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', $e->getMessage());
        }
    }

    public function inutilizarForm()
    {
        $empresa = $this->empresaAtiva();

        return view('nfces.inutilizar', compact('empresa'));
    }

    public function inutilizar(Request $request, RawNativeNfceIssuer $issuer)
    {
        $empresa = $this->empresaAtiva();

        $validated = $request->validate([
            'serie' => 'required|integer|min:0|max:999',
            'numero_ini' => 'required|integer|min:1',
            'numero_fin' => 'required|integer|min:1|gte:numero_ini',
            'x_just' => 'required|string|min:15|max:255',
            'profile' => 'nullable|in:homolog_nac,homolog,producao',
        ]);

        try {
            $result = $issuer->inutilizar(
                empresa: $empresa,
                serie: (int) $validated['serie'],
                numeroIni: (int) $validated['numero_ini'],
                numeroFin: (int) $validated['numero_fin'],
                xJust: $validated['x_just'],
                endpointProfile: $validated['profile'] ?? null,
            );

            return redirect()->route('nfces.inutilizar.form')
                ->with('success', "Inutilização homologada [{$result->cStat}]: {$result->xMotivo} — prot. {$result->protocolo}");
        } catch (SefazRejectionException $e) {
            return back()->withInput()->with('error', "Inutilização rejeitada [{$e->cStat}]: {$e->xMotivo}");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function laboratorio()
    {
        $empresa = $this->empresaAtiva();
        $checks = $this->checklistHomologacao($empresa);
        $recentes = Nfce::query()
            ->where('empresa_id', $empresa->id)
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        return view('nfces.laboratorio', [
            'empresa' => $empresa,
            'checks' => $checks,
            'pronto' => collect($checks)->every(fn ($c) => $c['ok']),
            'recentes' => $recentes,
            'perfilDefault' => config('nfce.endpoint_profile', 'homolog_nac'),
        ]);
    }

    public function statusServico(Request $request, RawNativeNfceIssuer $issuer)
    {
        $empresa = $this->empresaAtiva();
        $profile = $request->validate([
            'profile' => 'nullable|in:homolog_nac,homolog,producao',
        ])['profile'] ?? config('nfce.endpoint_profile', 'homolog_nac');

        try {
            $ret = $issuer->statusServico($empresa, $profile);

            return back()->with('lab_status', [
                'ok' => ($ret['cStat'] ?? '') === '107',
                'cStat' => $ret['cStat'] ?? '',
                'xMotivo' => $ret['xMotivo'] ?? '',
                'profile' => $profile,
            ]);
        } catch (\Throwable $e) {
            return back()->with('lab_status', [
                'ok' => false,
                'cStat' => '',
                'xMotivo' => $e->getMessage(),
                'profile' => $profile,
            ]);
        }
    }

    public function emitirTeste(Request $request, RawNativeNfceIssuer $issuer)
    {
        $empresa = $this->empresaAtiva();
        $checks = $this->checklistHomologacao($empresa);
        if (collect($checks)->contains(fn ($c) => ! $c['ok'])) {
            return back()->withErrors(['lab' => 'Complete os pré-requisitos do checklist antes de emitir.']);
        }

        $validated = $request->validate([
            'profile' => 'required|in:homolog_nac,homolog,producao',
            'valor' => 'required|numeric|min:0.01|max:100',
            'modo' => 'required|in:sync,fila',
        ]);

        $valor = (float) $validated['valor'];
        $profile = $validated['profile'];

        [$numero, $serie, $ambiente] = $issuer->reservarNumero($empresa);

        $payload = [
            'itens' => [[
                'descricao' => 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL',
                'ncm' => '22021000',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
                'quantidade' => 1,
                'valor_unitario' => $valor,
                'pis_cst' => '49',
                'cofins_cst' => '49',
            ]],
            'pagamentos' => [[
                't_pag' => '01',
                'v_pag' => $valor,
                'v_troco' => null,
            ]],
            'dest_doc' => null,
            'dest_nome' => null,
            'natureza' => 'VENDA',
            'endpoint_profile' => $profile,
        ];

        $nfce = Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => $numero,
            'serie' => $serie,
            'ambiente' => $ambiente,
            'tp_emis' => 1,
            'status' => 'processando',
            'payload' => $payload,
            'valor_total' => $valor,
        ]);

        if ($validated['modo'] === 'fila') {
            EmitirNfceJob::dispatch($nfce);

            return redirect()->route('nfces.show', $nfce->id)
                ->with('success', "Teste #{$nfce->id} enviado para a fila (perfil {$profile}).");
        }

        try {
            $requestEmit = new NfceEmitRequest(
                itens: [
                    new NfceItem(
                        descricao: $payload['itens'][0]['descricao'],
                        ncm: '22021000',
                        cfop: '5102',
                        unidade: 'UN',
                        quantidade: 1,
                        valorUnitario: $valor,
                    ),
                ],
                pagamentos: [new NfcePayment('01', $valor)],
                numeroOverride: $numero,
                endpointProfile: $profile,
            );

            $result = $issuer->emit($empresa, $requestEmit, $nfce);

            return redirect()->route('nfces.show', $nfce->id)
                ->with('success', "Autorizada! Chave {$result->chave} — protocolo {$result->protocolo}");
        } catch (SefazRejectionException $e) {
            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', "Rejeitada [{$e->cStat}]: {$e->xMotivo}");
        } catch (SefazTransportException $e) {
            $nfce->update(['status' => 'erro', 'x_motivo' => $e->getMessage()]);

            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', 'Falha de transporte: '.$e->getMessage());
        } catch (\Throwable $e) {
            $nfce->update(['status' => 'erro', 'x_motivo' => $e->getMessage()]);

            return redirect()->route('nfces.show', $nfce->id)
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @return list<array{label: string, ok: bool, detail: string}>
     */
    private function checklistHomologacao(Empresa $empresa): array
    {
        $cert = $empresa->certificado;
        $certOk = $cert && $cert->ativo;
        $certDetail = 'Ausente';
        if ($certOk) {
            $ate = $cert->valido_ate;
            if ($ate instanceof \DateTimeInterface) {
                $certDetail = 'Válido até '.$ate->format('d/m/Y');
            } elseif (is_string($ate) && $ate !== '') {
                $certDetail = 'Válido até '.$ate;
            } else {
                $certDetail = 'Ativo';
            }
        }

        return [
            [
                'label' => 'UF Amazonas',
                'ok' => strtoupper((string) $empresa->uf) === 'AM',
                'detail' => (string) ($empresa->uf ?: '—'),
            ],
            [
                'label' => 'Inscrição Estadual',
                'ok' => ! empty($empresa->inscricao_estadual),
                'detail' => (string) ($empresa->inscricao_estadual ?: 'Não configurada'),
            ],
            [
                'label' => 'CSC idToken',
                'ok' => ! empty($empresa->nfce_csc_id),
                'detail' => $empresa->nfce_csc_id ? 'ID '.$empresa->nfce_csc_id : 'Não configurado',
            ],
            [
                'label' => 'CSC Token',
                'ok' => ! empty($empresa->nfce_csc_token),
                'detail' => $empresa->nfce_csc_token ? 'Configurado' : 'Não configurado',
            ],
            [
                'label' => 'Certificado A1',
                'ok' => (bool) $certOk,
                'detail' => $certDetail,
            ],
            [
                'label' => 'CNPJ + município IBGE',
                'ok' => ! empty($empresa->cnpj) && ! empty($empresa->cod_ibge_mun),
                'detail' => trim(($empresa->cnpj ?: '—').' / '.($empresa->cod_ibge_mun ?: '—')),
            ],
            [
                'label' => 'Ambiente NFC-e',
                'ok' => in_array((int) $empresa->nfce_ambiente, [1, 2], true),
                'detail' => ((int) $empresa->nfce_ambiente === 1) ? 'Produção' : 'Homologação',
            ],
        ];
    }

    private function empresaAtiva(): Empresa
    {
        $id = Session::get('empresa_ativa');
        if (is_object($id) && isset($id->id)) {
            $id = $id->id;
        }
        $empresa = Empresa::with('certificado')->find($id);
        if (! $empresa || ! Auth::user()->empresas->contains($empresa->id)) {
            abort(403, 'Selecione uma empresa ativa.');
        }

        return $empresa;
    }
}
