<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Fiscal #{{ $nota->numero ?? $nota->id }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #333; line-height: 1.3; }
        .container { width: 100%; margin: 0 auto; }

        /* Helpers */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .mb-2 { margin-bottom: 5px; }

        /* Estrutura de Caixas */
        .box { border: 1px solid #999; padding: 5px; margin-bottom: 5px; position: relative; }
        .box-header {
            background-color: #eee;
            border-bottom: 1px solid #999;
            margin: -5px -5px 5px -5px;
            padding: 3px 5px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
        }

        /* Tabelas */
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px; text-align: left; font-size: 10px; }
        th { background-color: #f9f9f9; font-weight: bold; text-transform: uppercase; }

        /* Marca D'água para Rascunho/Erro */
        .watermark {
            position: fixed;
            top: 35%;
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

        /* Cabeçalho NFS-e */
        .header-table { width: 100%; border: 1px solid #999; margin-bottom: 5px; }
        .header-logo { width: 100px; padding: 5px; text-align: center; border-right: 1px solid #999; }
        .header-info { padding: 5px; text-align: center; }
        .header-side { width: 140px; background-color: #eee; border-left: 1px solid #999; text-align: center; padding: 5px; }
    </style>
</head>
<body>

@if($nota->status !== 'autorizada')
    <div class="watermark">
        SEM VALOR FISCAL<br>
        <span style="font-size: 20px">({{ strtoupper($nota->status) }})</span>
    </div>
@endif

<div class="container">

    <table class="header-table">
        <tr>
            <td class="header-logo">
                {{-- Coloque sua logo em public/img/logo.png --}}
                <img src="{{ public_path('img/logo.png') }}" style="max-width: 80px; max-height: 60px;" alt="Logo">
            </td>
            <td class="header-info">
                <div style="font-size: 14px; font-weight: bold;">NOTA FISCAL DE SERVIÇOS ELETRÔNICA - NFS-e</div>
                <div style="font-size: 10px; margin-top: 5px; color: #555;">
                    Emissão: <strong>{{ $nota->data_emissao->format('d/m/Y H:i:s') }}</strong><br>
                    Competência: <strong>{{ $nota->competencia->format('d/m/Y') }}</strong><br>
                    Código de Verificação: <strong>{{ $nota->codigo_verificacao ?? 'PENDENTE' }}</strong>
                </div>
            </td>
            <td class="header-side">
                <div style="font-size: 9px; margin-bottom: 5px;">NÚMERO DA NOTA</div>
                <div style="font-size: 16px; font-weight: bold;">{{ $nota->numero ?? 'PROVISÓRIA' }}</div>
                <div style="font-size: 9px; margin-top: 5px;">Série: {{ $nota->serie }}</div>
            </td>
        </tr>
    </table>

    <div class="box">
        <div class="box-header">Prestador de Serviços</div>
        <table style="width: 100%; border: none;">
            <tr style="border: none;">
                <td style="border: none; width: 65%;">
                    <div style="font-size: 12px; font-weight: bold;">{{ $emitente->razao_social }}</div>
                    <div>CNPJ: {{ $emitente->cnpj }} &nbsp;|&nbsp; IM: {{ $emitente->inscricao_municipal }}</div>
                    <div>{{ $emitente->endereco }}, {{ $emitente->numero }} {{ $emitente->complemento }}</div>
                    <div>{{ $emitente->bairro }} - {{ $emitente->cidade }}/{{ $emitente->uf }}</div>
                </td>
                <td style="border: none; vertical-align: top; text-align: right;">
                    @if($emitente->email) <div>{{ $emitente->email }}</div> @endif
                    @if($emitente->telefone) <div>Tel: {{ $emitente->telefone }}</div> @endif
                    <div style="margin-top: 5px; font-weight: bold;">{{ $emitente->regime_tributario }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="box-header">Tomador de Serviços</div>
        <div style="padding: 2px;">
            <span class="font-bold uppercase">{{ $tomador->razao_social }}</span><br>
            CNPJ/CPF: {{ $tomador->documento }} <br>
            Endereço: {{ $tomador->endereco }}, {{ $tomador->numero }} {{ $tomador->complemento }} - {{ $tomador->bairro }}<br>
            Município: {{ $tomador->cidade }}/{{ $tomador->uf }} &nbsp;|&nbsp; CEP: {{ $tomador->cep }}<br>
            @if($tomador->email) E-mail: {{ $tomador->email }} @endif
        </div>
    </div>

    <div class="box">
        <div class="box-header">Discriminação dos Serviços</div>
        <div style="min-height: 120px; padding: 5px; white-space: pre-wrap; font-size: 11px;">{{ $servico->discriminacao }}</div>

        <div style="border-top: 1px dashed #ccc; margin-top: 10px; padding-top: 5px; font-size: 9px; color: #555;">
            Código do Serviço (Municipal): <strong>{{ $servico->item_lista_servico }}</strong> &nbsp;|&nbsp;
            Código NBS: <strong>{{ $servico->codigo_nbs }}</strong>
        </div>
    </div>

    <div class="box">
        <div class="box-header">Detalhamento de Valores</div>
        <table style="width: 100%;">
            <thead>
            <tr>
                <th class="text-right">Valor do Serviço</th>
                <th class="text-right">Deduções</th>
                <th class="text-right">Base de Cálculo</th>
                <th class="text-right">Alíquota ISS</th>
                <th class="text-right">Valor do ISS</th>
                <th class="text-right">Crédito</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="text-right">R$ {{ number_format($servico->valor_servico, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($servico->valor_deducoes, 2, ',', '.') }}</td>
                <td class="text-right">R$ {{ number_format($servico->valor_servico, 2, ',', '.') }}</td>
                <td class="text-right">{{ number_format($servico->aliquota_iss, 2, ',', '.') }}%</td>
                <td class="text-right">R$ {{ number_format($servico->valor_iss, 2, ',', '.') }}</td>
                <td class="text-right">R$ 0,00</td>
            </tr>
            </tbody>
        </table>

        <table style="width: 100%; margin-top: 5px;">
            <thead>
            <tr>
                <th class="text-right">PIS</th>
                <th class="text-right">COFINS</th>
                <th class="text-right">INSS</th>
                <th class="text-right">IR</th>
                <th class="text-right">CSLL</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td class="text-right">R$ 0,00</td>
                <td class="text-right">R$ 0,00</td>
                <td class="text-right">R$ 0,00</td>
                <td class="text-right">R$ 0,00</td>
                <td class="text-right">R$ 0,00</td>
            </tr>
            </tbody>
        </table>

        <div style="margin-top: 10px; text-align: right; background-color: #eee; padding: 5px; border: 1px solid #ccc;">
            <span style="font-size: 10px; font-weight: bold; margin-right: 10px;">VALOR LÍQUIDO DA NOTA:</span>
            <span style="font-size: 14px; font-weight: bold;">R$ {{ number_format($servico->valor_liquido, 2, ',', '.') }}</span>
        </div>
    </div>

    @if($servico->iss_retido == 1)
        <div style="border: 1px solid #d32f2f; background-color: #ffebee; color: #b71c1c; padding: 5px; font-weight: bold; text-align: center; font-size: 10px; margin-bottom: 5px;">
            ISS RETIDO PELO TOMADOR - O tomador do serviço é o responsável pelo recolhimento do ISS.
        </div>
    @endif

    <div class="box">
        <div class="box-header">Outras Informações</div>
        <div style="font-size: 9px; padding: 5px;">
            {{ $outras_informacoes }} <br>
            Prestador optante pelo Simples Nacional. Documento emitido por ME ou EPP. <br>
            Não gera direito a crédito fiscal de IPI.
        </div>
    </div>

    @if($qrCodeBase64 && $nota->chave)
        <div style="margin-top: 15px; text-align: center;">
            <img src="{{ $qrCodeBase64 }}" style="width: 90px; height: 90px;">
            <div style="font-size: 9px; margin-top: 5px;">
                Chave de Acesso:<br>
                <strong>{{ $nota->chave }}</strong>
            </div>
        </div>
    @endif

    <div style="margin-top: 20px; font-size: 8px; color: #888; text-align: center;">
        Gerado pelo sistema <strong>G2M Fiscal</strong>
    </div>

</div>
</body>
</html>
