<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\FormaPagamento;
use App\Models\Produto;
use App\Services\Erp\DocumentoOrchestrator;
use App\Services\Erp\FormaPagamentoService;
use Illuminate\Http\Request;
use Throwable;

class PdvController extends Controller
{
    public function index(FormaPagamentoService $formasService)
    {
        $empresa = $this->empresaAtiva();
        $formasService->garantirDefaults($empresa);

        $formas = FormaPagamento::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get()
            ->map(fn (FormaPagamento $f) => [
                'id' => $f->id,
                'nome' => $f->nome,
                'codigo' => $f->codigo,
                'tipo_liquidacao' => $f->tipo_liquidacao,
                'dias_recebimento' => $f->dias_recebimento,
                'parcelas' => $f->parcelas,
                'juros_percentual' => (float) $f->juros_percentual,
            ]);

        return view('pdv.index', [
            'formas' => $formas,
            'buscarUrl' => route('pdv.produtos'),
            'finalizarUrl' => route('pdv.finalizar'),
            'clientesBuscarUrl' => route('clientes.buscar'),
        ]);
    }

    public function buscarProdutos(Request $request)
    {
        $empresa = $this->empresaAtiva();
        $q = trim((string) $request->get('q', ''));

        if ($q === '') {
            return response()->json([]);
        }

        $eanDigits = preg_replace('/\D/', '', $q) ?: '';

        $produtos = Produto::query()
            ->where('empresa_id', $empresa->id)
            ->where('ativo', true)
            ->where(function ($query) use ($q, $eanDigits) {
                $query->where('descricao', 'like', '%'.$q.'%')
                    ->orWhere('sku', 'like', '%'.$q.'%')
                    ->orWhere('ean', 'like', '%'.$q.'%');

                if ($eanDigits !== '' && $eanDigits !== $q) {
                    $query->orWhere('ean', 'like', '%'.$eanDigits.'%');
                }
            })
            ->orderBy('descricao')
            ->limit(15)
            ->get(['id', 'descricao', 'sku', 'ean', 'preco_venda', 'unidade', 'ncm', 'cfop', 'csosn', 'estoque_atual']);

        return response()->json($produtos);
    }

    public function statusVenda(int $documento)
    {
        $empresa = $this->empresaAtiva();

        $doc = DocumentoComercial::query()
            ->where('empresa_id', $empresa->id)
            ->whereKey($documento)
            ->with('nfce')
            ->firstOrFail();

        $nfce = $doc->nfce;
        $imprimirUrl = null;
        if ($nfce && $nfce->podeImprimirDanfe()) {
            $imprimirUrl = route('nfces.imprimir', $nfce->id);
        }

        return response()->json([
            'documento_id' => $doc->id,
            'documento_status' => $doc->status,
            'documento_status_label' => $doc->status_label,
            'mensagem_erro' => $doc->mensagem_erro,
            'nfce_id' => $nfce?->id,
            'nfce_status' => $nfce?->status,
            'nfce_numero' => $nfce?->numero,
            'pode_imprimir' => $imprimirUrl !== null,
            'imprimir_url' => $imprimirUrl,
            'documento_url' => route('documentos.show', $doc->id),
            'nfce_url' => $nfce ? route('nfces.show', $nfce->id) : null,
            'terminal' => in_array($doc->status, [
                DocumentoComercial::STATUS_AUTORIZADO,
                DocumentoComercial::STATUS_ERRO,
                DocumentoComercial::STATUS_CANCELADO,
            ], true)
                || ($nfce && in_array($nfce->status, ['autorizada', 'rejeitada', 'cancelada', 'pendente_transmissao'], true)),
        ]);
    }

