<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EmpresaPerfil;
use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VinculosController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $empresasQuery = Empresa::query()
            ->with([
                'users' => fn ($rel) => $rel->orderBy('name'),
                'modulos',
            ])
            ->orderBy('razao_social');

        if ($q !== '') {
            $digits = preg_replace('/\D+/', '', $q) ?: null;
            $empresasQuery->where(function ($builder) use ($q, $digits) {
                $builder
                    ->where('razao_social', 'like', "%{$q}%")
                    ->orWhere('nome_fantasia', 'like', "%{$q}%");
                if ($digits) {
                    $builder->orWhere('cnpj', 'like', "%{$digits}%");
                }
            });
        }

        $empresas = $empresasQuery->get();

        $empresasOpcoes = Empresa::query()
            ->orderBy('razao_social')
            ->get(['id', 'razao_social', 'nome_fantasia', 'cnpj']);

        $usuarios = User::query()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_platform_admin']);

        return view('admin.vinculos', compact('empresas', 'empresasOpcoes', 'usuarios', 'q'));
    }

    public function updateModulos(Request $request, Empresa $empresa)
    {
        $request->validate([
            'modulo_erp' => ['sometimes', 'boolean'],
            'modulo_pdv' => ['sometimes', 'boolean'],
            'modulo_financeiro_gerencial' => ['sometimes', 'boolean'],
            'modulo_contabil' => ['sometimes', 'boolean'],
        ]);

        $empresa->definirModulo(EmpresaModulo::MODULO_ERP, $request->boolean('modulo_erp'));
        $empresa->definirModulo(EmpresaModulo::MODULO_PDV, $request->boolean('modulo_pdv'));
        $empresa->definirModulo(
            EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL,
            $request->boolean('modulo_financeiro_gerencial')
        );
        $empresa->definirModulo(EmpresaModulo::MODULO_CONTABIL, $request->boolean('modulo_contabil'));

        return back()->with('success', 'Módulos atualizados.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'empresa_id' => ['required', 'exists:empresas,id'],
            'perfil' => ['required', Rule::in(EmpresaPerfil::assignableValues())],
        ]);

        $user = User::query()->findOrFail($data['user_id']);
        $empresa = Empresa::query()->findOrFail($data['empresa_id']);

        if ($user->isPlatformAdmin()) {
            return back()->withErrors([
                'user_id' => 'Admin de plataforma não precisa de vínculo por empresa. Use Artisan se necessário.',
            ]);
        }

        if ($empresa->users()->where('user_id', $user->id)->exists()) {
            $atual = $empresa->users()->where('user_id', $user->id)->first()?->pivot?->perfil;
            if ($atual === EmpresaPerfil::Admin->value) {
                return back()->withErrors([
                    'user_id' => 'Este usuário já é admin imutável desta empresa.',
                ]);
            }

            $empresa->users()->updateExistingPivot($user->id, ['perfil' => $data['perfil']]);

            return back()->with('success', "Perfil de {$user->name} atualizado para {$data['perfil']}.");
        }

        $empresa->users()->attach($user->id, ['perfil' => $data['perfil']]);

        return back()->with('success', "{$user->name} vinculado como {$data['perfil']}.");
    }

    public function update(Request $request, Empresa $empresa, User $user)
    {
        $data = $request->validate([
            'perfil' => ['required', Rule::in(EmpresaPerfil::assignableValues())],
        ]);

        if ($user->isPlatformAdmin()) {
            return back()->withErrors(['erro' => 'Não é possível alterar admin de plataforma.']);
        }

        $pivot = $empresa->users()->where('user_id', $user->id)->first();
        if (! $pivot) {
            return back()->withErrors(['erro' => 'Vínculo não encontrado.']);
        }

        if ($pivot->pivot->perfil === EmpresaPerfil::Admin->value) {
            return back()->withErrors(['erro' => 'Admins de empresa são imutáveis.']);
        }

        $empresa->users()->updateExistingPivot($user->id, ['perfil' => $data['perfil']]);

        return back()->with('success', 'Perfil atualizado.');
    }

    public function destroy(Empresa $empresa, User $user)
    {
        if ($user->isPlatformAdmin()) {
            return back()->withErrors(['erro' => 'Não é possível remover admin de plataforma.']);
        }

        $pivot = $empresa->users()->where('user_id', $user->id)->first();
        if (! $pivot) {
            return back()->withErrors(['erro' => 'Vínculo não encontrado.']);
        }

        if ($pivot->pivot->perfil === EmpresaPerfil::Admin->value) {
            return back()->withErrors(['erro' => 'Admins de empresa são imutáveis e não podem ser removidos.']);
        }

        $empresa->users()->detach($user->id);

        return back()->with('success', 'Vínculo removido.');
    }
}
