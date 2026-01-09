<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateSaasUser extends Command
{
    /**
     * O nome que você usará no terminal.
     */
    protected $signature = 'create:user';

    /**
     * Descrição.
     */
    protected $description = 'Cria um novo cliente (usuário SaaS) com acesso ao painel';

    public function handle()
    {
        $this->info('--- NOVO CLIENTE DO SISTEMA ---');

        // 1. Coleta os dados básicos
        $name = $this->ask('Nome do Cliente');
        $email = $this->ask('E-mail de Login');

        // Verifica se já existe para evitar erro de SQL
        if (User::where('email', $email)->exists()) {
            $this->error('Erro: Este e-mail já está cadastrado no sistema!');
            return;
        }

        // Senha ou Gerar Automática
        $password = $this->secret('Senha (deixe vazio para gerar aleatória)');
        if (empty($password)) {
            $password = Str::random(10);
            $this->comment("Senha gerada automaticamente: {$password}");
        }

        // Confirmação visual
        if (!$this->confirm("Deseja criar o usuário '{$name}' ({$email})?")) {
            $this->info('Operação cancelada.');
            return;
        }

        try {
            // 2. Cria o Usuário
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(), // Já marca como verificado para ele logar direto
                // Se você tiver uma coluna 'role' ou 'is_admin' na tabela users,
                // aqui você definiria como 'cliente' ou false.
                // Exemplo: 'role' => 'client',
            ]);

            $this->newLine();
            $this->info('✅ Cliente criado com sucesso!');
            $this->info('O usuário agora pode fazer login e cadastrar suas próprias empresas.');

            $this->table(
                ['ID', 'Nome', 'E-mail'],
                [[$user->id, $user->name, $user->email]]
            );

        } catch (\Exception $e) {
            $this->error('Erro ao criar registro: ' . $e->getMessage());
        }
    }
}
