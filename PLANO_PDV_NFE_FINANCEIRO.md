# Plano de Implementação: Módulo PDV, Estoque, Financeiro Básico e NF-e

## 1. Visão Geral e Arquitetura
A implementação visa transformar o G2M Fiscal num sistema mais completo com uma frente de caixa (PDV) ágil, controle de estoque com Kardex (auditoria), um financeiro básico (Contas a Pagar/Receber) e emissão de NF-e otimizada primariamente para optantes do Simples Nacional. A prioridade máxima no front-end é a **velocidade e fluidez** (sem page reloads, com sensação de Single Page Application), especialmente corrigindo gargalos de navegação existentes, como a lentidão da barra inferior.

## 2. Faseamento da Implementação para Agentes de Código

### FASE 1: Banco de Dados e Gerenciamento de Módulos
**Objetivo:** Controlar quais empresas têm acesso ao PDV e Financeiro sem sobrecarregar a tabela de empresas.
- **Tarefas:**
  - Criar tabela `empresa_modulos` (ou estrutura equivalente de assinatura).
  - Colunas base: `empresa_id`, `modulo` (pdv, financeiro), `ativo` (boolean).
  - Criar Middlewares/Policies no Laravel para bloquear rotas e ações caso o módulo não esteja ativo para a empresa logada.

### FASE 2: Cadastro de Produtos e Controle de Estoque (Kardex)
**Objetivo:** Estrutura genérica de produtos e registro histórico de movimentações (auditoria contínua).
- **Tarefas:**
  - Migrations e Models:
    - `produtos`: `id`, `empresa_id`, `nome`, `sku`, `ean` (código de barras), `preco_custo`, `preco_venda`, `unidade_medida`, `ncm`, `cfop_padrao`, `estoque_atual`, `ativo`.
    - `estoque_movimentacoes` (Kardex): `id`, `produto_id`, `empresa_id`, `tipo` (entrada/saida), `quantidade`, `origem` (ex: 'venda_pdv', 'compra', 'ajuste'), `referencia_id` (ID da venda/compra).
  - **Regra de Negócio:** Sempre que houver uma venda, o estoque é decrementado via *Database Transaction*, inserindo o registro simultaneamente na tabela do Kardex.

### FASE 3: Módulo Financeiro Básico
**Objetivo:** Controle elementar de Contas a Pagar e Contas a Receber ligado às vendas.
- **Tarefas:**
  - Ativar flag `FEATURE_FINANCEIRO`.
  - Migrations/Models:
    - `lancamentos_financeiros`: `id`, `empresa_id`, `tipo` (receita/despesa), `descricao`, `valor`, `data_vencimento`, `data_pagamento`, `status` (aberto, pago, atrasado), `origem`.
  - **Integração:** Ao fechar uma Venda no PDV, o sistema gera automaticamente os lançamentos de recebimento (se pago à vista, já nasce com `status='pago'` e `data_pagamento` preenchida).

### FASE 4: O Coração do Sistema - O PDV (Frente de Caixa)
**Objetivo:** Interface ultra-rápida, fluida e feita para uso intensivo diário.
- **Tarefas Frontend (Alpine.js/Livewire):**
  - Construir layout focado no uso de teclado (Ex: F2 finaliza venda, F4 cancela item, foco automático no campo de código de barras).
  - Requisições assíncronas de busca de produto e cálculo de totais para manter a resposta imediata (zero refresh).
  - Correção de UX geral: otimizar e cachear itens da barra de navegação inferior para eliminar a lentidão reportada.
- **Tarefas Backend:**
  - Modelos `vendas` (tabela mestre: total, descontos, forma_pagamento) e `venda_itens` (tabela detalhe: produto_id, qtd, valor_unit).

### FASE 5: Emissão de NF-e (Foco no Simples Nacional)
**Objetivo:** Integrar a venda com a validação e transmissão de nota fiscal de produto.
- **Tarefas:**
  - Uso do `nfephp-org/sped-common`.
  - Mapear informações mínimas para Simples Nacional (NCM, CFOP mapeado a partir da venda).
  - **Assincronicidade:** A emissão da NF-e *não* deve travar a tela do PDV. A venda é registrada rapidamente, o cliente é liberado, e o envio para a Sefaz roda em Background (via Laravel Jobs/Filas) ou via botão "Transmitir" posterior, dependendo do fluxo da loja.

## 3. Diretrizes e Recomendações de Performance (Critérios de Aceite)
- **Performance de Banco:** Usar Eloquent `select()` rigorosamente nas listagens e dropdowns (`select('id', 'nome')`) para não inflar a memória. Combinar agragados com `DB::raw` no Dashboard financeiro.
- **Segurança Tenant:** Uso obrigatório de `$request->validated()` e a injeção estrita do `empresa_id` do usuário logado durante as inserções para impedir vazamento de dados (Mass Assignment).
- **Log de Respostas da Sefaz:** Ao integrar NF-e, sanitizar respostas. Nunca logar *payloads* completos por questão de PII/Dados Fiscais Sensíveis (seguir guideline do projeto).