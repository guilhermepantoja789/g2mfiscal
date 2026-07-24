<?php

namespace App\Services\Acl;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class EmpresaAcl
{
    public function empresaIdAtiva(): ?int
    {
        $valor = session('empresa_ativa');

        if ($valor === null) {
            return null;
        }

        if (is_numeric($valor)) {
            return (int) $valor;
        }

        if ($valor instanceof Empresa) {
            return (int) $valor->id;
        }

        if (is_object($valor) && isset($valor->id)) {
            return (int) $valor->id;
        }

        if (is_array($valor) && isset($valor['id'])) {
            return (int) $valor['id'];
        }

        return null;
    }

    public function isPlatformAdmin(?User $user = null): bool
    {
        $user ??= Auth::user();

        return $user?->isPlatformAdmin() ?? false;
    }

    public function podeAcessarEmpresa(?User $user, int $empresaId): bool
    {
        if (! $user) {
            return false;
        }

        if ($this->isPlatformAdmin($user)) {
            return Empresa::query()->whereKey($empresaId)->exists();
        }

        return $this->pertenceAEmpresa($user, $empresaId);
    }

    /**
     * @return Collection<int, Empresa>|EloquentCollection<int, Empresa>
     */
    public function empresasVisiveis(?User $user = null): Collection
    {
        $user ??= Auth::user();
        if (! $user) {
            return collect();
        }

        if ($this->isPlatformAdmin($user)) {
            return Empresa::query()->orderBy('razao_social')->get();
        }

        return $user->empresas()->orderBy('razao_social')->get();
    }

    public function pertenceAEmpresa(?User $user, int $empresaId): bool
    {
        if (! $user) {
            return false;
        }

        return $user->empresas()->where('empresas.id', $empresaId)->exists();
    }

    public function perfilNaEmpresa(?User $user, int $empresaId): ?EmpresaPerfil
    {
        if (! $user) {
            return null;
        }

        $pivot = $user->empresas()->where('empresas.id', $empresaId)->first()?->pivot;
        if (! $pivot || ! $pivot->perfil) {
            return null;
        }

        return EmpresaPerfil::tryFrom((string) $pivot->perfil);
    }

    public function perfilAtivo(?User $user = null): ?EmpresaPerfil
    {
        $user ??= Auth::user();
        $empresaId = $this->empresaIdAtiva();
        if (! $user || ! $empresaId) {
            return null;
        }

        return $this->perfilNaEmpresa($user, $empresaId);
    }

    public function isAdmin(?User $user = null, ?int $empresaId = null): bool
    {
        return $this->perfil($user, $empresaId) === EmpresaPerfil::Admin;
    }

    public function isContador(?User $user = null, ?int $empresaId = null): bool
    {
        return $this->perfil($user, $empresaId) === EmpresaPerfil::Contador;
    }

    public function isOperador(?User $user = null, ?int $empresaId = null): bool
    {
        return $this->perfil($user, $empresaId) === EmpresaPerfil::Operador;
    }

    public function podeAdministrar(?User $user = null, ?int $empresaId = null): bool
    {
        $user ??= Auth::user();
        if ($this->isPlatformAdmin($user)) {
            return true;
        }

        return $this->isAdmin($user, $empresaId);
    }

    /**
     * Emissão, cancelamento, PDV, cadastros operacionais de escrita.
     */
    public function podeEscreverOperacional(?User $user = null, ?int $empresaId = null): bool
    {
        $user ??= Auth::user();
        if ($this->isPlatformAdmin($user)) {
            return true;
        }

        $perfil = $this->perfil($user, $empresaId);

        return in_array($perfil, [EmpresaPerfil::Admin, EmpresaPerfil::Operador], true);
    }

    /**
     * Hub Contábil: admin (supervisão) e contador.
     */
    public function podeAcessarContabil(?User $user = null, ?int $empresaId = null): bool
    {
        $user ??= Auth::user();
        if ($this->isPlatformAdmin($user)) {
            return true;
        }

        $perfil = $this->perfil($user, $empresaId);

        return in_array($perfil, [EmpresaPerfil::Admin, EmpresaPerfil::Contador], true);
    }

    public function temPerfil(EmpresaPerfil|string ...$perfis): bool
    {
        if ($this->isPlatformAdmin()) {
            foreach ($perfis as $perfil) {
                $esperado = $perfil instanceof EmpresaPerfil
                    ? $perfil
                    : EmpresaPerfil::tryFrom($perfil);
                if ($esperado === EmpresaPerfil::Admin) {
                    return true;
                }
            }
        }

        $atual = $this->perfilAtivo();
        if (! $atual) {
            return false;
        }

        foreach ($perfis as $perfil) {
            $esperado = $perfil instanceof EmpresaPerfil
                ? $perfil
                : EmpresaPerfil::tryFrom($perfil);

            if ($esperado && $atual === $esperado) {
                return true;
            }
        }

        return false;
    }

    private function perfil(?User $user, ?int $empresaId): ?EmpresaPerfil
    {
        $user ??= Auth::user();
        $empresaId ??= $this->empresaIdAtiva();
        if (! $user || ! $empresaId) {
            return null;
        }

        return $this->perfilNaEmpresa($user, $empresaId);
    }
}
