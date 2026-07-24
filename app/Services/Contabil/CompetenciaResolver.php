<?php

namespace App\Services\Contabil;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;

class CompetenciaResolver
{
    /**
     * Extrai data de emissão (dhEmi / dEmi) de XML NF-e/NFC-e.
     */
    public function dataDeXml(?string $xml): ?Carbon
    {
        if ($xml === null || trim($xml) === '') {
            return null;
        }

        $dom = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $ok = @$dom->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $ok) {
            return null;
        }

        $xpath = new DOMXPath($dom);
        $raw = $this->primeiroTexto($xpath, [
            '//*[local-name()="ide"]/*[local-name()="dhEmi"]',
            '//*[local-name()="ide"]/*[local-name()="dEmi"]',
            '//*[local-name()="dhEmi"]',
            '//*[local-name()="dEmi"]',
        ]);

        if ($raw === null || $raw === '') {
            return null;
        }

        try {
            return Carbon::parse($raw)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $queries
     */
    private function primeiroTexto(DOMXPath $xpath, array $queries): ?string
    {
        foreach ($queries as $q) {
            $node = $xpath->query($q)->item(0);
            if ($node) {
                $text = trim((string) $node->textContent);
                if ($text !== '') {
                    return $text;
                }
            }
        }

        return null;
    }
}
