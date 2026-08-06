<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Cliente;
use App\Models\Servico;
use App\Models\Certificado;

class DevDemoSeeder extends Seeder
{
    public function run()
    {
        // 1. Criar Usuário Admin
        $user = User::firstOrCreate(
            ['email' => 'admin@teste.com'],
            [
                'name' => 'Admin G2M',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Criar Empresa Principal
        $empresa = Empresa::firstOrCreate(
            ['cnpj' => '12345678000199'], // CNPJ Fictício
            [
                'user_id' => $user->id,
                'razao_social' => 'Minha Empresa de Tecnologia Ltda',
                'nome_fantasia' => 'Tech Solutions',
                'inscricao_municipal' => '123456',
                'regime_tributario' => 3, // 3 = Simples Nacional (no seu sistema)
                'regime_apuracao_sn' => 1,
                'email' => 'contato@techsolutions.com.br',
                'cep' => '69000000',
                'logradouro' => 'Av. Torquato Tapajós',
                'numero' => '100',
                'bairro' => 'Flores',
                'uf' => 'AM',
                'cod_ibge_mun' => '1302603', // Manaus
            ]
        );

        // Vincula usuário à empresa (Pivot table)
        if (!$empresa->users()->where('user_id', $user->id)->exists()) {
            $empresa->users()->attach($user->id, ['perfil' => 'dono']);
        }

        // 3. Criar Certificado (Fake para evitar erro de "Arquivo não existe")
        // Cria um arquivo vazio só para o Storage::exists passar
        $nomeArquivo = 'certificados/dummy_cert.pfx';
        Storage::put($nomeArquivo, 'CONTEUDO_FAKE_APENAS_PARA_TESTE_DE_INTERFACE');

        Certificado::create([
            'empresa_id' => $empresa->id,
            'nome_arquivo' => $nomeArquivo,
            'senha' => '1234',
            'ativo' => true,
            'valido_ate' => now()->addYear(),
        ]);

        // 4. Criar Clientes (Tomadores)
        Cliente::create([
            'empresa_id' => $empresa->id,
            'razao_social' => 'Mercado do João Ltda',
            'cnpj' => '98765432000100',
            'email' => 'joao@mercado.com',
            'cep' => '01001000',
            'logradouro' => 'Rua das Flores',
            'numero' => '50',
            'bairro' => 'Centro',
            'uf' => 'SP',
            'cidade_codigo' => '3550308', // SP
        ]);

        // 5. Criar Serviços (O mais importante para seu teste agora)

        // Serviço 1: Desenvolvimento (LC 01.01 → NBS 115021000)
        Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Desenvolvimento de Software',
            'codigo_interno' => 'DEV-001',
            'codigo_tributacao_nacional' => '010101',
            'codigo_tributacao_municipal' => '100',
            'codigo_nbs' => '115021000',
            'fin_nfse' => '0',
            'c_ind_op' => '100301',
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
            'descricao' => 'Desenvolvimento e instalação de aplicativos e programas de computador.',
            'valor_unitario' => 2500.00,
            'iss_retido' => false,
            'aliquota_iss' => 2.00,
        ]);

        // Serviço 2: Manutenção / suporte (LC 01.07 → NBS 115013000)
        Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Manutenção Mensal',
            'codigo_interno' => 'SUP-002',
            'codigo_tributacao_nacional' => '010701',
            'codigo_tributacao_municipal' => '100',
            'codigo_nbs' => '115013000',
            'fin_nfse' => '0',
            'c_ind_op' => '100301',
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
            'descricao' => 'Suporte técnico e manutenção de computadores referente ao mês vigente.',
            'valor_unitario' => 350.00,
            'iss_retido' => false,
            'aliquota_iss' => 2.00,
        ]);

        // Serviço 3: Consultoria TI (LC 01.06 → NBS 115011000)
        Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Consultoria Técnica',
            'codigo_interno' => 'CONS-003',
            'codigo_tributacao_nacional' => '010601',
            'codigo_tributacao_municipal' => '100',
            'codigo_nbs' => '115011000',
            'fin_nfse' => '0',
            'c_ind_op' => '100301',
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
            'descricao' => 'Consultoria em tecnologia da informação e infraestrutura.',
            'valor_unitario' => 5000.00,
            'iss_retido' => true,
            'aliquota_iss' => 5.00,
        ]);

        $this->command->info('Dados de teste criados com sucesso!');
        $this->command->info('Login: admin@teste.com');
        $this->command->info('Senha: password');
    }
}
