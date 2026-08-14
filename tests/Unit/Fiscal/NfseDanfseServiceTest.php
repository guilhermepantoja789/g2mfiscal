<?php

namespace Tests\Unit\Fiscal;

use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\NotaFiscal;
use App\Models\Servico;
use App\Models\User;
use App\Services\Fiscal\NfseDanfseService;
use Tests\TestCase;

class NfseDanfseServiceTest extends TestCase
{
    public function test_autorizada_producao_nao_e_espelho(): void
    {
        $nota = $this->makeNota(status: 'autorizada', ambiente: 'producao');
        $html = (new NfseDanfseService)->renderHtml($nota);

        $this->assertStringNotContainsString('ESPELHO', $html);
        $this->assertStringNotContainsString('SEM VALOR FISCAL', $html);
        $this->assertStringNotContainsString('SEM VALIDADE JURÍDICA', $html);
        $this->assertStringContainsString('Documento Auxiliar da NFS-e', $html);
        $this->assertStringContainsString('XML autorizado', $html);
        $this->assertStringContainsString('cClassTrib', $html);
        $this->assertStringContainsString('data:image/png;base64', $html);
    }

    public function test_autorizada_homolog_tem_aviso_sem_validade(): void
    {
        $nota = $this->makeNota(status: 'autorizada', ambiente: 'homologacao');
        $html = (new NfseDanfseService)->renderHtml($nota);

        $this->assertStringContainsString('NFS-e SEM VALIDADE JURÍDICA', $html);
        $this->assertStringNotContainsString('ESPELHO — DOCUMENTO SEM VALOR FISCAL', $html);
    }

    public function test_rascunho_e_espelho_sem_valor_fiscal(): void
    {
        $nota = $this->makeNota(status: 'criada', ambiente: 'producao', comXml: false);
        $html = (new NfseDanfseService)->renderHtml($nota);

        $this->assertStringContainsString('ESPELHO — DOCUMENTO SEM VALOR FISCAL', $html);
        $this->assertStringContainsString('SEM VALOR FISCAL', $html);
    }

    public function test_render_pdf_comeca_com_percent_pdf(): void
    {
        $nota = $this->makeNota(status: 'autorizada', ambiente: 'producao');
        $pdf = (new NfseDanfseService)->renderPdf($nota);

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_aviso_tipo_por_status_e_ambiente(): void
    {
        $service = new NfseDanfseService;

        $this->assertNull($service->avisoTipo($this->makeNota('autorizada', 'producao')));
        $this->assertSame('homolog', $service->avisoTipo($this->makeNota('autorizada', 'homologacao')));
        $this->assertSame('espelho', $service->avisoTipo($this->makeNota('criada', 'producao', comXml: false)));
    }

    public function test_formata_chave_em_grupos_de_quatro(): void
    {
        $chave = '13026032209279540000120000000000000226012551849886';
        $this->assertSame(50, strlen($chave));

        $formatada = (new NfseDanfseService)->formatarChave($chave);

        $this->assertSame('1302 6032 2092 7954 0000 1200 0000 0000 0002 2601 2551 8498 86', $formatada);
    }

    private function makeNota(string $status, string $ambiente, bool $comXml = true): NotaFiscal
    {
        $user = User::factory()->create();
        $empresa = Empresa::create([
            'user_id' => $user->id,
            'cnpj' => fake()->unique()->numerify('##############'),
            'razao_social' => 'TESTE',
            'nome_fantasia' => 'TESTE',
            'inscricao_municipal' => '1',
            'regime_tributario' => 3,
            'cep' => '69000000',
            'logradouro' => 'RUA',
            'numero' => '1',
            'bairro' => 'CENTRO',
            'uf' => 'AM',
            'cod_ibge_mun' => '1302603',
            'email' => 'a@b.com',
            'telefone' => '92999999999',
        ]);

        $cliente = Cliente::create([
            'empresa_id' => $empresa->id,
            'cnpj' => '12345678909',
            'razao_social' => 'TOMADOR',
            'logradouro' => 'RUA A',
            'numero' => '10',
            'bairro' => 'CENTRO',
            'cep' => '69000000',
            'cidade_codigo' => '1302603',
            'uf' => 'AM',
        ]);

        $servico = Servico::create([
            'empresa_id' => $empresa->id,
            'nome' => 'Serviço Teste',
            'descricao' => 'Serviço de teste',
            'codigo_tributacao_nacional' => '010301',
            'codigo_tributacao_municipal' => '100',
            'codigo_nbs' => '115011000',
            'valor_unitario' => 100,
        ]);

        $xml = $comXml
            ? (string) file_get_contents(base_path('tests/Fixtures/Nfse/autorizada-minima.xml'))
            : null;

        $nota = NotaFiscal::create([
            'empresa_id' => $empresa->id,
            'cliente_id' => $cliente->id,
            'servico_id' => $servico->id,
            'status' => $status,
            'ambiente' => $ambiente,
            'numero_nfse' => $status === 'autorizada' ? 2 : null,
            'numero_dps' => $status === 'autorizada' ? 3190 : null,
            'chave_acesso' => $comXml ? '13026032209279540000120000000000000226012551849886' : null,
            'codigo_verificacao' => $status === 'autorizada' ? 'ABC1-2345' : null,
            'tomador_cnpj' => '12345678909',
            'tomador_nome' => 'TOMADOR',
            'valor_servico' => 100,
            'descricao' => 'Servico teste',
            'emissao' => now(),
            'trib_issqn' => 1,
            'tp_ret_issqn' => 1,
            'cst_ibscbs' => '000',
            'c_class_trib' => '000001',
            'c_ind_op' => '100301',
            'xml_autorizado' => $xml,
        ]);

        return $nota->fresh(['empresa', 'cliente', 'servico']);
    }
}
