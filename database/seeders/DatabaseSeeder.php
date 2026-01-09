<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Usuário Admin
        $user = \App\Models\User::factory()->create([
            'name' => 'Admin G2m',
            'email' => 'admin@g2m.com.br',
            'password' => bcrypt('12345678'),
        ]);

        // 2. Empresa Principal
        $empresa = \App\Models\Empresa::create([
            'user_id' => $user->id,
            'cnpj' => '12345678000199',
            'razao_social' => 'G2m Consultoria de TI Ltda',
            'nome_fantasia' => 'G2m Tech',
            'cep' => '69000000',
            'logradouro' => 'Av. Djalma Batista',
            'numero' => '1000',
            'bairro' => 'Chapada',
            'uf' => 'AM',
            'regime_tributario' => 1,
            'cod_ibge_mun' => '1302603', // <--- LINHA ADICIONADA (Manaus)
        ]);

        // Vincula
        $user->empresas()->attach([$empresa->id]);

        // 3. Criar 15 Clientes para esta empresa
        $clientes = \App\Models\Cliente::factory(15)->create([
            'empresa_id' => $empresa->id
        ]);

        // 4. Para cada cliente, criar notas fiscais
        foreach ($clientes as $cliente) {
            // Cada cliente terá entre 2 e 10 notas
            \App\Models\NotaFiscal::factory(rand(2, 10))->create([
                'empresa_id' => $empresa->id,
                'cliente_id' => $cliente->id,
                // Copia os dados do cliente para a nota (snapshot)
                'tomador_nome' => $cliente->razao_social,
                'tomador_cnpj' => $cliente->cnpj,
            ]);
        }
    }
}
