# Plano — G2M Fiscal

Última atualização: **2026-07-24**

Fonte única de **o que o produto é hoje**, **gaps de robustez** e **próximos passos**. Guia técnico de emissão NFC-e: [`NFCE_EMISSAO.md`](NFCE_EMISSAO.md). Setup: [`README.md`](README.md).

---

## 1. O que é hoje

G2M Fiscal é um SaaS multi-empresa (Laravel 12 + Blade/Tailwind/Alpine) para **gestão fiscal, comercial, estoque e financeira**, centrado em `DocumentoComercial`.

```text
UI Documentos / PDV / Produtos / Estoque / Lançamentos / Contábil
        │
        ▼
 DocumentoComercial ──► DocumentoOrchestrator
        │                      │
        ├─ venda serviço ──────┼──► EmitirNotaFiscalJob / NfseNacionalService
        ├─ venda produto ──────┼──► EmitirNfceJob / FiscalEngine
        └─ compra XML 55 ──────┼──► NfeXmlImporter → estoque + contas a pagar
                               │
                    onFiscalAutorizado
                      → EstoqueService
                      → LancamentoFinanceiroService
                      → ContabilPostingService  (partidas dobradas 2B)
```

Telas avulsas de NFS-e (`/app/notas`) e NFC-e (`/app/nfces`) permanecem para legado e laboratório.

### Matriz de maturidade

