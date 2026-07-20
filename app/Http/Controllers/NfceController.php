<?php

namespace App\Http\Controllers;

use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use App\Core\FiscalEngine\Exceptions\SefazRejectionException;
use App\Core\FiscalEngine\Exceptions\SefazTransportException;
use App\Jobs\EmitirNfceJob;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Services\Fiscal\NfceDanfeService;
use App\Services\Fiscal\NfceEmitRequest;
use App\Services\Fiscal\RawNativeNfceIssuer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class NfceController extends Controller
{
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
            'descricao' => 'required|string|max:120',
            'ncm' => 'required|string|max:8',
            'cfop' => 'required|in:5102,5405',
            'csosn' => 'required|in:102,500',
            'unidade' => 'required|string|max:6',
            'quantidade' => 'required|numeric|min:0.001',
            'valor_unitario' => 'required|numeric|min:0.01',
            't_pag' => 'required|in:01,03,04,17',
            'v_troco' => 'nullable|numeric|min:0',
            'dest_doc' => 'nullable|string|max:18',
            'dest_nome' => 'nullable|string|max:120',
        ]);

        $qtd = (float) $validated['quantidade'];
        $vu = (float) $validated['valor_unitario'];
        $total = round($qtd * $vu, 2);
        $vTroco = isset($validated['v_troco']) ? (float) $validated['v_troco'] : null;
        $vPag = $validated['t_pag'] === '01' && $vTroco !== null && $vTroco > 0
            ? round($total + $vTroco, 2)
            : $total;

        [$numero, $serie, $ambiente] = $issuer->reservarNumero($empresa);

        $payload = [
            'itens' => [[
                'descricao' => $validated['descricao'],
                'ncm' => preg_replace('/\D/', '', $validated['ncm']),
                'cfop' => $validated['cfop'],
                'csosn' => $validated['csosn'],
                'unidade' => $validated['unidade'],
                'quantidade' => $qtd,
                'valor_unitario' => $vu,
                'pis_cst' => '49',
                'cofins_cst' => '49',
            ]],
            'pagamentos' => [[
                't_pag' => $validated['t_pag'],
                'v_pag' => $vPag,
                'v_troco' => $validated['t_pag'] === '01' ? $vTroco : null,
            ]],
            'dest_doc' => $validated['dest_doc'] ? preg_replace('/\D/', '', $validated['dest_doc']) : null,
            'dest_nome' => $validated['dest_nome'] ?? null,
            'natureza' => 'VENDA',
        ];

        $nfce = Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => $numero,
            'serie' => $serie,
            'ambiente' => $ambiente,
            'tp_emis' => 1,
            'status' => 'processando',
            'payload' => $payload,
            'valor_total' => $total,
            'destinatario_doc' => $payload['dest_doc'],
            'destinatario_nome' => $payload['dest_nome'],
        ]);

        EmitirNfceJob::dispatch($nfce);

        return redirect()->route('nfces.show', $nfce->id)
            ->with('success', 'NFC-e enviada para processamento assíncrono.');
    }

    public function show(int $id)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        return view('nfces.show', compact('empresa', 'nfce'));
    }

    public function imprimir(int $id, NfceDanfeService $danfe)
    {
        $empresa = $this->empresaAtiva();
        $nfce = Nfce::query()->where('empresa_id', $empresa->id)->findOrFail($id);

        return $danfe->download($nfce);
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
