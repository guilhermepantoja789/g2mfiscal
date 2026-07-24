# G2M Fiscal

Plataforma SaaS multi-empresa para **gestão fiscal, comercial, estoque e financeira**. Stack: Laravel 12, Blade, Tailwind, Alpine.js.

O fluxo principal de negócio passa por **Documentos Comerciais** (`DocumentoComercial`), que orquestram emissão fiscal (NFS-e / NFC-e), movimentos de estoque e lançamentos financeiros. As telas avulsas de NFS-e e NFC-e permanecem para legado e laboratório.

Status do produto e próximos passos: [`PLANO.md`](PLANO.md).

## Funcionalidades

### Multi-empresa e cadastros
- Várias empresas por usuário, com sessão `empresa_ativa`
- Clientes, fornecedores, serviços, produtos (NCM/CFOP/CSOSN/EAN)
- Equipe/vínculos e módulos da empresa são geridos **somente** pelo admin de plataforma em `/app/admin/vinculos` (operador/contador; admins de empresa imutáveis na UI)
- Admin de **plataforma** (`users.is_platform_admin`): vê todas as empresas e gerencia módulos + vínculos em `/app/admin/vinculos` — flag **somente via Artisan** (sem UI/seeder)
- Certificado digital A1 (OpenSSL 3 + fallback legado)

### Fiscal
- **NFS-e Nacional** — DPS / SEFIN, job assíncrono (`nota_fiscais`)
- **NFC-e Amazonas (modelo 65)** — engine nativo em `App\Core\FiscalEngine\` (`nfces`); sem `sped-nfe` / Focus / ACBr
- DANFSE (NFS-e) e DANFE bobina 80mm (NFC-e); cancelamento, inutilização e contingência (homolog)
- Importação de **XML NF-e modelo 55 de compra** (entrada)

### Core ERP (Documentos)
- Venda (canal `nfse` ou `nfce`) e compra (`nfe_entrada`)
- Kardex de estoque + contas a pagar/receber (`lancamentos_financeiros`)
- Formas de pagamento (à vista/prazo, parcelas, juros) → N lançamentos
- Hub do documento: itens, fiscal vinculado, estoque e financeiro
- Módulos por empresa (`empresa_modulos`; ERP / PDV / financeiro gerencial opt-out; **contábil opt-in**)

### PDV
- Venda simples (`/app/pdv`): busca EAN/SKU, forma de pagamento, finaliza via orchestrator + NFC-e
- Sem abertura/fechamento de caixa no v1

### Contábil (v1)
- Hub fiscal para admin/contador: painel, livros NFS-e/NFC-e/entradas, export CSV/ZIP, cadastro fiscal
- Plano de contas + postagem dobrada (`ContabilPostingService`); DRE/balanço gerencial

### Financeiro gateway (opcional)
- Cobranças e carteira Asaas atrás de `FEATURE_FINANCEIRO`
- Convive com o livro gerencial; não o substitui

### Recorrências
- Assinaturas que geram NFS-e periodicamente (ainda **sem** passar por `DocumentoComercial` — ver [`PLANO.md`](PLANO.md))

## Arquitetura (visão rápida)

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
                      → EstoqueService + LancamentoFinanceiroService
                      → ContabilPostingService (partidas 2B)
```

Arquivos-chave:

| Área | Caminho |
|------|---------|
| Orchestrator ERP | `app/Services/Erp/DocumentoOrchestrator.php` |
| Import XML 55 | `app/Services/Erp/NfeXmlImporter.php` |
| Estoque / P&R | `app/Services/Erp/EstoqueService.php`, `LancamentoFinanceiroService.php` |
| Contábil | `app/Services/Contabil/` |
| NFS-e | `app/Services/NfseNacionalService.php` |
| NFC-e engine | `app/Core/FiscalEngine/` |
| NFC-e adapter | `app/Services/Fiscal/RawNativeNfceIssuer.php` |
| Plano (status + próximos passos) | [`PLANO.md`](PLANO.md) |
| Guia emissão NFC-e | [`NFCE_EMISSAO.md`](NFCE_EMISSAO.md) |

