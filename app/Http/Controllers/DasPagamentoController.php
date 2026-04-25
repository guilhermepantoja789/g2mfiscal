<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DasPagamento;
use App\Models\Empresa;

class DasPagamentoController extends Controller
{
    public function registrarPagamento(Request $request)
    {
        $request->validate([
            'competencia' => 'required|string',
            'valor_pago' => 'required|numeric|min:0',
            'data_pagamento' => 'required|date',
        ]);

        $sessao = session('empresa_ativa');
        $empresaId = is_numeric($sessao) ? $sessao : data_get($sessao, 'id');

        $das = DasPagamento::where('empresa_id', $empresaId)
            ->where('competencia', $request->competencia)
            ->first();

        if (!$das) {
            return back()->with('error', 'Registro de DAS não encontrado para esta competência.');
        }

        $das->update([
            'valor_pago' => $request->valor_pago,
            'data_pagamento' => $request->data_pagamento,
            'status' => 'pago',
        ]);

        return back()->with('success', 'Pagamento do DAS registrado com sucesso!');
    }
}
