<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class TestarGerarPdf extends Command
{
    protected $signature = 'teste:gerar_pdf';
    protected $description = 'Gera DANFSe Padrão Nacional com QR Code Base64';

    public function handle()
    {
        // XML CORRIGIDO E ATUALIZADO
        $xmlString = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<NFSe xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.01">
  <infNFSe Id="NFS13026032209279540000120000000000000226012551849886">
    <xLocEmi>Manaus</xLocEmi>
    <xLocPrestacao>Manaus</xLocPrestacao>
    <nNFSe>2</nNFSe>
    <cLocIncid>1302603</cLocIncid>
    <xLocIncid>Manaus</xLocIncid>
    <xTribNac>Assessoria e consultoria em informatica.</xTribNac>
    <xTribMun>Assessoria e consultoria em informatica.</xTribMun>
    <verAplic>SefinNacional_1.5.0</verAplic>
    <ambGer>2</ambGer>
    <tpEmis>1</tpEmis>
    <procEmi>1</procEmi>
    <cStat>100</cStat>
    <dhProc>2026-01-08T18:48:30-03:00</dhProc>
    <nDFSe>3357</nDFSe>
    <emit>
      <CNPJ>09279540000120</CNPJ>
      <IM>12248401</IM>
      <xNome>G. DA M. MARTINS &amp; CIA LTDA</xNome>
      <enderNac>
        <xLgr>RAPHAEL ALMEIDA</xLgr>
        <nro>33</nro>
        <xBairro>NOVO ALEIXO</xBairro>
        <cMun>1302603</cMun>
        <xMun>Manaus</xMun>
        <UF>AM</UF>
        <CEP>69098100</CEP>
      </enderNac>
      <fone>(92) 98114-9511</fone>
      <email>glaucio@gdoism.com.br</email>
    </emit>
    <valores>
      <vLiq>10.00</vLiq>
    </valores>
    <DPS xmlns="http://www.sped.fazenda.gov.br/nfse" versao="1.00">
      <infDPS Id="DPS130260320927954000012000001000000000003190">
        <tpAmb>2</tpAmb>
        <dhEmi>2026-01-08T18:48:19-03:00</dhEmi>
        <verAplic>1.0.0</verAplic>
        <serie>00001</serie>
        <nDPS>3190</nDPS>
        <dCompet>2026-01-08</dCompet>
        <tpEmit>1</tpEmit>
        <cLocEmi>1302603</cLocEmi>
        <prest>
          <CNPJ>09279540000120</CNPJ>
          <IM>12248401</IM>
          <regTrib>
            <opSimpNac>3</opSimpNac>
            <regApTribSN>1</regApTribSN>
            <regEspTrib>0</regEspTrib>
          </regTrib>
        </prest>
        <toma>
          <CNPJ>00000000000191</CNPJ>
          <xNome>TOMADOR TESTE DE HOMOLOGACAO</xNome>
          <enderNac>
             <CEP>69093-770</CEP>
             <xMun>Manaus</xMun>
             <UF>AM</UF>
          </enderNac>
        </toma>
        <serv>
          <locPrest>
            <cLocPrestacao>1302603</cLocPrestacao>
          </locPrest>
          <cServ>
            <cTribNac>010601</cTribNac>
            <cTribMun>100</cTribMun>
            <xDescServ>Teste de Emissao API Nacional - G2m Fiscal</xDescServ>
          </cServ>
        </serv>
        <valores>
          <vServPrest>
            <vServ>10.00</vServ>
          </vServPrest>
          <trib>
            <tribMun>
               <tpRetISSQN>1</tpRetISSQN>
            </tribMun>
          </trib>
        </valores>
      </infDPS>
    </DPS>
  </infNFSe>
</NFSe>
XML;

        $xmlString = str_replace('xmlns=', 'ns=', $xmlString);
        $xml = simplexml_load_string($xmlString);

        if (!$xml) { $this->error("Erro XML"); return; }

        $this->info("Gerando QR Code e PDF...");

        // 1. EXTRAIR A CHAVE CORRETAMENTE
        $idAttribute = (string) $xml->infNFSe->attributes()['Id'];
        $chaveAcesso = str_replace('NFS', '', $idAttribute);

        // 2. GERAR QR CODE EM BASE64 (ROBUSTEZ TOTAL)
        // Isso evita que o PDF fique em branco se o servidor bloquear acesso externo na hora de gerar
        $baseUrlConsulta = config('services.nfse_nacional.url_consulta');
        $qrLink = "{$baseUrlConsulta}/?tpc=1&chave=" . $chaveAcesso;
        $qrApiUrl = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qrLink);

        try {
            // Baixa a imagem para a memória
            $qrContent = file_get_contents($qrApiUrl);
            $qrBase64 = 'data:image/png;base64,' . base64_encode($qrContent);
        } catch (\Exception $e) {
            // Fallback se estiver sem internet no ambiente de dev
            $this->warn("Não foi possível gerar QR Code: " . $e->getMessage());
            $qrBase64 = null;
        }

        // 3. CONFIGURAR PDF
        $pdf = Pdf::loadView('pdf.danfse', [
            'xml' => $xml,
            'qrCodeBase64' => $qrBase64,
            'chaveAcesso' => $chaveAcesso
        ])
            ->setPaper('a4', 'portrait')
            ->setOption('isRemoteEnabled', true) // Importante para imagens externas
            ->setOption('margin-top', 0)
            ->setOption('margin-right', 0)
            ->setOption('margin-bottom', 0)
            ->setOption('margin-left', 0);

        $path = 'public/notas/danfse_v3.pdf';
        Storage::put($path, $pdf->output());

        $this->info("Sucesso! Salvo em: storage/app/{$path}");
    }
}
