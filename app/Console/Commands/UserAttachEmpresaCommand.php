<?php

namespace App\Console\Commands;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Console\Command;

class UserAttachEmpresaCommand extends Command
{
    protected $signature = 'user:attach-empresa
                            {email : E-mail do usuário}
                            {empresa_id : ID da empresa}
                            {--perfil=operador : admin|operador|contador}
                            {--detach : Remove o vínculo em vez de criar/atualizar}';

    protected $description = 'Vincula (ou remove) usuário a empresa com perfil; único caminho ops para promover admin de empresa';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $empresaId = (int) $this->argument('empresa_id');
        $detach = (bool) $this->option('detach');

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error("Usuário não encontrado: {$email}");

            return self::FAILURE;
        }

        $empresa = Empresa::query()->find($empresaId);
        if (! $empresa) {
            $this->error("Empresa não encontrada: {$empresaId}");

            return self::FAILURE;
        }

        if ($detach) {
            if (! $user->empresas()->where('empresas.id', $empresaId)->exists()) {
                $this->warn('Vínculo inexistente — nada a remover.');

                return self::SUCCESS;
            }

            $user->empresas()->detach($empresaId);
            $this->info("✅ Vínculo removido: {$user->email} ↔ empresa #{$empresaId}.");

            return self::SUCCESS;
        }

        $perfilRaw = (string) $this->option('perfil');
        $perfil = EmpresaPerfil::tryFrom($perfilRaw);
        if (! $perfil) {
            $this->error('Perfil inválido. Use: '.implode(', ', EmpresaPerfil::values()));

            return self::FAILURE;
        }

        if ($user->empresas()->where('empresas.id', $empresaId)->exists()) {
            $user->empresas()->updateExistingPivot($empresaId, ['perfil' => $perfil->value]);
            $this->info("✅ Perfil atualizado: {$user->email} → {$perfil->label()} na empresa #{$empresaId}.");
        } else {
            $user->empresas()->attach($empresaId, ['perfil' => $perfil->value]);
            $this->info("✅ Vínculo criado: {$user->email} → {$perfil->label()} na empresa #{$empresaId}.");
        }

        return self::SUCCESS;
    }
}