    public function finalizar(Request $request, DocumentoOrchestrator $orchestrator)
    {
        $empresa = $this->empresaAtiva();

        $validated = $request->validate([
            'pagamentos' => 'required|array|min:1',
            'pagamentos.*.forma_pagamento_id' => 'required|integer',
            'pagamentos.*.valor' => 'required|numeric|min:0.01',
            'pagamentos.*.v_troco' => 'nullable|numeric|min:0',
            'forma_pagamento_id' => 'nullable|integer',
            'itens' => 'required|array|min:1',
            'itens.*.produto_id' => 'required|integer',
            'itens.*.quantidade' => 'required|numeric|min:0.001',
            'itens.*.valor_unitario' => 'required|numeric|min:0',
            'dest_doc' => 'nullable|string|max:18',
            'dest_nome' => 'nullable|string|max:120',
            'cliente_id' => 'nullable|integer',
        ]);

        $destDoc = isset($validated['dest_doc'])
            ? (preg_replace('/\D/', '', (string) $validated['dest_doc']) ?: null)
            : null;
        $destNome = isset($validated['dest_nome'])
            ? (trim((string) $validated['dest_nome']) ?: null)
            : null;

        $clienteId = null;
        if (! empty($validated['cliente_id'])) {
            $cliente = Cliente::query()
                ->where('empresa_id', $empresa->id)
                ->whereKey($validated['cliente_id'])
                ->firstOrFail();
            $clienteId = $cliente->id;
        }

        $pagamentos = [];
        foreach ($validated['pagamentos'] as $linha) {
            FormaPagamento::query()
                ->where('empresa_id', $empresa->id)
                ->whereKey($linha['forma_pagamento_id'])
                ->where('ativo', true)
                ->firstOrFail();

            $pagamentos[] = [
                'forma_pagamento_id' => (int) $linha['forma_pagamento_id'],
                'valor' => round((float) $linha['valor'], 2),
                'v_troco' => isset($linha['v_troco']) ? round((float) $linha['v_troco'], 2) : null,
            ];
        }

        $itens = [];
        foreach ($validated['itens'] as $linha) {
            $produto = Produto::query()
                ->where('empresa_id', $empresa->id)
                ->whereKey($linha['produto_id'])
                ->firstOrFail();

            $itens[] = [
                'produto_id' => $produto->id,
                'descricao' => $produto->descricao,
                'ncm' => $produto->ncm,
                'cfop' => $produto->cfop,
                'csosn' => $produto->csosn,
                'unidade' => $produto->unidade,
                'ean' => $produto->ean,
                'quantidade' => $linha['quantidade'],
                'valor_unitario' => $linha['valor_unitario'],
            ];
        }

        try {
            $doc = $orchestrator->criarRascunho($empresa, [
                'tipo' => DocumentoComercial::TIPO_VENDA,
                'canal_fiscal' => DocumentoComercial::CANAL_NFCE,
                'pagamentos' => $pagamentos,
                'forma_pagamento_id' => $pagamentos[0]['forma_pagamento_id'],
                'cliente_id' => $clienteId,
                'dest_doc' => $destDoc,
                'dest_nome' => $destNome,
                'itens' => $itens,
            ]);
            $doc = $orchestrator->confirmar($doc);
        } catch (Throwable $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()->withErrors(['erro' => $e->getMessage()]);
        }

        $doc->loadMissing('nfce');
        $nfce = $doc->nfce;

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'documento_id' => $doc->id,
                'nfce_id' => $nfce?->id,
                'status' => $doc->status,
                'status_url' => route('pdv.vendas.status', $doc->id),
                'documento_url' => route('documentos.show', $doc->id),
                'nfce_url' => $nfce ? route('nfces.show', $nfce->id) : null,
            ]);
        }

        return redirect()->route('documentos.show', $doc->id)
            ->with('success', 'Venda enviada para emissão NFC-e.');
    }

    private function empresaAtiva(): Empresa
    {
        $empresa = Empresa::find(session('empresa_ativa'));
        if (! $empresa) {
            abort(403, 'Selecione uma empresa.');
        }

        return $empresa;
    }
}
