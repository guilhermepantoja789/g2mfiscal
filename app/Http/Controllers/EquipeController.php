<?php

namespace App\Http\Controllers;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use App\Services\Acl\EmpresaAcl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EquipeController extends Controller
{
    public function __construct(
        private EmpresaAcl $acl,
    ) {}

    private function getEmpresaIdFromSession(): ?int
    {
        return $this->acl->empresaIdAtiva();
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'perfil' => ['nullable', Rule::in(EmpresaPerfil::assignableValues())],
        ], [
            'email.exists' => 'Usuário não encontrado. Peça para ele se cadastrar no sistema.',
        ]);

        $empresaId = $this->getEmpresaIdFromSession();

        if (! $empresaId) {
            return back()->withErrors(['erro' => 'Nenhuma empresa selecionada.']);
        }

        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            return back()->withErrors(['erro' => 'Empresa inválida.']);
        }

        if (! $this->acl->podeAdministrar(Auth::user(), $empresaId)) {
            abort(403, 'Apenas administradores podem gerenciar a equipe.');
        }

        $novoUsuario = User::where('email', $request->email)->first();

        if ($novoUsuario->isPlatformAdmin()) {
            return back()->withErrors(['email' => 'Não é possível vincular admin de plataforma pela equipe.']);
        }

        if ($empresa->users()->where('user_id', $novoUsuario->id)->exists()) {
            return back()->withErrors(['email' => 'Usuário já faz parte da equipe.']);
        }

        $perfil = EmpresaPerfil::tryFrom((string) $request->input('perfil', EmpresaPerfil::Operador->value))
            ?? EmpresaPerfil::Operador;

        if ($perfil === EmpresaPerfil::Admin) {
            return back()->withErrors(['perfil' => 'Admins de empresa são imutáveis. Use apenas operador ou contador.']);
        }

        $empresa->users()->attach($novoUsuario->id, ['perfil' => $perfil->value]);

        return back()->with('success', "{$novoUsuario->name} adicionado como {$perfil->label()}!");
    }

    public function destroy($userId)
    {
        $empresaId = $this->getEmpresaIdFromSession();
        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            return back()->withErrors(['erro' => 'Erro ao identificar empresa.']);
        }

        if (! $this->acl->podeAdministrar(Auth::user(), (int) $empresaId)) {
            abort(403, 'Apenas administradores podem remover membros.');
        }

        if ((int) $userId === (int) Auth::id()) {
            return back()->withErrors(['erro' => 'Você não pode se remover.']);
        }

        $alvo = User::find($userId);
        if (! $alvo) {
            return back()->withErrors(['erro' => 'Usuário não encontrado.']);
        }

        if ($alvo->isPlatformAdmin()) {
            return back()->withErrors(['erro' => 'Não é possível remover admin de plataforma.']);
        }

        $perfilAlvo = $this->acl->perfilNaEmpresa($alvo, (int) $empresaId);
        if ($perfilAlvo === EmpresaPerfil::Admin) {
            return back()->withErrors(['erro' => 'Admins de empresa são imutáveis e não podem ser removidos.']);
        }

        $empresa->users()->detach($userId);

        return back()->with('success', 'Membro removido.');
    }

    public function updateRole(Request $request, $userId)
    {
        $empresaId = $this->getEmpresaIdFromSession();
        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            return back()->withErrors(['erro' => 'Empresa inválida.']);
        }

        if (! $this->acl->podeAdministrar(Auth::user(), (int) $empresaId)) {
            abort(403, 'Apenas administradores podem alterar perfis.');
        }

        $request->validate([
            'perfil' => ['required', Rule::in(EmpresaPerfil::assignableValues())],
        ]);

        if ((int) $userId === (int) Auth::id()) {
            return back()->withErrors(['erro' => 'Você não pode alterar o próprio perfil.']);
        }

        $alvo = User::find($userId);
        if (! $alvo) {
            return back()->withErrors(['erro' => 'Usuário não encontrado.']);
        }

        if ($alvo->isPlatformAdmin()) {
            return back()->withErrors(['erro' => 'Não é possível alterar perfil de admin de plataforma.']);
        }

        $perfilAtual = $this->acl->perfilNaEmpresa($alvo, (int) $empresaId);
        if ($perfilAtual === EmpresaPerfil::Admin) {
            return back()->withErrors(['erro' => 'Admins de empresa são imutáveis.']);
        }

        $empresa->users()->updateExistingPivot($userId, ['perfil' => $request->perfil]);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }
}