## Tech stack

- PHP 8.2+, Laravel 12
- MySQL ou PostgreSQL (testes: SQLite in-memory)
- Tailwind, Alpine.js, Vite, DomPDF, `endroid/qr-code`
- `nfephp-org/sped-common` **somente** para assinatura NFS-e

## Pré-requisitos

- PHP >= 8.2, Composer, Node.js/npm
- Banco MySQL ou PostgreSQL
- Fila (`QUEUE_CONNECTION=database` ou equivalente) para emissão assíncrona
- OpenSSL (ambientes com OpenSSL 3: A1 legado usa fallback automático)

## Instalação

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# Configure DB_* e filas no .env
php artisan migrate
npm run build
```

Desenvolvimento:

```bash
php artisan serve
# outro terminal
npm run dev
# worker de fila (emissão fiscal)
php artisan queue:work
```

Acesse `http://localhost:8000` → área logada em `/app`.

## Configuração

### NFS-e Nacional
```ini
# 1=produção, 2=homologação; vazio = deriva de APP_ENV
# NFSE_NACIONAL_TP_AMB=2
```

### NFC-e (Amazonas)
```ini
FISCAL_NFCE_DRIVER=raw_native
NFCE_ENDPOINT_PROFILE=homolog_nac
NFCE_SSL_VERIFY=false
```

Cadastro IE, CRT, CSC, série e ambiente na configuração da empresa.

### Financeiro Asaas (opcional)
```ini
FEATURE_FINANCEIRO=true
```

### Módulo ERP
Por padrão o ERP está habilitado para a empresa (sem linha em `empresa_modulos`). Para desligar, registre `modulo=erp` com `ativo=false`. Contábil é **opt-in**.

### Admin de plataforma
```bash
# Concede visão de todas as empresas + gestão em /app/admin/vinculos
php artisan user:platform-admin email@exemplo.com
php artisan user:platform-admin email@exemplo.com --revoke

# Único caminho ops para promover admin de empresa (UI só atribui operador/contador)
php artisan user:attach-empresa email@exemplo.com EMPRESA_ID --perfil=admin
php artisan user:attach-empresa email@exemplo.com EMPRESA_ID --perfil=operador
php artisan user:attach-empresa email@exemplo.com EMPRESA_ID --detach
```

Criação de login sem vínculo: `php artisan create:user`.

## UI principal (`/app`)

| Rota | Uso |
|------|-----|
| `/app/documentos` | Hub comercial (venda/compra, confirmar, fiscal) |
| `/app/documentos/importar-xml` | Entrada NF-e 55 |
| `/app/pdv` | Venda rápida → NFC-e |
| `/app/produtos`, `/app/fornecedores` | Cadastros |
| `/app/formas-pagamento` | Formas de pagamento |
| `/app/estoque` | Saldos e Kardex |
| `/app/financeiro/lancamentos` | Contas a pagar/receber |
| `/app/contabil` | Hub contábil (opt-in) |
| `/app/admin/vinculos` | Empresas (plataforma): módulos + vínculos (só platform admin) |
| `/app/notas`, `/app/nfces` | Emissão avulsa / lab (legado) |

## Testes

```bash
php artisan test
```

Cobertura relevante: FiscalEngine, NFS-e ambiente/payload, ERP (estoque, XML importer, orchestrator, PDV), ACL/contábil.

## Documentação

| Documento | Conteúdo |
|-----------|----------|
| Este README | Visão do produto, setup, config |
| [`PLANO.md`](PLANO.md) | O que é hoje, gaps de robustez, próximos passos |
| [`NFCE_EMISSAO.md`](NFCE_EMISSAO.md) | Guia técnico portável de emissão NFC-e |

## Licença

[MIT](https://opensource.org/licenses/MIT)
