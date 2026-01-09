<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Fiscal #{{ $nota->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }

        /* Cabeçalho */
        .header-box { border: 1px solid #000; padding: 10px; margin-bottom: 10px; }
        .title { font-size: 16px; font-weight: bold; text-transform: uppercase; text-align: center; margin-bottom: 5px; }
        .subtitle { font-size: 10px; text-align: center; color: #666; }

        /* Dados */
        .box { border: 1px solid #ccc; padding: 10px; margin-bottom: 10px; }
        .box-title { font-size: 10px; font-weight: bold; background-color: #eee; padding: 5px; margin: -10px -10px 10px -10px; border-bottom: 1px solid #ccc; }

        /* Tabela de Valores */
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f9f9f9; font-size: 10px; text-transform: uppercase; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }

        /* Marca D'água para Rascunho */
        .watermark {
            position: fixed;
            top: 40%;
            left: 10%;
            width: 80%;
            text-align: center;
            font-size: 60px;
            color: rgba(200, 0, 0, 0.15); /* Vermelho bem claro */
            transform: rotate(-45deg);
            z-index: -1000;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Status Colors */
        .status-badge { float: right; padding: 5px 10px; border-radius: 4px; color: white; font-weight: bold; font-size: 10px; }
        .bg-green { background-color: #16a34a; } /* Autorizada */
        .bg-yellow { background-color: #ca8a04; } /* Processando */
        .bg-gray { background-color: #999; } /* Rascunho */
        .bg-red { background-color: #dc2626; } /* Erro */

    </style>
</head>
<body>

@if($nota->status !== 'autorizada')
    <div class="watermark">
        SEM VALOR FISCAL<br>
        <span style="font-size: 30px">({{ strtoupper($nota->status) }})</span>
    </div>
@endif

<div class="container">

    <div class="header-box">
        <div class="title">
            @if($nota->status == 'autorizada')
                Nota Fiscal de Serviços Eletrônica (NFS-e)
            @else
                Recibo Provisório de Serviços (RPS)
            @endif
        </div>
        <div class="subtitle">
            Emitido em: {{ $nota->created_at->format('d/m/Y H:i:s') }} |
            Código de Verificação: {{ $nota->codigo_verificacao ?? 'PENDENTE' }}
        </div>
    </div>

    <div class="box">
        <div class="box-title">PRESTADOR DE SERVIÇOS</div>
        <strong>{{ $nota->empresa->razao_social }}</strong><br>
        CNPJ: {{ $nota->empresa->cnpj }}<br>
        {{ $nota->empresa->logradouro }}, {{ $nota->empresa->numero }} - {{ $nota->empresa->bairro }}<br>
        {{ $nota->empresa->uf }} (IBGE: {{ $nota->empresa->cod_ibge_mun }})
    </div>

    <div class="box">
        <div class="box-title">TOMADOR DE SERVIÇOS</div>
        <strong>{{ $nota->cliente->razao_social ?? $nota->tomador_nome }}</strong><br>
        CNPJ/CPF: {{ $nota->cliente->cnpj ?? $nota->tomador_cnpj }}<br>
        @if($nota->cliente)
            {{ $nota->cliente->logradouro }}, {{ $nota->cliente->numero }} - {{ $nota->cliente->bairro }}<br>
            {{ $nota->cliente->uf }}
        @else
            Endereço não informado no cadastro rápido.
        @endif
    </div>

    <div class="box">
        <div class="box-title">DISCRIMINAÇÃO DOS SERVIÇOS</div>
        <p style="white-space: pre-line;">{{ $nota->descricao }}</p>
        <br>
        <small style="color: #666">Código do Serviço (LC 116): {{ $nota->codigo_servico }}</small>
    </div>

    <div class="box">
        <div class="box-title">VALORES E IMPOSTOS</div>
        <table>
            <thead>
            <tr>
                <th>Valor do Serviço</th>
                <th>Alíquota ISS</th>
                <th>Valor ISS</th>
                <th>Outras Retenções</th>
                <th class="text-right">Valor Líquido</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>R$ {{ number_format($nota->valor_servico, 2, ',', '.') }}</td>
                <td>{{ number_format($nota->aliquota_iss, 2, ',', '.') }}%</td>
                <td>R$ {{ number_format($nota->valor_iss, 2, ',', '.') }}</td>
                <td>R$ 0,00</td>
                <td class="text-right font-bold">R$ {{ number_format($nota->valor_liquido, 2, ',', '.') }}</td>
            </tr>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 9px; color: #888; text-align: center; border-top: 1px dashed #ccc; padding-top: 10px;">
        @if($nota->status == 'autorizada')
            Documento emitido por ME ou EPP optante pelo Simples Nacional.<br>
            Não gera direito a crédito fiscal de IPI.
        @else
            <strong>ATENÇÃO: ESTE DOCUMENTO NÃO POSSUI VALOR FISCAL.</strong><br>
            Aguardando processamento e autorização pela Prefeitura Municipal.
        @endif
        <br><br>
        Gerado pelo sistema <strong>G2m Fiscal</strong>.
    </div>

</div>
</body>
</html>
