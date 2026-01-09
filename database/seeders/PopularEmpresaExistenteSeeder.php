<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Servico;

class PopularEmpresaExistenteSeeder extends Seeder
{
    public function run()
    {
        $empresa = Empresa::first();
        if (!$empresa) return;

        // Cliente
        Cliente::firstOrCreate(
            ['empresa_id' => $empresa->id, 'cnpj' => '13341900107653'],
            ['razao_social' => 'Bezerra e Rico S.A.', 'uf' => 'AM']
        );

        // Serviço formatado corretamente
        Servico::updateOrCreate(
            ['empresa_id' => $empresa->id, 'codigo_interno' => 'DEV-001'],
            [
                'nome' => 'Desenvolvimento de Software',
                'codigo_tributacao_nacional' => '010501', // 6 dígitos numéricos
                'codigo_tributacao_municipal' => '100',  // 3 dígitos numéricos
                'descricao' => 'Desenvolvimento e licenciamento de programas de computador customizáveis.',
                'valor_unitario' => 2500.00,
                'aliquota_iss' => 2.00,
            ]
        );
    }
}
