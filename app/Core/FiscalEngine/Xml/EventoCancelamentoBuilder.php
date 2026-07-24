<?php

namespace App\Core\FiscalEngine\Xml;

use DOMDocument;
use InvalidArgumentException;

/**
 * Monta XML do evento de cancelamento NFC-e (tpEvento 110111).
 */
class EventoCancelamentoBuilder
{
    private const NS = 'http://www.portalfiscal.inf.br/nfe';

    public function __construct(
        private readonly string $cUF = '13',
        private readonly string $versao = '1.00',
    ) {}

    /**
     * @return array{id: string, xml: string, dom: DOMDocument}
     */
    public function build(
        string $chave,
        string $cnpj,
        string $nProt,
        string $xJust,
        int $tpAmb,
        int $nSeqEvento = 1,
        ?\DateTimeInterface $dhEvento = null,
    ): array {
        $chave = preg_replace('/\D/', '', $chave) ?? '';
        $cnpj = preg_replace('/\D/', '', $cnpj) ?? '';
        $nProt = preg_replace('/\D/', '', $nProt) ?? '';
        $xJust = trim($xJust);

        if (strlen($chave) !== 44) {
            throw new InvalidArgumentException('Chave NFC-e inválida para cancelamento.');
        }
        if (strlen($cnpj) !== 14) {
            throw new InvalidArgumentException('CNPJ inválido para cancelamento.');
        }
        if ($nProt === '') {
            throw new InvalidArgumentException('Protocolo de autorização obrigatório para cancelamento.');
        }
        if (mb_strlen($xJust) < 15 || mb_strlen($xJust) > 255) {
            throw new InvalidArgumentException('Justificativa de cancelamento deve ter entre 15 e 255 caracteres.');
        }

        $dhEvento ??= new \DateTimeImmutable('now', new \DateTimeZone('America/Manaus'));
        $id = 'ID110111'.$chave.str_pad((string) $nSeqEvento, 2, '0', STR_PAD_LEFT);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = false;
        $dom->preserveWhiteSpace = false;

        $evento = $dom->createElementNS(self::NS, 'evento');
        $evento->setAttribute('versao', $this->versao);
        $dom->appendChild($evento);

        $inf = $dom->createElementNS(self::NS, 'infEvento');
        $inf->setAttribute('Id', $id);
        $evento->appendChild($inf);

        $this->el($dom, $inf, 'cOrgao', $this->cUF);
        $this->el($dom, $inf, 'tpAmb', (string) $tpAmb);
        $this->el($dom, $inf, 'CNPJ', $cnpj);
        $this->el($dom, $inf, 'chNFe', $chave);
        $this->el($dom, $inf, 'dhEvento', $dhEvento->format('Y-m-d\TH:i:sP'));
        $this->el($dom, $inf, 'tpEvento', '110111');
        $this->el($dom, $inf, 'nSeqEvento', (string) $nSeqEvento);
        $this->el($dom, $inf, 'verEvento', $this->versao);

        $det = $dom->createElementNS(self::NS, 'detEvento');
        $det->setAttribute('versao', $this->versao);
        $inf->appendChild($det);
        $this->el($dom, $det, 'descEvento', 'Cancelamento');
        $this->el($dom, $det, 'nProt', $nProt);
        $this->el($dom, $det, 'xJust', htmlspecialchars($xJust, ENT_XML1 | ENT_COMPAT, 'UTF-8'));

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
