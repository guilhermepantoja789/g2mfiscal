<?php

namespace App\Http\Controllers;

use App\Models\Cobranca;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CobrancaController extends Controller
{
    public function index(Request $request)
    {
        $empresaId = session('empresa_ativa');

        // 1. Query Base
        $query = Cobranca::where('empresa_id', $empresaId)
            ->with(['cliente', 'notaFiscal']); // Eager Loading

        // 2. Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('cliente', function($q) use ($search) {
                $q->where('razao_social', 'like', "%{$search}%")
                    ->orWhere('cnpj', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('vencimento_inicio')) {
            $query->whereDate('vencimento', '>=', $request->vencimento_inicio);
        }

        if ($request->filled('vencimento_fim')) {
            $query->whereDate('vencimento', '<=', $request->vencimento_fim);
        }

        // 3. Resumo Financeiro (Cards do Topo da Lista)
        // Fazemos uma query separada rápida para pegar os totais gerais, independente da paginação
        $resumo = Cobranca::where('empresa_id', $empresaId)
            ->select(
                DB::raw("SUM(CASE WHEN status = 'PENDING' AND vencimento >= CURDATE() THEN valor ELSE 0 END) as a_receber"),
                DB::raw("SUM(CASE WHEN status = 'PENDING' AND vencimento < CURDATE() THEN valor ELSE 0 END) as atrasado"),
                DB::raw("SUM(CASE WHEN status = 'RECEIVED' THEN valor ELSE 0 END) as recebido_mes")
            // Nota: recebido_mes idealmente filtraria pelo mês atual, simplifiquei aqui para o exemplo
            )
            ->first();

        // 4. Ordenação (Vencidos primeiro, depois os próximos a vencer)
        $cobrancas = $query->orderByRaw("
            CASE
                WHEN status = 'PENDING' AND vencimento < CURDATE() THEN 1
                WHEN status = 'PENDING' THEN 2
                ELSE 3
            END
        ")->orderBy('vencimento')->paginate(15)->withQueryString();

        return view('cobrancas.index', compact('cobrancas', 'resumo'));
    }

    /**
     * Simula o Webhook (Baixa Manual)
     */
    public function marcarComoPago($id)
    {
        $cobranca = Cobranca::where('empresa_id', session('empresa_ativa'))->findOrFail($id);

        $cobranca->update([
            'status' => 'RECEIVED',
            'updated_at' => now()
        ]);

        return back()->with('success', 'Pagamento registrado manualmente com sucesso!');
    }
}
