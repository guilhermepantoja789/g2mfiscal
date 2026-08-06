# NFS-e Nacional — Reforma Tributária (IBS/CBS)

Documentação técnica local para a adequação do emissor DPS → SEFIN.

## Fontes oficiais (versionadas)

| Artefato | Versão no repo | Caminho |
|----------|----------------|---------|
| XSD DPS/NFS-e | 1.01 (2026-02-09) | `xsd/Schemas/1.01/` |
| Anexo VII IndOp | V1.00.00 | `anexos/AnexoVII-IndOp_IBSCBS_V1.00.00.xlsx` |
| Anexo VIII correlação | V1.01.00 | `anexos/AnexoVIII-CorrelacaoItemNBSIndOpCClassTrib_IBSCBS_V1.01.00.xlsx` |
| NBS 2.0 (MDIC) | CSV oficial | `anexos/nbs2-0-mdic.csv` |
| ClassTrib | IT 2025.002 (portal DF-e SVRS) | `database/seeders/data/class_tribs.csv` |

CSVs de seed: `database/seeders/data/` (`ind_ops.csv`, `class_tribs.csv`, `nbs_codes.csv`, `nbs_correlacoes.csv`, `lista_servicos.csv`).

Portal: https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/rtc/rtc

## Produção — comandos

```bash
php artisan migrate --force
php artisan db:seed --class=TributacaoNacionalSeeder --force
php artisan nfse:seed-ibscbs
# opcional (defaults IBS nos serviços; NÃO preenche codigo_nbs):
# php artisan nfse:seed-ibscbs --backfill
```

O deploy CI já executa `TributacaoNacionalSeeder` + `nfse:seed-ibscbs` após `migrate`.

## Contagens esperadas (seed)

| Tabela | ~linhas |
|--------|---------|
| `ind_ops` | 26 |
| `class_tribs` | 164 (com `destaque` Manaus/TI/ZFM) |
| `nbs_codes` | 920 (folhas 9 dígitos) |
| `nbs_correlacoes` | ~980 (Anexo VIII; escopo `ti_manaus` para LC 1.0x) |

## `cNBS` (obrigatório com IBSCBS)

Caminho: `DPS/infDPS/serv/cServ/cNBS` — `TSCodNBS` = **9 dígitos**.

Não confundir com `cTribNac` (LC 116, 6 dígitos). Sem `cNBS`, a SEFIN rejeita DPS com grupo `IBSCBS`.

Anexo VIII é **orientação UX** (wizard de serviço) — não é regra de validação SEFIN.

## Defaults Manaus / TI

| Campo | Default típico (remoto) |
|-------|-------------------------|
| `cTribMun` | `100` (prática Manaus; revalidar na SEFIN) |
| `cIndOp` | `100301` (ou `100501` para licença 01.05) |
| `CST` / `cClassTrib` | `000` / `000001` |
| NBS 01.01 | `115021000` |
| NBS 01.06 | `115011000` |
| NBS 01.07 suporte | `115013000` |

## Calendário (NFS-e)

- Portal NT 009 citou previsão 03/08/2026; Conta Azul / Ato Conjunto RFB/CGIBS nº 4: **Grupo 1 → 01/10/2026**, **Grupo 2 → 01/12/2026**; Simples/MEI campos IBS/CBS em **2027**.
- Layout API atual: NT004 + `tpRetPisCofins` (NT007). IndOp V1.00 até NT009 entrar em produção.

## Wizard na UI

- Serviço create/edit: passos LC116 → NBS → IndOp → CST + `x-help-panel`
- Nota create/edit: checklist NBS do serviço + help-panel

## Checklist homologação

1. Empresa A1 + `NFSE_TP_AMB=2`
2. `php artisan nfse:seed-ibscbs`
3. Serviço com `codigo_nbs`, `c_ind_op`, `cst_ibscbs`, `c_class_trib`
4. Emitir B2B/B2C e confirmar `<cNBS>` + `<IBSCBS>` na DPS
