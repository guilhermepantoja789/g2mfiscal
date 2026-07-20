<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 4mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #111;
            width: 72mm;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #333; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; padding: 1px 0; }
        .chave { font-size: 8px; word-break: break-all; }
        img.qr { width: 110px; height: 110px; }
    </style>
</head>
<body>
    <div class="center bold">{{ $empresa->nome_fantasia ?: $empresa->razao_social }}</div>
    <div class="center">CNPJ: {{ $empresa->cnpj }} — IE: {{ $empresa->inscricao_estadual }}</div>
    <div class="center">{{ $empresa->logradouro }}, {{ $empresa->numero }} — {{ $empresa->bairro }}</div>
    <div class="line"></div>
    <div class="center bold">DANFE NFC-e — Documento Auxiliar</div>
    <div class="center">NFC-e nº {{ $nfce->numero }} Série {{ $nfce->serie }}</div>
    <div class="center">
        Ambiente: {{ $nfce->ambiente == 1 ? 'Produção' : 'Homologação' }}
        @if($nfce->protocolo) — Prot. {{ $nfce->protocolo }} @endif
    </div>
    <div class="line"></div>

    <table>
        <tr class="bold">
            <td>Item</td>
            <td style="text-align:right">Qtd</td>
            <td style="text-align:right">Vl Unit</td>
            <td style="text-align:right">Total</td>
        </tr>
        @foreach($itens as $i => $item)
            @php
                $tot = round(($item['quantidade'] ?? 0) * ($item['valor_unitario'] ?? 0), 2);
            @endphp
            <tr>
                <td colspan="4">{{ $i + 1 }}. {{ $item['descricao'] ?? '' }}</td>
            </tr>
            <tr>
                <td></td>
                <td style="text-align:right">{{ number_format($item['quantidade'] ?? 0, 3, ',', '.') }}</td>
                <td style="text-align:right">{{ number_format($item['valor_unitario'] ?? 0, 2, ',', '.') }}</td>
                <td style="text-align:right">{{ number_format($tot, 2, ',', '.') }}</td>
            </tr>
        @endforeach
    </table>

    <div class="line"></div>
    <div class="bold" style="text-align:right">TOTAL R$ {{ number_format($nfce->valor_total, 2, ',', '.') }}</div>

    @foreach($pagamentos as $pag)
        <div>
            Pagto {{ $pag['t_pag'] ?? '' }}:
            R$ {{ number_format($pag['v_pag'] ?? 0, 2, ',', '.') }}
            @if(!empty($pag['v_troco']))
                — Troco R$ {{ number_format($pag['v_troco'], 2, ',', '.') }}
            @endif
        </div>
    @endforeach

    <div class="line"></div>
    <div class="center chave">Chave de Acesso<br>{{ $nfce->chave }}</div>

    @if($qrDataUri)
        <div class="center" style="margin-top: 8px;">
            <img class="qr" src="{{ $qrDataUri }}" alt="QR Code NFC-e">
        </div>
    @endif

    @if($nfce->ambiente == 2)
        <div class="center bold" style="margin-top: 8px;">NF-E EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL</div>
    @endif
</body>
</html>
