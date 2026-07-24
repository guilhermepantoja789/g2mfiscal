# NFS-e Nacional — Reforma Tributária (IBS/CBS)

Documentação técnica local para a adequação do emissor DPS → SEFIN.

## Fontes oficiais

- Portal: https://www.gov.br/nfse/pt-br/biblioteca/documentacao-tecnica/documentacao-atual
- XSD: `nfse-esquemas-xsd-v1.01-20260209.zip` → pasta `xsd/Schemas/1.01/`
- Anexo C (IndOp): `anexo-c-indop-ibscbs-v1.01-20260122.xlsx`
- Anexo I (layout DPS/NFS-e): `anexo_i-sefin_adn-dps_nfse-snnfse-v1-01-20260209.xlsx`
- NT 004 (RTC IBSCBS): layout DPS / regras de negócio

## DPS v1.01 — ordem do grupo `IBSCBS` (contribuinte)

Caminho: `DPS/infDPS/IBSCBS` (após `valores` ISS).

1. `finNFSe` (obrigatório; XSD 1.01 só aceita `0` = regular)
2. `indFinal` (0/1; opcional no XSD)
3. `cIndOp` (6 dígitos — Anexo C / seed `ind_ops`)
4. `tpOper` (opcional)
5. `gRefNFSe` (opcional)
6. `tpEnteGov` (opcional)
7. `indDest` (**obrigatório**: `0` = tomador=destinatário; `1` exige grupo `dest`)
8. `dest` (condicional)
9. `imovel` (condicional)
10. `valores/trib/gIBSCBS`:
    - `CST` (3)
    - `cClassTrib` (6)
    - `cCredPres` / `gTribRegular` / `gDif` (opcionais)

Valores de IBS/CBS (alíquotas, BC, totais) são calculados pela SEFIN na NFS-e gerada — não montar o grupo `NFSe/infNFSe/IBSCBS` na DPS.

## Defaults g2mfiscal (Manaus / serviços típicos)

| Campo | Default |
|-------|---------|
| `finNFSe` | `0` |
| `indFinal` | CPF → `1`; CNPJ → `0` |
| `indDest` | `0` |
| `cIndOp` | `100301` (demais serviços / distância) |
| `CST` / `cClassTrib` | `000` / `000001` (tributação integral) |

Obrigatoriedade operacional: a partir de **03/08/2026** (Ato Conjunto RFB/CGIBS nº 1 + RIBS).

## Operação

```bash
php artisan migrate
php artisan nfse:seed-ibscbs --backfill
```

## Checklist homologação (Produção Restrita SEFIN)

1. Empresa com certificado A1 e `NFSE_TP_AMB=2`
2. Serviço com `c_ind_op`, `cst_ibscbs`, `c_class_trib` preenchidos
3. Emitir nota B2B (CNPJ) → XML DPS `versao="1.01"` com `<IBSCBS>` e `indFinal=0`
4. Emitir nota B2C (CPF) → `indFinal=1`
5. Confirmar autorização e presença do grupo IBSCBS na NFS-e retornada
