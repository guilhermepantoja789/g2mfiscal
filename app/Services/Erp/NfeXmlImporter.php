<?php

namespace App\Services\Erp;

use App\Models\DocumentoComercial;
use App\Models\Empresa;
use App\Models\Fornecedor;
use App\Models\Produto;
use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;

class NfeXmlImporter
{
    public function __construct(
        private DocumentoOrchestrator $orchestrator,
    ) {}

    /**
     * Parseia XML NF-e 55 e retorna preview estruturado (sem persistir documento).
     *
     * @return array{
     *   chave: string,
     *   numero: string,
     *   serie: string,
     *   valor_total: float,
     *   fornecedor: array<string, mixed>,
     *   itens: list<array<string, mixed>>,
     *   xml: string
     * }
     */
    public function preview(string $xmlContent): array
    {
        $parsed = $this->parse($xmlContent);

        return [
            'chave' => $parsed['chave'],
            'numero' => $parsed['numero'],
            'serie' => $parsed['serie'],
            'valor_total' => $parsed['valor_total'],
            'fornecedor' => $parsed['fornecedor'],
            'itens' => $parsed['itens'],
            'xml' => $parsed['xml'],
        ];
    }

    /**
     * Importa XML e cria documento comercial de compra (rascunho ou já confirmado).
     */
    public function importar(Empresa $empresa, string $xmlContent, bool $confirmar = true): DocumentoComercial
    {
        $parsed = $this->parse($xmlContent);

        if ($parsed['chave'] !== '') {
            $existente = DocumentoComercial::query()
                ->where('empresa_id', $empresa->id)
                ->where('chave_nfe', $parsed['chave'])
                ->first();

            if ($existente) {
                throw new RuntimeException('NF-e já importada (documento #'.$existente->id.').');
            }
        }

        $fornecedor = $this->upsertFornecedor($empresa, $parsed['fornecedor']);

        $itens = [];
        foreach ($parsed['itens'] as $item) {
            $produto = $this->resolverProduto($empresa, $item);
            $itens[] = [
                'produto_id' => $produto->id,
                'descricao' => $item['descricao'],
                'ncm' => $item['ncm'],
                'cfop' => $item['cfop'],
                'csosn' => $item['csosn'] ?? '102',
                'unidade' => $item['unidade'],
                'codigo_fornecedor' => $item['codigo'],
                'ean' => $item['ean'],
                'quantidade' => $item['quantidade'],
                'valor_unitario' => $item['valor_unitario'],
            ];
        }

        $doc = $this->orchestrator->criarRascunho($empresa, [
            'tipo' => DocumentoComercial::TIPO_COMPRA,
            'canal_fiscal' => DocumentoComercial::CANAL_NFE_ENTRADA,
            'fornecedor_id' => $fornecedor->id,
            'pago_avista' => false,
            'vencimento' => now()->addDays(30)->toDateString(),
            'chave_nfe' => $parsed['chave'] ?: null,
            'xml_nfe' => $parsed['xml'],
            'numero_nfe' => $parsed['numero'],
            'serie_nfe' => $parsed['serie'],
            'data_competencia' => $this->normalizarDataEmissao($parsed['data_emissao'] ?? null),
            'observacoes' => 'Importação XML NF-e '.$parsed['chave'],
            'itens' => $itens,
        ]);

        if ($confirmar) {
            $doc = $this->orchestrator->confirmar($doc);
        }

        return $doc;
    }

