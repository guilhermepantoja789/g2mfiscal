<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DANFSe - NFS-e #{{ $nota->numero ?? $nota->id }}</title>
    <style>
        /* ============================================================
         * DANFSe Padrão Nacional — Espelho Local
         * ============================================================ */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 9px;
            color: #1a1a1a;
            line-height: 1.35;
            background: #fff;
        }
        .page {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 8px;
        }

        /* ---- Faixa de aviso (sempre visível) ---- */
        .aviso-espelho {
            background: #c62828;
            color: #fff;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        /* ---- Marca d'água extra para rascunho/erro ---- */
        .watermark {
            position: fixed;
            top: 38%;
            left: 8%;
            width: 84%;
            text-align: center;
            font-size: 56px;
            color: rgba(198, 40, 40, 0.12);
            transform: rotate(-40deg);
            z-index: -1000;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 6px;
        }

        /* ---- Cabeçalho ---- */
        .header {
            border: 1.5px solid #333;
            margin-bottom: 5px;
        }
        .header-row {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        .header-cell {
            display: table-cell;
            vertical-align: middle;
            padding: 6px 8px;
        }
        .header-logo {
            width: 90px;
            text-align: center;
            border-right: 1px solid #333;
        }
        .header-logo img {
            max-width: 70px;
            max-height: 55px;
        }
        .header-title {
            text-align: center;
        }
        .header-title h1 {
            font-size: 13px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .header-title .subtitle {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
        }
        .header-number {
            width: 130px;
            text-align: center;
            border-left: 1px solid #333;
            background: #f5f5f5;
        }
        .header-number .label {
            font-size: 7px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 2px;
        }
        .header-number .value {
            font-size: 16px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .header-number .serie {
            font-size: 8px;
            color: #555;
            margin-top: 2px;
        }

        /* ---- Seções (boxes) ---- */
        .section {
            border: 1px solid #999;
            margin-bottom: 4px;
        }
        .section-header {
            background: linear-gradient(to right, #e0e0e0, #f0f0f0);
            border-bottom: 1px solid #999;
            padding: 2px 6px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #333;
        }
        .section-body {
            padding: 5px 6px;
        }

        /* ---- Layout em grid (via table para DomPDF) ---- */
        .info-table {
            width: 100%;
            border-collapse: collapse;
        }
        .info-table td {
            border: none;
            padding: 1px 0;
            vertical-align: top;
        }
        .info-label {
            font-size: 7px;
            color: #777;
            text-transform: uppercase;
            display: block;
            margin-bottom: 0px;
        }
        .info-value {
            font-size: 9px;
            color: #1a1a1a;
        }
        .info-value-bold {
            font-size: 10px;
            font-weight: bold;
            color: #1a1a1a;
        }

        /* ---- Tabela de Valores ---- */
        .values-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .values-table th {
            background: #f5f5f5;
            border: 1px solid #bbb;
            padding: 3px 4px;
            font-size: 7px;
            text-transform: uppercase;
            text-align: center;
            font-weight: bold;
            color: #444;
        }
        .values-table td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            font-size: 9px;
            text-align: right;
        }

        /* ---- Valor líquido ---- */
        .total-box {
            background: #e8f5e9;
            border: 1.5px solid #4caf50;
            padding: 5px 8px;
            text-align: right;
            margin-top: 4px;
        }
        .total-label {
            font-size: 9px;
            font-weight: bold;
            color: #333;
            display: inline;
            margin-right: 15px;
        }
        .total-value {
            font-size: 14px;
            font-weight: bold;
            color: #2e7d32;
        }

        /* ---- ISS Retido ---- */
        .iss-retido-box {
            border: 1.5px solid #d32f2f;
            background: #ffebee;
            color: #b71c1c;
            padding: 4px 8px;
            font-weight: bold;
            text-align: center;
            font-size: 9px;
            margin-top: 4px;
        }

        /* ---- Discriminação ---- */
        .discriminacao-text {
            min-height: 80px;
            white-space: pre-wrap;
            font-size: 9px;
            line-height: 1.4;
            padding: 4px 0;
        }

        /* ---- Tributos ---- */
        .tributos-table {
            width: 100%;
            border-collapse: collapse;
        }
        .tributos-table th, .tributos-table td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            font-size: 8px;
            text-align: center;
        }
        .tributos-table th {
            background: #f5f5f5;
            font-size: 7px;
            text-transform: uppercase;
            font-weight: bold;
            color: #444;
        }

        /* ---- QR Code / Rodapé ---- */
        .footer-qr {
            text-align: center;
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #ccc;
        }
        .footer-qr img {
            width: 80px;
            height: 80px;
        }
        .chave-acesso {
            font-size: 8px;
            margin-top: 3px;
            color: #333;
            word-break: break-all;
        }
        .footer-system {
            font-size: 7px;
            color: #aaa;
            text-align: center;
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #eee;
        }

        /* ---- Helpers ---- */
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .text-left { text-align: left; }
    </style>
</head>
<body>

<div class="page">

    {{-- ========== FAIXA DE AVISO (sempre visível) ========== --}}
    <div class="aviso-espelho">
        ⚠ ESPELHO — DOCUMENTO SEM VALOR FISCAL ⚠
    </div>

    {{-- ========== MARCA D'ÁGUA extra para rascunho/erro ========== --}}
    @if($nota->status !== 'autorizada')
        <div class="watermark">
            SEM VALOR FISCAL<br>
            <span style="font-size: 18px;">({{ strtoupper($nota->status) }})</span>
        </div>
    @endif

    {{-- ========== CABEÇALHO ========== --}}
    <div class="header">
        <div class="header-row">
            <div class="header-cell header-logo">
                @if(file_exists(public_path('img/logo.png')))
                    <img src="{{ public_path('img/logo.png') }}" alt="Logo">
                @else
                    <div style="font-size: 8px; color: #999;">LOGO</div>
                @endif
            </div>
            <div class="header-cell header-title">
                <h1>Nota Fiscal de Serviços Eletrônica - NFS-e</h1>
                <div class="subtitle">
                    DANFSe — Documento Auxiliar da NFS-e &bull; Padrão Nacional
                </div>
            </div>
            <div class="header-cell header-number">
                <div class="label">Número da NFS-e</div>
                <div class="value">{{ $nota->numero ?? '—' }}</div>
                <div class="serie">Série: {{ $nota->serie }}</div>
            </div>
        </div>
    </div>

    {{-- ========== DADOS DA NOTA ========== --}}
    <div class="section">
        <div class="section-body" style="padding: 3px 6px;">
            <table class="info-table">
                <tr>
                    <td style="width: 25%;">
                        <span class="info-label">Data/Hora de Emissão</span>
                        <span class="info-value">
                            @if($nota->data_emissao instanceof \DateTime || $nota->data_emissao instanceof \Carbon\Carbon)
                                {{ $nota->data_emissao->format('d/m/Y H:i') }}
                            @else
                                {{ $nota->data_emissao ?? '—' }}
                            @endif
                        </span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">Competência</span>
                        <span class="info-value">
                            @if($nota->competencia instanceof \DateTime || $nota->competencia instanceof \Carbon\Carbon)
                                {{ $nota->competencia->format('m/Y') }}
                            @else
                                {{ $nota->competencia ?? '—' }}
                            @endif
                        </span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">Local da Prestação</span>
                        <span class="info-value">{{ $nota->local_prestacao }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">Código de Verificação</span>
                        <span class="info-value" style="font-weight:bold;">{{ $nota->codigo_verificacao ?? '—' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ========== PRESTADOR ========== --}}
    <div class="section">
        <div class="section-header">Prestador de Serviços</div>
        <div class="section-body">
            <table class="info-table">
                <tr>
                    <td colspan="3">
                        <span class="info-label">Razão Social / Nome</span>
                        <span class="info-value-bold">{{ $emitente->razao_social }}</span>
                        @if($emitente->nome_fantasia)
                            <span class="info-value" style="color:#666;"> ({{ $emitente->nome_fantasia }})</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="width: 35%;">
                        <span class="info-label">CNPJ</span>
                        <span class="info-value">{{ preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $emitente->cnpj) }}</span>
                    </td>
                    <td style="width: 30%;">
                        <span class="info-label">Inscrição Municipal</span>
                        <span class="info-value">{{ $emitente->inscricao_municipal ?? '—' }}</span>
                    </td>
                    <td style="width: 35%;">
                        <span class="info-label">Regime Tributário</span>
                        <span class="info-value">{{ $emitente->regime_tributario }}</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="info-label">Endereço</span>
                        <span class="info-value">
                            {{ $emitente->endereco }}, {{ $emitente->numero }}
                            {{ $emitente->complemento ? '- ' . $emitente->complemento : '' }}
                            — {{ $emitente->bairro }}
                        </span>
                    </td>
                    <td>
                        <span class="info-label">Município / UF</span>
                        <span class="info-value">{{ $emitente->cidade }}/{{ $emitente->uf }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Telefone</span>
                        <span class="info-value">{{ $emitente->telefone ?? '—' }}</span>
                    </td>
                    <td colspan="2">
                        <span class="info-label">E-mail</span>
                        <span class="info-value">{{ $emitente->email ?? '—' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ========== TOMADOR ========== --}}
    <div class="section">
        <div class="section-header">Tomador de Serviços</div>
        <div class="section-body">
            <table class="info-table">
                <tr>
                    <td colspan="3">
                        <span class="info-label">Razão Social / Nome</span>
                        <span class="info-value-bold">{{ $tomador->razao_social }}</span>
                    </td>
                </tr>
                <tr>
                    <td style="width: 35%;">
                        <span class="info-label">CNPJ / CPF</span>
                        <span class="info-value">
                            @php
                                $doc = $tomador->documento;
                                if (strlen($doc) == 14) {
                                    $doc = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
                                } elseif (strlen($doc) == 11) {
                                    $doc = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
                                }
                            @endphp
                            {{ $doc }}
                        </span>
                    </td>
                    <td style="width: 30%;">
                        <span class="info-label">Inscrição Municipal</span>
                        <span class="info-value">{{ $tomador->inscricao_municipal ?: '—' }}</span>
                    </td>
                    <td style="width: 35%;">
                        <span class="info-label">E-mail</span>
                        <span class="info-value">{{ $tomador->email ?: '—' }}</span>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <span class="info-label">Endereço</span>
                        <span class="info-value">
                            {{ $tomador->endereco }}{{ $tomador->numero ? ', ' . $tomador->numero : '' }}
                            {{ $tomador->complemento ? '- ' . $tomador->complemento : '' }}
                            {{ $tomador->bairro ? '— ' . $tomador->bairro : '' }}
                        </span>
                    </td>
                    <td>
                        <span class="info-label">Município / UF</span>
                        <span class="info-value">
                            {{ $tomador->cidade ?: '—' }}{{ $tomador->uf ? '/' . $tomador->uf : '' }}
                            {{ $tomador->cep ? '— CEP: ' . preg_replace('/(\d{5})(\d{3})/', '$1-$2', $tomador->cep) : '' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    {{-- ========== DISCRIMINAÇÃO DOS SERVIÇOS ========== --}}
    <div class="section">
        <div class="section-header">Discriminação dos Serviços</div>
        <div class="section-body">
            {{-- Código + Nome do Serviço --}}
            <table class="info-table" style="margin-bottom: 4px;">
                <tr>
                    <td style="width: 35%;">
                        <span class="info-label">Código do Serviço (LC 116)</span>
                        <span class="info-value">{{ $servico->item_lista_servico ?: '—' }}</span>
                    </td>
                    <td style="width: 35%;">
                        <span class="info-label">Código NBS</span>
                        <span class="info-value">{{ $servico->codigo_nbs ?: '—' }}</span>
                    </td>
                    <td style="width: 30%;">
                        <span class="info-label">Situação Tributária ISS</span>
                        <span class="info-value">{{ $servico->trib_issqn ?? '—' }}</span>
                    </td>
                </tr>
            </table>

            {{-- Descrição livre --}}
            <div class="discriminacao-text">{{ $servico->discriminacao }}</div>
        </div>
    </div>

    {{-- ========== VALORES DO SERVIÇO ========== --}}
    <div class="section">
        <div class="section-header">Valores da Nota Fiscal</div>
        <div class="section-body" style="padding: 4px 6px;">
            <table class="values-table">
                <thead>
                <tr>
                    <th style="width: 20%;">Valor do Serviço (R$)</th>
                    <th style="width: 16%;">Deduções (R$)</th>
                    <th style="width: 20%;">Base de Cálculo (R$)</th>
                    <th style="width: 14%;">Alíquota ISS</th>
                    <th style="width: 16%;">Valor ISS (R$)</th>
                    <th style="width: 14%;">ISS Retido?</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ number_format($servico->valor_servico, 2, ',', '.') }}</td>
                    <td>{{ number_format($servico->valor_deducoes, 2, ',', '.') }}</td>
                    <td>{{ number_format($servico->valor_servico - $servico->valor_deducoes, 2, ',', '.') }}</td>
                    <td class="text-center">{{ number_format($servico->aliquota_iss, 2, ',', '.') }}%</td>
                    <td>{{ number_format($servico->valor_iss, 2, ',', '.') }}</td>
                    <td class="text-center">{{ $servico->iss_retido ? 'SIM' : 'NÃO' }}</td>
                </tr>
                </tbody>
            </table>

            {{-- Tributos Aproximados (Lei da Transparência) --}}
            @if(isset($tributos) && $tributos->v_total > 0)
            <table class="tributos-table" style="margin-top: 5px;">
                <thead>
                <tr>
                    <th colspan="4" style="text-align: left; padding-left: 4px;">
                        Valor Aprox. dos Tributos — Lei nº 12.741/2012 (De Olho no Imposto)
                    </th>
                </tr>
                <tr>
                    <th>Tributo</th>
                    <th>Federal</th>
                    <th>Estadual</th>
                    <th>Municipal</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td style="font-weight: bold; text-align: left;">Alíquota (%)</td>
                    <td>{{ number_format($tributos->p_fed, 2, ',', '.') }}%</td>
                    <td>{{ number_format($tributos->p_est, 2, ',', '.') }}%</td>
                    <td>{{ number_format($tributos->p_mun, 2, ',', '.') }}%</td>
                </tr>
                <tr>
                    <td style="font-weight: bold; text-align: left;">Valor (R$)</td>
                    <td>{{ number_format($tributos->v_fed, 2, ',', '.') }}</td>
                    <td>{{ number_format($tributos->v_est, 2, ',', '.') }}</td>
                    <td>{{ number_format($tributos->v_mun, 2, ',', '.') }}</td>
                </tr>
                <tr style="background: #f5f5f5; font-weight: bold;">
                    <td style="text-align: left;">Total</td>
                    <td colspan="3">R$ {{ number_format($tributos->v_total, 2, ',', '.') }}</td>
                </tr>
                </tbody>
            </table>
            @endif

            {{-- ISS Retido --}}
            @if($servico->iss_retido)
                <div class="iss-retido-box">
                    ⚠ ISS RETIDO PELO TOMADOR — O tomador do serviço é responsável pelo recolhimento do ISS no valor de R$ {{ number_format($servico->valor_iss, 2, ',', '.') }}
                </div>
            @endif

            {{-- Valor Líquido --}}
            <div class="total-box">
                <span class="total-label">VALOR LÍQUIDO DA NFS-e:</span>
                <span class="total-value">R$ {{ number_format($servico->valor_liquido, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- ========== OUTRAS INFORMAÇÕES ========== --}}
    <div class="section">
        <div class="section-header">Outras Informações</div>
        <div class="section-body" style="font-size: 8px; color: #555; padding: 4px 6px;">
            {{ $outras_informacoes }}<br>
            Não gera direito a crédito fiscal de IPI.
        </div>
    </div>

    {{-- ========== QR CODE + CHAVE DE ACESSO ========== --}}
    @if($qrCodeBase64 && $nota->chave && $nota->chave !== 'PENDENTE')
        <div class="footer-qr">
            <img src="{{ $qrCodeBase64 }}" alt="QR Code">
            <div class="chave-acesso">
                <span class="info-label" style="display: inline;">Chave de Acesso:</span>
                <strong>{{ $nota->chave }}</strong>
            </div>
        </div>
    @endif

    {{-- ========== RODAPÉ ========== --}}
    <div class="footer-system">
        Gerado pelo sistema <strong>G2M Fiscal</strong> &bull; Este PDF é um <strong>espelho interno</strong> e <strong>não</strong> substitui a DANFSe oficial emitida pelo Portal Nacional da NFS-e.
    </div>

</div>

</body>
</html>
