<?php

namespace App\Core\FiscalEngine\Xml;

use DOMDocument;
use InvalidArgumentException;

/**
 * Monta XML de inutilização de numeração NFC-e (mod 65).
 */
class InutilizacaoXmlBuilder
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    public function __construct(
        private readonly string $cUF = '13',
        private readonly string $mod = '65',
        private readonly string $versao = '4.00',
    ) {}

    /**
     * @return array{id: string, xml: string, dom: DOMDocument}
     */
    public function build(
        string $cnpj,
        int $serie,
        int $nNFIni,
        int $nNFFin,
        string $xJust,
        int $tpAmb,
        ?int $ano = null,
    ): array {
        $cnpj = preg_replace('/\D/', '', $cnpj) ?? '';
        $xJust = trim($xJust);

        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ inválido para inutilização.');
        }
        if ($serie < 0 || $serie > 999) {
            throw new InvalidArgumentException('Série inválida para inutilização.');
        }
        if ($nNFIni < 1 || $nNFFin < $nNFIni) {
            throw new InvalidArgumentException('Faixa de numeração inválida para inutilização.');
        }
        if (mb_strlen($xJust) < 15 || mb_strlen($xJust) > 255) {
            throw new InvalidArgumentException('Justificativa de inutilização deve ter entre 15 e 255 caracteres.');
        }

        $ano ??= (int) (new \DateTimeImmutable('now', new \DateTimeZone('America/Manaus')))->format('y');
        $ano = $ano % 100;

        $id = 'ID'
            .$this->cUF
            .$cnpj
            .$this->mod
            .str_pad((string) $serie, 3, '0', STR_PAD_LEFT)
            .str_pad((string) $nNFIni, 9, '0', STR_PAD_LEFT)
            .str_pad((string) $nNFFin, 9, '0', STR_PAD_LEFT);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;

        $inut = $dom->createElementNS(self::NS, 'inutNFe');
        $inut->setAttribute('versao', $this->versao);
        $dom->appendChild($inut);

        $inf = $dom->createElementNS(self::NS, 'infInut');
        $inf->setAttribute('Id', $id);
        $inut->appendChild($inf);

        $this->el($dom, $inf, 'tpAmb', (string) $tpAmb);
        $this->el($dom, $inf, 'xServ', 'INUTILIZAR');
        $this->el($dom, $inf, 'cUF', $this->cUF);
        $this->el($dom, $inf, 'ano', str_pad((string) $ano, 2, '0', STR_PAD_LEFT));
        $this->el($dom, $inf, 'CNPJ', $cnpj);
        $this->el($dom, $inf, 'mod', $this->mod);
        $this->el($dom, $inf, 'serie', (string) $serie);
        $this->el($dom, $inf, 'nNFIni', (string) $nNFIni);
        $this->el($dom, $inf, 'nNFFin', (string) $nNFFin);
        $this->el($dom, $inf, 'xJust', htmlspecialchars($xJust, ENT_XML1 | ENT_COMPAT, 'UTF-8'));

        return [
            'id' => $id,
            'xml' => $dom->saveXML() ?: '',
            'dom' => $dom,
        ];
    }

    private function el(DOMDocument $dom, \DOMElement $parent, string $name, string $value): void
    {
        $parent->appendChild($dom->createElementNS(self::NS, $name, $value));
    }
}
