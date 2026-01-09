<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session; // Facade

class EquipeController extends Controller
{
    // Helper BLINDADO para pegar ID
    private function getEmpresaIdFromSession()
    {
        // 1. Pega o valor cru da sessão
        $valor = Session::get('empresa_ativa');

        if (!$valor) return null;

        // 2. Se for numérico, é o ID (cenário ideal)
        if (is_numeric($valor)) {
            return $valor;
        }

        // 3. Se for objeto (Empresa Model), pega o ID com segurança
        if ($valor instanceof Empresa) {
            return $valor->id;
        }

        // 4. Se for objeto genérico ou array
        if (is_object($valor) && isset($valor->id)) {
            return $valor->id;
        }

        if (is_array($valor) && isset($valor['id'])) {
            return $valor['id'];
        }

        return null;
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ], [
            'email.exists' => 'Usuário não encontrado. Peça para ele se cadastrar no sistema.'
        ]);

        $empresaId = $this->getEmpresaIdFromSession();

        if (!$empresaId) {
            return back()->withErrors(['erro' => 'Nenhuma empresa selecionada.']);
        }

        $empresa = Empresa::find($empresaId);

        if (!$empresa) {
            return back()->withErrors(['erro' => 'Empresa inválida.']);
        }

        // Segurança
        if (!$empresa->users()->where('user_id', Auth::id())->exists()) {
            abort(403, 'Você não tem permissão para gerenciar a equipe.');
        }

        $novoUsuario = User::where('email', $request->email)->first();

        // Evita duplicidade
        if ($empresa->users()->where('user_id', $novoUsuario->id)->exists()) {
            return back()->withErrors(['email' => 'Usuário já faz parte da equipe.']);
        }

        // Adiciona com perfil padrão
        $empresa->users()->attach($novoUsuario->id, ['perfil' => 'operador']);

        return back()->with('success', "{$novoUsuario->name} adicionado com sucesso!");
    }

    public function destroy($userId)
    {
        $empresaId = $this->getEmpresaIdFromSession();
        $empresa = Empresa::find($empresaId);

        if (!$empresa) return back()->withErrors(['erro' => 'Erro ao identificar empresa.']);

        // Segurança
        if (!$empresa->users()->where('user_id', Auth::id())->exists()) {
            abort(403);
        }

        // Não remover a si mesmo
        if ($userId == Auth::id()) {
            return back()->withErrors(['erro' => 'Você não pode se remover.']);
        }

        $empresa->users()->detach($userId);

        return back()->with('success', 'Membro removido.');
    }

    // Adicione logo após o método store
    public function updateRole(Request $request, $userId)
    {
        $empresaId = $this->getEmpresaIdFromSession();
        $empresa = Empresa::find($empresaId);

        if (!$empresa) return back()->withErrors(['erro' => 'Empresa inválida.']);

        // Segurança: Só admin pode promover outros
        // Verifica se quem está logado é admin NESTA empresa
        $adminPivot = $empresa->users()->where('user_id', Auth::id())->first()->pivot;
        if ($adminPivot->perfil !== 'admin') {
            abort(403, 'Apenas administradores podem alterar perfis.');
        }

        // Validação básica
        if (!in_array($request->perfil, ['admin', 'operador'])) {
            return back()->withErrors(['erro' => 'Perfil inválido.']);
        }

        // Atualiza
        $empresa->users()->updateExistingPivot($userId, ['perfil' => $request->perfil]);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }
}
