<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DANFSe - NFS-e #{{ $nota->numero ?? $nota->id }}</title>
    <style>
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

        .aviso-faixa {
            background: #c62828;
            color: #fff;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding: 5px 0;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

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
        .header-brand {
            width: 110px;
            text-align: center;
            border-right: 1px solid #333;
            background: #f7f7f7;
        }
        .header-brand .sigla {
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        .header-brand .sigla-sub {
            font-size: 7px;
            color: #555;
            text-transform: uppercase;
            margin-top: 2px;
        }
        .header-title {
            text-align: center;
        }
        .header-title h1 {
            font-size: 12px;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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

        .section {
            border: 1px solid #999;
            margin-bottom: 4px;
        }
        .section-header {
            background: #e8e8e8;
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

        .discriminacao-text {
            min-height: 60px;
            white-space: pre-wrap;
            font-size: 9px;
            line-height: 1.4;
            padding: 4px 0;
        }

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

        .footer-qr {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #ccc;
        }
        .footer-qr-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-qr-table td {
            vertical-align: middle;
        }
        .footer-qr img {
            width: 78px;
            height: 78px;
        }
        .chave-acesso {
            font-size: 9px;
            color: #333;
            letter-spacing: 0.5px;
        }
        .auth-text {
            font-size: 7px;
            color: #555;
            margin-top: 4px;
        }
        .footer-system {
            font-size: 7px;
            color: #888;
            text-align: center;
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #eee;
        }

        .text-center { text-align: center; }
    </style>
</head>
<body>

<div class="page">

    @if(($aviso ?? null) === 'espelho')
        <div class="aviso-faixa">ESPELHO — DOCUMENTO SEM VALOR FISCAL</div>
    @elseif(($aviso ?? null) === 'homolog')
        <div class="aviso-faixa">NFS-e SEM VALIDADE JURÍDICA</div>
    @endif

    @if($nota->status !== 'autorizada')
        <div class="watermark">
            SEM VALOR FISCAL<br>
            <span style="font-size: 18px;">({{ strtoupper($nota->status) }})</span>
        </div>
    @endif

    <div class="header">
        <div class="header-row">
            <div class="header-cell header-brand">
                <div class="sigla">DANFSe</div>
                <div class="sigla-sub">Padrão Nacional</div>
            </div>
            <div class="header-cell header-title">
                <h1>Nota Fiscal de Serviços Eletrônica</h1>
                <div class="subtitle">Documento Auxiliar da NFS-e</div>
            </div>
            <div class="header-cell header-number">
                <div class="label">Número da NFS-e</div>
                <div class="value">{{ $nota->numero ?? '—' }}</div>
                <div class="serie">Série: {{ $nota->serie }}</div>
            </div>
        </div>
    </div>

    <div class="section">
        <div class="section-header">Identificação da NFS-e</div>
        <div class="section-body" style="padding: 3px 6px;">
            <table class="info-table">
                <tr>
                    <td style="width: 25%;">
                        <span class="info-label">Data/Hora de Emissão</span>
                        <span class="info-value">
                            @if($nota->data_emissao instanceof \DateTimeInterface)
                                {{ $nota->data_emissao->format('d/m/Y H:i') }}
                            @else
                                {{ $nota->data_emissao ?? '—' }}
                            @endif
                        </span>
                    </td>
                    <td style="width: 20%;">
                        <span class="info-label">Competência</span>
                        <span class="info-value">
                            @if($nota->competencia instanceof \DateTimeInterface)
                                {{ $nota->competencia->format('m/Y') }}
                            @else
                                {{ $nota->competencia ?? '—' }}
                            @endif
                        </span>
                    </td>
                    <td style="width: 20%;">
                        <span class="info-label">Nº DPS / Série</span>
                        <span class="info-value">{{ $nota->numero_dps ?? '—' }} / {{ $nota->serie }}</span>
                    </td>
                    <td style="width: 17%;">
                        <span class="info-label">Situação</span>
                        <span class="info-value">{{ $nota->situacao ?? $nota->status }}</span>
                    </td>
                    <td style="width: 18%;">
                        <span class="info-label">Finalidade</span>
                        <span class="info-value">{{ $nota->finalidade ?? 'Regular' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">Local da Prestação</span>
                        <span class="info-value">{{ $nota->local_prestacao }}</span>
                    </td>
                    <td>
                        <span class="info-label">Código de Verificação</span>
                        <span class="info-value" style="font-weight:bold;">{{ $nota->codigo_verificacao ?? '—' }}</span>
                    </td>
                    <td colspan="3">
                        <span class="info-label">Chave de Acesso</span>
                        <span class="info-value-bold">{{ $nota->chave_formatada ?? $nota->chave }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

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
                        <span class="info-label">Município / UF / IBGE</span>
                        <span class="info-value">{{ $emitente->cidade }}/{{ $emitente->uf }} {{ $emitente->cod_ibge ? '— '.$emitente->cod_ibge : '' }}</span>
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="info-label">CEP</span>
                        <span class="info-value">{{ $emitente->cep ? preg_replace('/(\d{5})(\d{3})/', '$1-$2', preg_replace('/\D/', '', $emitente->cep)) : '—' }}</span>
                    </td>
                    <td>
                        <span class="info-label">Telefone</span>
                        <span class="info-value">{{ $emitente->telefone ?? '—' }}</span>
                    </td>
                    <td>
                        <span class="info-label">E-mail</span>
                        <span class="info-value">{{ $emitente->email ?? '—' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

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
                                $doc = preg_replace('/\D/', '', (string) $tomador->documento);
                                if (strlen($doc) == 14) {
                                    $doc = preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $doc);
                                } elseif (strlen($doc) == 11) {
                                    $doc = preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $doc);
                                }
                            @endphp
                            {{ $doc ?: '—' }}
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
                            {{ $tomador->cep ? '— CEP: ' . preg_replace('/(\d{5})(\d{3})/', '$1-$2', preg_replace('/\D/', '', $tomador->cep)) : '' }}
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <div class="section-header">Discriminação dos Serviços</div>
        <div class="section-body">
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
            <div class="discriminacao-text">{{ $servico->discriminacao }}</div>
        </div>
    </div>

    <div class="section">
        <div class="section-header">Tributação Municipal (ISSQN)</div>
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

            @if($servico->iss_retido)
                <div class="iss-retido-box">
                    ISS RETIDO PELO TOMADOR — responsável pelo recolhimento de R$ {{ number_format($servico->valor_iss, 2, ',', '.') }}
                </div>
            @endif
        </div>
    </div>

    @if(!empty($ibscbs))
    <div class="section">
        <div class="section-header">Tributação IBS/CBS</div>
        <div class="section-body" style="padding: 3px 6px;">
            <table class="info-table">
                <tr>
                    <td style="width: 25%;">
                        <span class="info-label">CST</span>
                        <span class="info-value">{{ $ibscbs->cst ?: '—' }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">cClassTrib</span>
                        <span class="info-value">{{ $ibscbs->c_class_trib ?: '—' }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">cIndOp</span>
                        <span class="info-value">{{ $ibscbs->c_ind_op ?: '—' }}</span>
                    </td>
                    <td style="width: 25%;">
                        <span class="info-label">indDest / indFinal</span>
                        <span class="info-value">{{ $ibscbs->ind_dest ?? '—' }} / {{ $ibscbs->ind_final ?? '—' }}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    @endif

    <div class="section">
        <div class="section-header">Valor Total da NFS-e</div>
        <div class="section-body" style="padding: 4px 6px;">
            @if(isset($tributos) && $tributos->v_total > 0)
            <table class="tributos-table">
                <thead>
                <tr>
                    <th colspan="4" style="text-align: left; padding-left: 4px;">
                        Totais aproximados dos tributos — Lei nº 12.741/2012
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

            <div class="total-box">
                <span class="total-label">VALOR LÍQUIDO DA NFS-e:</span>
                <span class="total-value">R$ {{ number_format($servico->valor_liquido, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    @if(!empty($outras_informacoes))
    <div class="section">
        <div class="section-header">Informações Complementares</div>
        <div class="section-body" style="font-size: 8px; color: #555; padding: 4px 6px;">
            {{ $outras_informacoes }}
        </div>
    </div>
    @endif

    @if(!empty($qrCodeBase64) && $nota->chave && $nota->chave !== 'PENDENTE')
        <div class="footer-qr">
            <table class="footer-qr-table">
                <tr>
                    <td style="width: 90px;">
                        <img src="{{ $qrCodeBase64 }}" alt="QR Code">
                    </td>
                    <td>
                        <span class="info-label">Chave de Acesso</span>
                        <div class="chave-acesso"><strong>{{ $nota->chave_formatada ?? $nota->chave }}</strong></div>
                        <div class="auth-text">
                            A autenticidade desta NFS-e pode ser verificada pela leitura deste código QR
                            ou pela consulta da chave de acesso no portal nacional da NFS-e.
                            @if(!empty($urlConsulta))
                                <br>{{ $urlConsulta }}
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    @endif

    <div class="footer-system">
        @if(($aviso ?? null) === 'espelho')
            Documento auxiliar gerado internamente — sem valor fiscal.
        @elseif(($aviso ?? null) === 'homolog')
            Documento gerado em ambiente de homologação (produção restrita).
        @else
            Documento auxiliar gerado a partir do XML autorizado da NFS-e.
        @endif
    </div>

</div>

</body>
</html>
