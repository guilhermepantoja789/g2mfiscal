<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>NFS-e</title>
    <style>
        @page { margin: 10px; }
        body {
            font-family: "Helvetica", Arial, sans-serif;
            font-size: 8px;
            color: #000;
            line-height: 1.1;
        }
        table { width: 100%; border-spacing: 0; border-collapse: collapse; margin-bottom: -1px; }
        td, th { border: 1px solid #000; padding: 2px 4px; vertical-align: top; }

        .no-border { border: none !important; }
        .b-bottom { border-bottom: 1px solid #000; }
        .b-right { border-right: 1px solid #000; }
        .bg-gray { background-color: #e0e0e0; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-bold { font-weight: bold; }

        .lbl { font-size: 6px; color: #444; display: block; margin-bottom: 1px; text-transform: uppercase; }
        .val { font-size: 9px; font-weight: bold; display: block; }
        .val-lg { font-size: 11px; }
        .val-xl { font-size: 14px; }

        .section-header {
            background-color: #d9d9d9;
            font-weight: bold;
            font-size: 8px;
            padding: 3px;
            border: 1px solid #000;
            margin-top: -1px;
            text-align: center;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

<table>
    <tr>
        <td width="60%" style="vertical-align: top; padding: 5px;">
            <div style="font-size: 16px; font-weight: bold;">NFSe</div>
            <div style="font-size: 10px; font-weight: bold;">Nota Fiscal de Serviço eletrônica</div>
            <br>
            <div style="font-size: 9px; font-weight: bold;">DANFSe v1.0</div>
            <div style="font-size: 8px;">Documento Auxiliar da NFS-e</div>

            <br><br>
            <div style="font-weight: bold; font-size: 9px;">Prefeitura Municipal de {{ $xml->infNFSe->xLocEmi }}</div>
            <div style="font-size: 7px;">Secretaria Municipal de Finanças / Fazenda</div>
        </td>

        <td width="40%" style="padding: 0;">
            <table class="no-border">
                <tr>
                    <td class="no-border b-bottom" style="padding: 4px;">
                        <span class="lbl">Chave de Acesso da NFS-e</span>
                        <span class="val" style="letter-spacing: 0.5px; word-break: break-all; font-size: 8px;">
                                {{ $chaveAcesso }}
                            </span>
                    </td>
                </tr>
                <tr>
                    <td class="no-border b-bottom" style="padding: 4px;">
                        <span class="lbl">Número da NFS-e</span>
                        <span class="val val-xl text-right">{{ $xml->infNFSe->nNFSe }}</span>
                    </td>
                </tr>
                <tr>
                    <td class="no-border" style="padding: 4px; text-align: center;">
                        <span class="lbl" style="text-align: left;">A autenticidade desta NFS-e pode ser verificada pela leitura deste código QR ou pela consulta da chave de acesso no portal nacional da NFS-e</span>
                        <div style="margin-top: 5px;">
                            @if($qrCodeBase64)
                                <img src="{{ $qrCodeBase64 }}" alt="QR Code" style="width: 80px; height: 80px;">
                            @else
                                <div style="border: 1px dashed #ccc; width:80px; height:80px; margin:0 auto; padding-top:30px; font-size:8px;">Erro QR</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table>
    <tr>
        <td width="25%"><span class="lbl">Número da DPS</span><span class="val">{{ $xml->infNFSe->DPS->infDPS->nDPS }}</span></td>
        <td width="25%"><span class="lbl">Série da DPS</span><span class="val">{{ $xml->infNFSe->DPS->infDPS->serie }}</span></td>
        <td width="25%"><span class="lbl">Data e Hora de emissão da NFS-e</span><span class="val">{{ date('d/m/Y H:i:s', strtotime((string)$xml->infNFSe->dhProc)) }}</span></td>
        <td width="25%"><span class="lbl">Competência da NFS-e</span><span class="val">{{ date('d/m/Y', strtotime((string)$xml->infNFSe->DPS->infDPS->dCompet)) }}</span></td>
    </tr>
</table>

<div class="section-header">EMITENTE DA NFS-e</div>
<table>
    <tr>
        <td colspan="3"><span class="lbl">Prestador do Serviço - Nome / Nome Empresarial</span><span class="val">{{ $xml->infNFSe->emit->xNome }}</span></td>
        <td width="30%"><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $xml->infNFSe->emit->CNPJ }}</span></td>
    </tr>
    <tr>
        <td colspan="4"><span class="lbl">Endereço</span><span class="val" style="font-weight: normal;">
                {{ $xml->infNFSe->emit->enderNac->xLgr }}, {{ $xml->infNFSe->emit->enderNac->nro }} - {{ $xml->infNFSe->emit->enderNac->xBairro }}
            </span></td>
    </tr>
    <tr>
        <td><span class="lbl">Município</span><span class="val">{{ $xml->infNFSe->emit->enderNac->xMun ?? $xml->infNFSe->xLocEmi }} - {{ $xml->infNFSe->emit->enderNac->UF }}</span></td>
        <td><span class="lbl">CEP</span><span class="val">{{ $xml->infNFSe->emit->enderNac->CEP }}</span></td>
        <td><span class="lbl">Inscrição Municipal</span><span class="val">{{ $xml->infNFSe->emit->IM }}</span></td>
        <td><span class="lbl">Telefone</span><span class="val">{{ $xml->infNFSe->emit->fone }}</span></td>
    </tr>
    <tr>
        <td colspan="4"><span class="lbl">E-mail</span><span class="val">{{ $xml->infNFSe->emit->email }}</span></td>
    </tr>
</table>

<div class="section-header">TOMADOR DO SERVIÇO</div>
<table>
    <tr>
        <td colspan="3"><span class="lbl">Nome / Nome Empresarial</span><span class="val">{{ $tomador->razao_social ?? 'Consumidor Final' }}</span></td>
        <td width="30%"><span class="lbl">CNPJ / CPF / NIF</span><span class="val">{{ $tomador->documento ?? 'Não Informado' }}</span></td>
    </tr>
    <tr>
        <td colspan="4"><span class="lbl">Endereço</span><span class="val" style="font-weight: normal;">
                {{ $tomador->endereco }} {{ $tomador->numero }} {{ $tomador->complemento ? '- ' . $tomador->complemento : '' }} - {{ $tomador->bairro }}
            </span></td>
    </tr>
    <tr>
        <td><span class="lbl">Município</span><span class="val">{{ $tomador->cidade }} - {{ $tomador->uf }}</span></td>
        <td><span class="lbl">CEP</span><span class="val">{{ $tomador->cep }}</span></td>
        <td><span class="lbl">Inscrição Municipal</span><span class="val">{{ $tomador->inscricao_municipal }}</span></td>
        <td><span class="lbl">Telefone</span><span class="val">{{ $tomador->telefone }}</span></td>
    </tr>
    <tr>
        <td colspan="4"><span class="lbl">E-mail</span><span class="val">{{ $tomador->email }}</span></td>
    </tr>
</table>

<div class="section-header">INTERMEDIÁRIO DO SERVIÇO</div>
<table>
    <tr>
        <td class="text-center" style="padding: 3px;"><span class="val" style="font-weight: normal;">NÃO IDENTIFICADO NA NFS-e</span></td>
    </tr>
</table>

<div class="section-header">DADOS DO SERVIÇO</div>
<table>
    <tr>
        <td colspan="2">
            <span class="lbl">Código de Tributação Municipal</span>
            <span class="val">{{ $xml->infNFSe->DPS->infDPS->serv->cServ->cTribMun }} - {{ $xml->infNFSe->xTribMun }}</span>
        </td>
        <td colspan="2">
            <span class="lbl">Código de Tributação Nacional</span>
            <span class="val">{{ $xml->infNFSe->DPS->infDPS->serv->cServ->cTribNac }} - {{ $xml->infNFSe->xTribNac }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <span class="lbl">País da Prestação</span>
            <span class="val">Brasil</span>
        </td>
        <td colspan="2">
            <span class="lbl">Município de Incidência do ISSQN</span>
            <span class="val">{{ $xml->infNFSe->xLocIncid }} - {{ $xml->infNFSe->emit->enderNac->UF }}</span>
        </td>
    </tr>
    <tr>
        <td colspan="2">
            <span class="lbl">Local da Prestação</span>
            <span class="val">{{ $xml->infNFSe->xLocPrestacao }} - {{ $xml->infNFSe->emit->enderNac->UF }}</span>
        </td>
        <td colspan="2">
            <span class="lbl">País Resultado da Prestação</span>
            <span class="val">-</span>
        </td>
    </tr>
    <tr>
        <td colspan="4" style="min-height: 80px; padding: 5px;">
            <div style="font-size: 10px;">
                {!! nl2br($xml->infNFSe->DPS->infDPS->serv->cServ->xDescServ) !!}
            </div>
        </td>
    </tr>
</table>

<div class="section-header">TRIBUTAÇÃO MUNICIPAL</div>
<table>
    <tr>
        <td><span class="lbl">Tributação do ISSQN</span><span class="val">Operação Tributável</span></td>
        <td><span class="lbl">Tipo de Imunidade</span><span class="val">-</span></td>
        <td><span class="lbl">Suspensão da Exigibilidade</span><span class="val">Não</span></td>
        <td><span class="lbl">Retenção do ISSQN</span><span class="val">
                @if(isset($xml->infNFSe->DPS->infDPS->valores->trib->tribMun->tpRetISSQN) && $xml->infNFSe->DPS->infDPS->valores->trib->tribMun->tpRetISSQN == 1) Não Retido @else Retido @endif
             </span></td>
    </tr>
    <tr>
        <td><span class="lbl">Valor do Serviço</span><span class="val text-right">R$ {{ number_format((float)$xml->infNFSe->DPS->infDPS->valores->vServPrest->vServ, 2, ',', '.') }}</span></td>
        <td><span class="lbl">Desconto Incondicionado</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">Total Deduções/Reduções</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">Base de Cálculo ISSQN</span><span class="val text-right">R$ {{ number_format((float)$xml->infNFSe->DPS->infDPS->valores->vServPrest->vServ, 2, ',', '.') }}</span></td>
    </tr>
    <tr>
        <td><span class="lbl">Alíquota Aplicada</span><span class="val text-right">-</span></td>
        <td><span class="lbl">ISSQN Apurado</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">Valor do ISSQN</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">Desconto Condicionado</span><span class="val text-right">R$ 0,00</span></td>
    </tr>
</table>

<div class="section-header">TRIBUTAÇÃO FEDERAL</div>
<table>
    <tr>
        <td><span class="lbl">PIS</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">COFINS</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">IRRF</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">INSS</span><span class="val text-right">R$ 0,00</span></td>
        <td><span class="lbl">CSLL</span><span class="val text-right">R$ 0,00</span></td>
    </tr>
    <tr>
        <td colspan="5">
            <span class="lbl">Regime de Apuração dos Tributos Federais</span>
            <span class="val">
                    Regime de apuração dos tributos federais e municipal pelo Simples Nacional
                </span>
        </td>
    </tr>
</table>

<div class="section-header">VALOR TOTAL DA NFS-e</div>
<table>
    <tr>
        <td class="text-center" style="padding: 10px; background-color: #f9f9f9;">
            <span class="val val-xl">R$ {{ number_format((float)$xml->infNFSe->valores->vLiq, 2, ',', '.') }}</span>
        </td>
    </tr>
</table>

<div class="section-header">INFORMAÇÕES ADICIONAIS</div>
<table>
    <tr>
        <td style="padding: 5px;">
            <span class="lbl">Regime Especial de Tributação</span>
            <span class="val">Nenhum</span>
            <br>
            <span class="lbl">Outras Informações</span>
            <span class="val" style="font-weight: normal; font-size: 8px;">
                    Lei da Transparência (Lei 12.741/2012): Tributos Totais Aprox.: R$ 0,00 (0,00%) <br>
                    Ambiente: {{ $xml->infNFSe->ambGer == 1 ? 'Produção' : 'Homologação' }}
                </span>
        </td>
    </tr>
</table>

</body>
</html>