| Frente | Status | Notas |
|--------|--------|--------|
| Multi-empresa, clientes, serviços, A1 | Pronto | OpenSSL 3 + legacy |
| NFS-e Nacional (emissão assíncrona) | Pronto | `nota_fiscais` + jobs + recovery |
| NFC-e AM — autorização / consulta / DANFE / lab | Pronto (homolog) | `App\Core\FiscalEngine\` |
| NFC-e AM — cancelamento / inutilização / contingência | Pronto (homolog) | Engine + UI |
| Core ERP — `DocumentoComercial` | Pronto | Orchestrator + UI |
| Produtos + Kardex | Pronto | `produtos`, `estoque_movimentacoes` |
| Lançamentos P/R gerenciais | Pronto | UI criar/editar/baixar/estornar/cancelar; ponte Asaas opcional |
| Import XML NF-e 55 (compra) | Pronto | `NfeXmlImporter` |
| `empresa_modulos` + middleware | Pronto | ERP / PDV / financeiro gerencial / contábil (opt-in) |
| Perfil `contador` + ACL básica | Pronto | Leitura fiscal; sem emissão/PDV/config |
| Admin de plataforma | Pronto | `is_platform_admin` via Artisan; vê todas empresas; `/app/admin/vinculos` |
| Admins de empresa imutáveis | Pronto | UI/equipe só operador/contador |
| Área Contábil (hub fiscal) | Pronto | Painel, livros por competência, export CSV/ZIP |
| Plano de contas + postagem 2B | Pronto | `ContabilPostingService` gera partidas; DRE/balanço gerencial |
| Formas de pagamento (flags + juros/parcelas) | Pronto | Sem gateway |
| PDV venda simples → NFC-e | Pronto | Sem abertura/fechamento de caixa |
| Cobranças / carteira Asaas | Opcional | `FEATURE_FINANCEIRO`; ponte a partir de P/R |
| NFC-e — go-live produção AM | Pendente | Checklist §4.2 |
| Recorrências → DocumentoComercial | Pendente | Hoje geram `NotaFiscal` direto |
| Escritório Contábil multi-cliente | Futuro | Portfolio além do pivot `empresa_user` |
| NF-e 55 de saída | Fora / backlog | Só entrada XML no v1 |
| Outras UFs / IBS-CBS / A3 | Fora do v1 | |

### Stack e arquivos-chave

- PHP 8.2+, Laravel 12, MySQL/Postgres (testes: SQLite in-memory)
- NFC-e: stack **nativa** (sem `sped-nfe` / Focus / ACBr); `sped-common` só para assinatura NFS-e

| Área | Caminho |
|------|---------|
| Orchestrator ERP | `app/Services/Erp/DocumentoOrchestrator.php` |
| Estoque / P&R | `EstoqueService.php`, `LancamentoFinanceiroService.php` |
| Contábil hub / posting | `app/Services/Contabil/*` |
| NFS-e | `app/Services/NfseNacionalService.php` |
| NFC-e engine / adapter | `app/Core/FiscalEngine/`, `RawNativeNfceIssuer.php` |

---

## 2. Gaps de robustez

Prioridade do plano: fechar inconsistências entre fiscal, estoque, financeiro e contábil antes de expandir superfície.

| Área | Gap |
|------|-----|
| Fiscal ↔ ERP | Cancel NFC-e → `onFiscalCancelado` estorna kardex, cancela P/R (aberto/pago) e marca documento; NFS-e ainda sem gancho |
| Financeiro | UI criar/editar/baixar/estornar; cancel via fiscal; ponte Asaas (FEATURE) gera `Cobranca` rascunho |
| Contábil | Hub com competência (`data_emissao`/`data_competencia`); plano + postagem 2B; DRE/balanço gerencial |
| Fiscal ops | Go-live produção AM aberto; CSC/idToken prod; `NFCE_SSL_VERIFY` + CA ICP-Brasil |

---

## 3. Próximos passos (ordem)

### 3.1 Integridade fiscal ↔ ERP (cancel / estorno)

- [x] Estornar movimentos de estoque do documento (`EstoqueService::estornarPorDocumento`)
- [x] Cancelar lançamentos financeiros abertos **e baixados** (política: cancela P/R; reembolso bancário fora do livro)
- [x] Atualizar status do `DocumentoComercial` (`onFiscalCancelado`)
- [x] Gancho no cancelamento NFC-e (`NfceController::cancelar`)
- [x] Testes de consistência cancel → estoque → P/R → documento
- [ ] Gancho equivalente no cancelamento NFS-e (quando existir fluxo de cancel)

### 3.2 NFC-e Amazonas — go-live produção

- [ ] CSC e idToken de **produção** (não os de homolog)
- [ ] Credenciamento NFC-e do CNPJ ativo na SEFAZ-AM
- [ ] Smoke produção com valor simbólico autorizado
- [ ] Cancelamento e inutilização validados em homolog; runbook de rejeições (cStat comuns)
- [ ] Contingência: emitir → imprimir DANFE → transmitir
- [ ] `NFCE_SSL_VERIFY=true` + CA ICP-Brasil em produção
- [ ] Alertas/`NfeStatusServico` (opcional: job periódico)

Detalhe de pipeline, cStat e portabilidade: [`NFCE_EMISSAO.md`](NFCE_EMISSAO.md).

### 3.3 Financeiro gerencial endurecido

- [x] Alinhar baixa/estorno ao ciclo fiscal (incl. cancel) — `baixar` / `estornar` / `cancelarDoDocumento`
- [x] UI mínima de lançamento manual (criar/editar/cancelar/estornar baixa)
- [x] Ponte Asaas — gerar `Cobranca` a partir de `LancamentoFinanceiro` quando `FEATURE_FINANCEIRO` (rascunho; ativação gateway permanece no fluxo de cobranças)

### 3.4 Contabilidade fase 2B

- [x] UI de plano de contas / mapeamentos fiscais → conta
- [x] Ativar `ContabilPostingService::fromDocumento` (débito/crédito) + `reverterDocumento`
- [x] Competência nos livros (`data_emissao` NFC-e / `data_competencia` documento; fallback `created_at`)
- [x] DRE / balanço gerencial a partir de `lancamentos_contabeis`
- [ ] Depois: entidade Escritório Contábil (portfolio multi-cliente) — futuro § matriz

### 3.5 Alinhamento legado → Documentos

- `ProcessarRecorrencias` criar `DocumentoComercial` (canal `nfse`) via orchestrator
- Migração opcional de NFS-e/NFC-e legadas → vínculo `documento_comercial_id`
- Compra manual (sem XML) mais completa na UI

### 3.6 Backlog não bloqueante

- NF-e modelo 55 de **saída**
- Outras UFs além do Amazonas
- Reforma Tributária (IBS/CBS) na NFC-e
- Criptografia de senha do certificado A1 (`Crypt`)
- PDV: abertura/fechamento de caixa (hoje só venda simples)

---

## 4. Decisões congeladas

Não mudam sem novo alinhamento:

- `nota_fiscais` (serviço) e `nfces` (produto) **permanecem tabelas separadas**
- NFC-e AM: stack **nativa** (sem `sped-nfe` / Focus / ACBr)
- Livro financeiro gerencial ≠ gateway Asaas (convivência)
- Um documento = um canal fiscal (sem misturar produto e serviço no mesmo doc no v1)
- UF v1 NFC-e: Amazonas (`cUF=13`); modelo **65**
- Senha A1: plaintext funcional no v1; `Crypt` é melhoria pós go-live

---

## 5. Changelog comprimido

| Data | Entrega |
|------|---------|
| 2026-07-20 | Core ERP Documentos + estoque + P/R + XML 55 entrada; Fase 4 NFC-e (cancel/inut/contingência homolog) |
| 2026-07-23 | Menus por categoria; formas de pagamento; PDV simples → NFC-e; toggles ERP/PDV/financeiro |
| 2026-07-24 | Área Contábil v1 (hub/livros/export); perfil `contador` + ACL; schema stub contábil + posting no-op |
| 2026-07-24 | Integridade cancel NFC-e ↔ ERP: estorno estoque + cancel P/R + status documento (`onFiscalCancelado`) |
| 2026-07-24 | §3.3 financeiro: UI manual + estorno baixa + ponte Asaas; §3.4 contábil 2B: plano/mapeamentos, posting, competência, DRE/balanço |
| 2026-07-24 | Admin plataforma (`is_platform_admin` + Artisan); admins de empresa imutáveis; painel de vínculos |

---

## 6. Fora de escopo (v1)

- NF-e 55 de saída (entrada XML já existe)
- Multi-UF / A3 / token
- IBS/CBS na NFC-e
- Homologação formal de software pela SEFAZ-AM (estado não exige)
- Substituir Asaas pelo livro gerencial
