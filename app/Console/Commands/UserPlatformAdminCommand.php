<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class UserPlatformAdminCommand extends Command
{
    protected $signature = 'user:platform-admin
                            {email : E-mail do usuário}
                            {--revoke : Remove a flag de admin de plataforma}';

    protected $description = 'Liga ou remove is_platform_admin (somente via Artisan; sem UI/seeder)';

    public function handle(): int
    {
        $email = (string) $this->argument('email');
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            $this->error("Usuário não encontrado: {$email}");

            return self::FAILURE;
        }

        $grant = ! $this->option('revoke');
        $user->forceFill(['is_platform_admin' => $grant])->save();

        $this->info($grant
            ? "✅ {$user->email} agora é admin de plataforma."
            : "✅ Flag de plataforma removida de {$user->email}.");

        return self::SUCCESS;
    }
}
