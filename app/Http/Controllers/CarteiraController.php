<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Empresa;
use App\Models\Cobranca;
use App\Models\Transferencia;
use Illuminate\Support\Facades\DB;

class CarteiraController extends Controller
{
    public function index()
    {
        $empresaId = session('empresa_ativa');
        $empresa = Empresa::find($empresaId);

        // TRAVA DE SEGURANÇA 1: Se não tem Wallet ID (não criou conta no Asaas), bloqueia
        if (empty($empresa->asaas_wallet_id)) {
            return view('financeiro.onboarding', compact('empresa'));
        }

        // TRAVA DE SEGURANÇA 2: Se não cadastrou banco de destino, força cadastro
        if (empty($empresa->conta) && empty($empresa->chave_pix)) {
            return view('financeiro.configurar_banco', compact('empresa'));
        }

        // 1. Calcular Saldo Virtual
        // Entradas: Cobranças com status RECEIVED
        $entradas = Cobranca::where('empresa_id', $empresaId)
            ->where('status', 'RECEIVED')
            ->sum('valor'); // Se tiver taxa de boleto, use 'valor_liquido' aqui

        // Saídas: Transferências (Status PROCESSING ou DONE)
        $saidas = Transferencia::where('empresa_id', $empresaId)
            ->whereIn('status', ['PROCESSING', 'DONE'])
            ->sum(DB::raw('valor + taxa'));

        $saldoDisponivel = $entradas - $saidas;

        // 2. Histórico de Saques
        $historicoSaques = Transferencia::where('empresa_id', $empresaId)
            ->latest()
            ->paginate(10);

        return view('financeiro.carteira', compact('empresa', 'saldoDisponivel', 'historicoSaques'));
    }

    /**
     * Salva os dados bancários do cliente
     */
    public function salvarDadosBancarios(Request $request)
    {
        $request->validate([
            'chave_pix' => 'nullable|string',
            'banco_nome' => 'required_without:chave_pix',
            'agencia' => 'required_without:chave_pix',
            'conta' => 'required_without:chave_pix',
        ]);

        $empresa = Empresa::find(session('empresa_ativa'));

        $empresa->update([
            'chave_pix' => $request->chave_pix,
            'banco_nome' => $request->banco_nome,
            'agencia' => $request->agencia,
            'conta' => $request->conta,
            'conta_tipo' => $request->conta_tipo ?? 'CC',
            'saque_automatico' => $request->has('saque_automatico')
        ]);

        return back()->with('success', 'Dados bancários atualizados com sucesso!');
    }

    /**
     * Processa o pedido de saque manual
     */
    public function solicitarSaque(Request $request)
    {
        $empresaId = session('empresa_ativa');
        $empresa = Empresa::find($empresaId);

        $valorSaque = str_replace(',', '.', str_replace('.', '', $request->valor));

        // Regras de Negócio (Validação)
        if (!$empresa->chave_pix && !$empresa->conta) {
            return back()->withErrors(['erro' => 'Cadastre uma conta bancária antes de sacar.']);
        }

        // Recalcula saldo para garantir (segurança)
        $entradas = Cobranca::where('empresa_id', $empresaId)->where('status', 'RECEIVED')->sum('valor');
        $saidas = Transferencia::where('empresa_id', $empresaId)->whereIn('status', ['PROCESSING', 'DONE'])->sum(DB::raw('valor + taxa'));
        $saldoAtual = $entradas - $saidas;

        if ($valorSaque > $saldoAtual) {
            return back()->withErrors(['erro' => 'Saldo insuficiente para este saque.']);
        }

        // Cria o registro de Saque
        Transferencia::create([
            'empresa_id' => $empresaId,
            'valor' => $valorSaque,
            'taxa' => 0.00, // Aqui você colocaria a lógica: É o 1º saque do dia? R$ 0. Se não, R$ 2.00
            'status' => 'PROCESSING', // Fica processando até o Asaas confirmar (webhook)
            'data_solicitacao' => now(),

            // Snapshot dos dados para onde foi o dinheiro
            'chave_pix_destino' => $empresa->chave_pix,
            'banco_destino' => $empresa->banco_nome,
            'agencia_destino' => $empresa->agencia,
            'conta_destino' => $empresa->conta,
        ]);

        // Aqui entraria a chamada para $asaasService->transferir(...)

        return back()->with('success', 'Solicitação de saque realizada! O dinheiro cairá na sua conta em breve.');
    }

    /**
     * ATIVAÇÃO DO MÓDULO (Criação da Subconta Asaas)
     */
    public function ativarConta(Request $request)
    {
        $empresa = \App\Models\Empresa::find(session('empresa_ativa'));

        // Simulação: Aqui você chamaria a API do Asaas (POST /accounts)
        // Como estamos em dev, vamos gerar um ID falso para liberar o acesso

        $fakeWalletId = 'wallet_' . uniqid() . '_test';
        $fakeApiKey = 'token_simulado_' . \Illuminate\Support\Str::random(30);

        $empresa->update([
            'asaas_wallet_id' => $fakeWalletId,
            'asaas_token' => $fakeApiKey
        ]);

        return redirect()->route('carteira.index')
            ->with('success', 'Conta Digital ativada com sucesso! Agora configure seu saque.');
    }
}
