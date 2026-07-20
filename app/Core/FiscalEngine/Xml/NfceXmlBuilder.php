<?php

namespace App\Core\FiscalEngine\Xml;

use App\Core\FiscalEngine\Dto\NfceEmitData;
use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class NfceXmlBuilder
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    public function __construct(
        private readonly AccessKeyGenerator $accessKeyGenerator = new AccessKeyGenerator,
        private readonly string $cUF = '13',
        private readonly string $mod = '65',
        private readonly string $versao = '4.00',
    ) {}

    /**
     * @return array{chave: string, xml: string, dom: DOMDocument}
     */
    public function build(NfceEmitData $data): array
    {
        if ($data->itens === []) {
            throw new InvalidArgumentException('NFC-e exige ao menos um item.');
        }
        if ($data->pagamentos === []) {
            throw new InvalidArgumentException('NFC-e exige ao menos um pagamento.');
        }

        $dhEmi = $data->dhEmi ?? new \DateTimeImmutable('now', new \DateTimeZone('America/Manaus'));
        $aamm = $dhEmi->format('ym');

        $chave = $this->accessKeyGenerator->generate(
            cUF: $this->cUF,
            aamm: $aamm,
            cnpj: $data->cnpj,
            mod: $this->mod,
            serie: $data->serie,
            numero: $data->numeroNfce,
            tpEmis: $data->tpEmis,
            cNF: $data->cNF,
        );

        $cNF = substr($chave, 35, 8);
        $cDV = substr($chave, 43, 1);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;

        $nfe = $dom->createElementNS(self::NS, 'NFe');
        $dom->appendChild($nfe);

        $infNFe = $this->create($dom, 'infNFe');
        $infNFe->setAttribute('Id', 'NFe'.$chave);
        $infNFe->setAttribute('versao', $this->versao);
        $nfe->appendChild($infNFe);

        $this->appendIde($dom, $infNFe, $data, $dhEmi, $cNF, $cDV);
        $this->appendEmit($dom, $infNFe, $data);

        if ($data->destDoc) {
            $this->appendDest($dom, $infNFe, $data);
        }

        $vProd = 0.0;
        foreach ($data->itens as $i => $item) {
            $nItem = $i + 1;
            $this->appendDet($dom, $infNFe, $item, $nItem);
            $vProd += $item->valorTotal();
        }
        $vProd = round($vProd, 2);

        $this->appendTotal($dom, $infNFe, $vProd);
        $this->appendTransp($dom, $infNFe);
        $this->appendPag($dom, $infNFe, $data->pagamentos, $vProd);
        $this->appendInfAdic($dom, $infNFe, $data->tpAmb);

        return [
            'chave' => $chave,
            'xml' => $dom->saveXML() ?: '',
            'dom' => $dom,
        ];
    }

    private function appendIde(
        DOMDocument $dom,
        DOMElement $infNFe,
        NfceEmitData $data,
        \DateTimeInterface $dhEmi,
        string $cNF,
        string $cDV,
    ): void {
        $ide = $this->create($dom, 'ide');
        $infNFe->appendChild($ide);

        $this->el($dom, $ide, 'cUF', $this->cUF);
        $this->el($dom, $ide, 'cNF', $cNF);
        $this->el($dom, $ide, 'natOp', $this->truncate($data->naturezaOperacao, 60));
        $this->el($dom, $ide, 'mod', $this->mod);
        $this->el($dom, $ide, 'serie', (string) $data->serie);
        $this->el($dom, $ide, 'nNF', (string) $data->numeroNfce);
        $this->el($dom, $ide, 'dhEmi', $dhEmi->format('Y-m-d\TH:i:sP'));
        $this->el($dom, $ide, 'tpNF', '1');
        $this->el($dom, $ide, 'idDest', '1');
        $this->el($dom, $ide, 'cMunFG', preg_replace('/\D/', '', $data->cMun) ?? '');
        $this->el($dom, $ide, 'tpImp', '4');
        $this->el($dom, $ide, 'tpEmis', (string) $data->tpEmis);
        $this->el($dom, $ide, 'cDV', $cDV);
        $this->el($dom, $ide, 'tpAmb', (string) $data->tpAmb);
        $this->el($dom, $ide, 'finNFe', '1');
        $this->el($dom, $ide, 'indFinal', '1');
        $this->el($dom, $ide, 'indPres', '1');
        $this->el($dom, $ide, 'procEmi', '0');
        $this->el($dom, $ide, 'verProc', 'G2MFiscal1.0');
    }

    private function appendEmit(DOMDocument $dom, DOMElement $infNFe, NfceEmitData $data): void
    {
        $emit = $this->create($dom, 'emit');
        $infNFe->appendChild($emit);

        $this->el($dom, $emit, 'CNPJ', preg_replace('/\D/', '', $data->cnpj) ?? '');
        $this->el($dom, $emit, 'xNome', $this->truncate($data->razaoSocial, 60));
        if ($data->nomeFantasia !== '') {
            $this->el($dom, $emit, 'xFant', $this->truncate($data->nomeFantasia, 60));
        }

        $ender = $this->create($dom, 'enderEmit');
        $emit->appendChild($ender);
        $this->el($dom, $ender, 'xLgr', $this->truncate($data->logradouro, 60));
        $this->el($dom, $ender, 'nro', $this->truncate($data->numero ?: 'S/N', 60));
        $this->el($dom, $ender, 'xBairro', $this->truncate($data->bairro, 60));
        $this->el($dom, $ender, 'cMun', preg_replace('/\D/', '', $data->cMun) ?? '');
        $this->el($dom, $ender, 'xMun', $this->truncate($data->municipio, 60));
        $this->el($dom, $ender, 'UF', strtoupper($data->uf));
        $this->el($dom, $ender, 'CEP', str_pad(preg_replace('/\D/', '', $data->cep) ?? '', 8, '0', STR_PAD_LEFT));
        $this->el($dom, $ender, 'cPais', '1058');
        $this->el($dom, $ender, 'xPais', 'BRASIL');
        if ($data->fone !== '') {
            $this->el($dom, $ender, 'fone', preg_replace('/\D/', '', $data->fone) ?? '');
        }

        $this->el($dom, $emit, 'IE', preg_replace('/\D/', '', $data->ie) ?? '');
        $this->el($dom, $emit, 'CRT', (string) $data->crt);
    }

    private function appendDest(DOMDocument $dom, DOMElement $infNFe, NfceEmitData $data): void
    {
        $doc = preg_replace('/\D/', '', (string) $data->destDoc) ?? '';
        if ($doc === '') {
            return;
        }

        $dest = $this->create($dom, 'dest');
        $infNFe->appendChild($dest);

        if (strlen($doc) === 14) {
            $this->el($dom, $dest, 'CNPJ', $doc);
        } else {
            $this->el($dom, $dest, 'CPF', str_pad($doc, 11, '0', STR_PAD_LEFT));
        }

        $nome = $data->destNome ?: ($data->tpAmb === 2 ? 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL' : 'CONSUMIDOR');
        $this->el($dom, $dest, 'xNome', $this->truncate($nome, 60));
        $this->el($dom, $dest, 'indIEDest', '9');
    }

    private function appendDet(DOMDocument $dom, DOMElement $infNFe, NfceItem $item, int $nItem): void
    {
        $det = $this->create($dom, 'det');
        $det->setAttribute('nItem', (string) $nItem);
        $infNFe->appendChild($det);

        $prod = $this->create($dom, 'prod');
        $det->appendChild($prod);

        $this->el($dom, $prod, 'cProd', $item->cProd ?: (string) $nItem);
        $this->el($dom, $prod, 'cEAN', $item->cEAN ?: 'SEM GTIN');
        $this->el($dom, $prod, 'xProd', $this->truncate($item->descricao, 120));
        $this->el($dom, $prod, 'NCM', preg_replace('/\D/', '', $item->ncm) ?? '');
        $this->el($dom, $prod, 'CFOP', preg_replace('/\D/', '', $item->cfop) ?? '');
        $this->el($dom, $prod, 'uCom', $this->truncate($item->unidade, 6));
        $this->el($dom, $prod, 'qCom', $this->decimal($item->quantidade, 4));
        $this->el($dom, $prod, 'vUnCom', $this->decimal($item->valorUnitario, 10));
        $this->el($dom, $prod, 'vProd', $this->decimal($item->valorTotal(), 2));
        $this->el($dom, $prod, 'cEANTrib', $item->cEAN ?: 'SEM GTIN');
        $this->el($dom, $prod, 'uTrib', $this->truncate($item->unidade, 6));
        $this->el($dom, $prod, 'qTrib', $this->decimal($item->quantidade, 4));
        $this->el($dom, $prod, 'vUnTrib', $this->decimal($item->valorUnitario, 10));
        $this->el($dom, $prod, 'indTot', '1');

        $imposto = $this->create($dom, 'imposto');
        $det->appendChild($imposto);

        $icms = $this->create($dom, 'ICMS');
        $imposto->appendChild($icms);
        $tag = $item->csosn === '500' ? 'ICMSSN500' : 'ICMSSN102';
        $sn = $this->create($dom, $tag);
        $icms->appendChild($sn);
        $this->el($dom, $sn, 'orig', '0');
        $this->el($dom, $sn, 'CSOSN', $item->csosn);

        $pis = $this->create($dom, 'PIS');
        $imposto->appendChild($pis);
        $pisOut = $this->create($dom, 'PISOutr');
        $pis->appendChild($pisOut);
        $this->el($dom, $pisOut, 'CST', $item->pisCst);
        $this->el($dom, $pisOut, 'vBC', '0.00');
        $this->el($dom, $pisOut, 'pPIS', '0.00');
        $this->el($dom, $pisOut, 'vPIS', '0.00');

        $cofins = $this->create($dom, 'COFINS');
        $imposto->appendChild($cofins);
        $cofOut = $this->create($dom, 'COFINSOutr');
        $cofins->appendChild($cofOut);
        $this->el($dom, $cofOut, 'CST', $item->cofinsCst);
        $this->el($dom, $cofOut, 'vBC', '0.00');
        $this->el($dom, $cofOut, 'pCOFINS', '0.00');
        $this->el($dom, $cofOut, 'vCOFINS', '0.00');
    }

    private function appendTotal(DOMDocument $dom, DOMElement $infNFe, float $vProd): void
    {
        $total = $this->create($dom, 'total');
        $infNFe->appendChild($total);
        $icmsTot = $this->create($dom, 'ICMSTot');
        $total->appendChild($icmsTot);

        foreach ([
            'vBC' => '0.00',
            'vICMS' => '0.00',
            'vICMSDeson' => '0.00',
            'vFCP' => '0.00',
            'vBCST' => '0.00',
            'vST' => '0.00',
            'vFCPST' => '0.00',
            'vFCPSTRet' => '0.00',
            'vProd' => $this->decimal($vProd, 2),
            'vFrete' => '0.00',
            'vSeg' => '0.00',
            'vDesc' => '0.00',
            'vII' => '0.00',
            'vIPI' => '0.00',
            'vIPIDevol' => '0.00',
            'vPIS' => '0.00',
            'vCOFINS' => '0.00',
            'vOutro' => '0.00',
            'vNF' => $this->decimal($vProd, 2),
        ] as $tag => $val) {
            $this->el($dom, $icmsTot, $tag, $val);
        }
    }

    private function appendTransp(DOMDocument $dom, DOMElement $infNFe): void
    {
        $transp = $this->create($dom, 'transp');
        $infNFe->appendChild($transp);
        $this->el($dom, $transp, 'modFrete', '9');
    }

    /**
     * @param  list<NfcePayment>  $pagamentos
     */
    private function appendPag(DOMDocument $dom, DOMElement $infNFe, array $pagamentos, float $vProd): void
    {
        $pag = $this->create($dom, 'pag');
        $infNFe->appendChild($pag);

        $vTroco = null;
        foreach ($pagamentos as $pagamento) {
            $detPag = $this->create($dom, 'detPag');
            $pag->appendChild($detPag);
            $this->el($dom, $detPag, 'tPag', $pagamento->tPag);
            $this->el($dom, $detPag, 'vPag', $this->decimal($pagamento->vPag, 2));
            if ($pagamento->tPag === '01' && $pagamento->vTroco !== null && $pagamento->vTroco > 0) {
                $vTroco = $pagamento->vTroco;
            }
        }

        if ($vTroco !== null) {
            $this->el($dom, $pag, 'vTroco', $this->decimal($vTroco, 2));
        }
    }

    private function appendInfAdic(DOMDocument $dom, DOMElement $infNFe, int $tpAmb): void
    {
        if ($tpAmb !== 2) {
            return;
        }

        $infAdic = $this->create($dom, 'infAdic');
        $infNFe->appendChild($infAdic);
        $this->el($dom, $infAdic, 'infCpl', 'NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL');
    }

    private function el(DOMDocument $dom, DOMElement $parent, string $name, string $value): DOMElement
    {
        $el = $this->create($dom, $name, htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $parent->appendChild($el);

        return $el;
    }

    private function create(DOMDocument $dom, string $name, ?string $value = null): DOMElement
    {
        if ($value === null) {
            return $dom->createElementNS(self::NS, $name);
        }

        return $dom->createElementNS(self::NS, $name, $value);
    }

    private function decimal(float $value, int $decimals): string
    {
        return number_format($value, $decimals, '.', '');
    }

    private function truncate(string $value, int $max): string
    {
        $value = trim($value);
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return mb_substr($value, 0, $max);
    }
}
