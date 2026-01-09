<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SelecaoEmpresaController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Carrega as empresas do usuário logado
        $empresas = $user->empresas;

        return view('empresas.selecao', compact('empresas', 'user'));
    }

    public function selecionar($id)
    {
        $user = Auth::user();

        // 1. SEGURANÇA: Verifica se o usuário realmente é dono dessa empresa
        // Se ele tentar trocar o ID na URL para acessar dados de outro, vai dar erro 403.
        if (!$user->empresas()->where('empresa_id', $id)->exists()) {
            abort(403, 'Acesso não autorizado a esta empresa.');
        }

        // 2. Salva na memória (Sessão) qual empresa estamos mexendo agora
        session(['empresa_ativa' => $id]);

        // 3. O PULO DO GATO: Redireciona para o Dashboard (onde está o layout novo)
        return redirect()->route('dashboard');
    }
}