    /**
     * @return array{
     *   chave: string,
     *   numero: string,
     *   serie: string,
     *   valor_total: float,
     *   fornecedor: array<string, mixed>,
     *   itens: list<array<string, mixed>>,
     *   xml: string
     * }
     */
    public function parse(string $xmlContent): array
    {
        $xmlContent = trim($xmlContent);
        if ($xmlContent === '') {
            throw new InvalidArgumentException('XML vazio.');
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $ok = $dom->loadXML($xmlContent);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $ok) {
            throw new InvalidArgumentException('XML inválido.');
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('n', 'http://www.portalfiscal.inf.br/nfe');

        $infNfe = $xpath->query('//n:infNFe')->item(0)
            ?? $xpath->query('//*[local-name()="infNFe"]')->item(0);

        if (! $infNfe) {
            throw new InvalidArgumentException('XML não contém infNFe (NF-e modelo 55).');
        }

        $mod = $this->text($xpath, './/*[local-name()="mod"]', $infNfe) ?: '55';
        if ($mod !== '55') {
            throw new InvalidArgumentException('Somente NF-e modelo 55 é suportada (mod='.$mod.').');
        }

        $chave = '';
        if ($infNfe instanceof \DOMElement && $infNfe->hasAttribute('Id')) {
            $chave = preg_replace('/\D/', '', $infNfe->getAttribute('Id')) ?? '';
        }
        if ($chave === '') {
            $chave = preg_replace('/\D/', '', $this->text($xpath, '//*[local-name()="chNFe"]')) ?? '';
        }

        $numero = $this->text($xpath, './/*[local-name()="ide"]/*[local-name()="nNF"]', $infNfe);
        $serie = $this->text($xpath, './/*[local-name()="ide"]/*[local-name()="serie"]', $infNfe);
        $valorTotal = (float) str_replace(',', '.', $this->text($xpath, './/*[local-name()="total"]/*[local-name()="ICMSTot"]/*[local-name()="vNF"]', $infNfe) ?: '0');
        $dhEmi = $this->text($xpath, './/*[local-name()="ide"]/*[local-name()="dhEmi"]', $infNfe)
            ?: $this->text($xpath, './/*[local-name()="ide"]/*[local-name()="dEmi"]', $infNfe);

        $emit = $xpath->query('.//*[local-name()="emit"]', $infNfe)->item(0);
        $fornecedor = [
            'cnpj' => preg_replace('/\D/', '', $this->text($xpath, './/*[local-name()="CNPJ"]', $emit) ?: $this->text($xpath, './/*[local-name()="CPF"]', $emit)) ?? '',
            'razao_social' => $this->text($xpath, './/*[local-name()="xNome"]', $emit),
            'inscricao_estadual' => $this->text($xpath, './/*[local-name()="IE"]', $emit) ?: null,
            'logradouro' => $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="xLgr"]', $emit) ?: null,
            'numero' => $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="nro"]', $emit) ?: null,
            'bairro' => $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="xBairro"]', $emit) ?: null,
            'uf' => $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="UF"]', $emit) ?: null,
            'cep' => preg_replace('/\D/', '', $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="CEP"]', $emit) ?: '') ?: null,
            'cidade_codigo' => $this->text($xpath, './/*[local-name()="enderEmit"]/*[local-name()="cMun"]', $emit) ?: null,
        ];

        if ($fornecedor['cnpj'] === '' || $fornecedor['razao_social'] === '') {
            throw new InvalidArgumentException('Emitente da NF-e incompleto.');
        }

        $itens = [];
        $dets = $xpath->query('.//*[local-name()="det"]', $infNfe);
        foreach ($dets as $det) {
            $prod = $xpath->query('.//*[local-name()="prod"]', $det)->item(0);
            $qCom = (float) str_replace(',', '.', $this->text($xpath, './/*[local-name()="qCom"]', $prod) ?: '0');
            $vUn = (float) str_replace(',', '.', $this->text($xpath, './/*[local-name()="vUnCom"]', $prod) ?: '0');
            $vProd = (float) str_replace(',', '.', $this->text($xpath, './/*[local-name()="vProd"]', $prod) ?: '0');
            if ($vUn <= 0 && $qCom > 0 && $vProd > 0) {
                $vUn = round($vProd / $qCom, 4);
            }

            $ean = preg_replace('/\D/', '', $this->text($xpath, './/*[local-name()="cEAN"]', $prod) ?: '') ?: null;
            if ($ean === 'SEM GTIN' || $ean === '') {
                $ean = null;
            }

            $itens[] = [
                'codigo' => $this->text($xpath, './/*[local-name()="cProd"]', $prod),
                'ean' => $ean,
                'descricao' => $this->text($xpath, './/*[local-name()="xProd"]', $prod) ?: 'Produto',
                'ncm' => preg_replace('/\D/', '', $this->text($xpath, './/*[local-name()="NCM"]', $prod) ?: '') ?: null,
                'cfop' => $this->text($xpath, './/*[local-name()="CFOP"]', $prod) ?: null,
                'unidade' => $this->text($xpath, './/*[local-name()="uCom"]', $prod) ?: 'UN',
                'quantidade' => $qCom > 0 ? $qCom : 1,
                'valor_unitario' => $vUn,
                'valor_total' => $vProd > 0 ? $vProd : round($qCom * $vUn, 2),
            ];
        }

        if ($itens === []) {
            throw new InvalidArgumentException('NF-e sem itens.');
        }

        return [
            'chave' => $chave,
            'numero' => $numero,
            'serie' => $serie,
            'valor_total' => $valorTotal > 0 ? $valorTotal : array_sum(array_column($itens, 'valor_total')),
            'data_emissao' => $dhEmi ?: null,
            'fornecedor' => $fornecedor,
            'itens' => $itens,
            'xml' => $xmlContent,
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private function upsertFornecedor(Empresa $empresa, array $dados): Fornecedor
    {
        $cnpj = preg_replace('/\D/', '', (string) $dados['cnpj']);

        $fornecedor = Fornecedor::query()
            ->where('empresa_id', $empresa->id)
            ->where('cnpj', $cnpj)
            ->first();

        $payload = [
            'razao_social' => $dados['razao_social'],
            'inscricao_estadual' => $dados['inscricao_estadual'] ?? null,
            'logradouro' => $dados['logradouro'] ?? null,
            'numero' => $dados['numero'] ?? null,
            'bairro' => $dados['bairro'] ?? null,
            'uf' => $dados['uf'] ?? null,
            'cep' => $dados['cep'] ?? null,
            'cidade_codigo' => $dados['cidade_codigo'] ?? null,
        ];

        if ($fornecedor) {
            $fornecedor->update($payload);

            return $fornecedor;
        }

        return Fornecedor::create([
            'empresa_id' => $empresa->id,
            'cnpj' => $cnpj,
            ...$payload,
        ]);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolverProduto(Empresa $empresa, array $item): Produto
    {
        $ean = $item['ean'] ?? null;
        $codigo = $item['codigo'] ?? null;

        $produto = null;
        if ($ean) {
            $produto = Produto::query()
                ->where('empresa_id', $empresa->id)
                ->where('ean', $ean)
                ->first();
        }

        if (! $produto && $codigo) {
            $produto = Produto::query()
                ->where('empresa_id', $empresa->id)
                ->where('sku', $codigo)
                ->first();
        }

        if ($produto) {
            return $produto;
        }

        return Produto::create([
            'empresa_id' => $empresa->id,
            'sku' => $codigo,
            'ean' => $ean,
            'descricao' => $item['descricao'],
            'ncm' => $item['ncm'],
            'cfop' => '1102',
            'csosn' => '102',
            'unidade' => $item['unidade'] ?? 'UN',
            'preco_venda' => 0,
            'custo_medio' => $item['valor_unitario'] ?? 0,
            'estoque_atual' => 0,
            'controla_estoque' => true,
            'ativo' => true,
        ]);
    }

    private function normalizarDataEmissao(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($raw)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function text(DOMXPath $xpath, string $query, ?\DOMNode $context = null): string
    {
        $nodes = $context
            ? $xpath->query($query, $context)
            : $xpath->query($query);

        if (! $nodes || $nodes->length === 0) {
            return '';
        }

        return trim((string) $nodes->item(0)?->textContent);
    }
}
