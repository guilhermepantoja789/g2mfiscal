<?php

namespace App\Support\Navigation;

use App\Enums\EmpresaPerfil;
use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Services\Acl\EmpresaAcl;
use Illuminate\Support\Collection;

class EmpresaNavBuilder
{
    public function __construct(private readonly EmpresaAcl $acl) {}

    /**
     * @return array{
     *     empresa: ?Empresa,
     *     groups: list<array{
     *         id: string,
     *         label: string,
     *         icon: string,
     *         accent?: string,
     *         mobile_primary?: bool,
     *         href?: string,
     *         active: bool,
     *         items: list<array{
     *             label: string,
     *             href: string,
     *             icon: string,
     *             active: bool,
     *             badge?: string,
     *             keywords?: string,
     *             nested?: bool
     *         }>,
     *         hint?: string
     *     }>,
     *     utility: list<array{id: string, label: string, icon: string, href: string, active: bool}>,
     *     command_items: list<array{label: string, href: string, group: string, keywords: string}>
     * }
     */
    public function build(): array
    {
        $empresaId = $this->acl->empresaIdAtiva();
        $empresa = $empresaId
            ? Empresa::query()->with('modulos')->find($empresaId)
            : null;

        $temErp = $empresa?->temModulo(EmpresaModulo::MODULO_ERP) ?? false;
        $temPdv = $empresa?->temModulo(EmpresaModulo::MODULO_PDV) ?? false;
        $temFinGer = $empresa?->temModulo(EmpresaModulo::MODULO_FINANCEIRO_GERENCIAL) ?? false;
        $temContabil = $empresa?->temModulo(EmpresaModulo::MODULO_CONTABIL) ?? false;
        $financeiroAsaas = (bool) config('services.financeiro.enabled', false);

        $perfil = $this->acl->perfilAtivo();
        $ehContador = $perfil === EmpresaPerfil::Contador;
        $ehAdmin = $this->acl->podeAdministrar();
        $ehPlatform = $this->acl->isPlatformAdmin();
        $podeContabil = $temContabil && ($ehAdmin || $ehContador || $ehPlatform);
        $podeEscrever = $this->acl->podeEscreverOperacional();

        $groups = [];

        $groups[] = [
            'id' => 'inicio',
            'label' => 'Início',
            'icon' => 'home',
            'mobile_primary' => true,
            'href' => route('dashboard'),
            'active' => request()->routeIs('dashboard'),
            'items' => [
                $this->item('Dashboard', route('dashboard'), 'home', request()->routeIs('dashboard'), 'inicio painel home'),
            ],
        ];

        if ($podeContabil) {
            $contabilActive = request()->routeIs('contabil.*');
            $groups[] = [
                'id' => 'contabil',
                'label' => 'Contábil',
                'icon' => 'calculator',
                'accent' => 'brand',
                'mobile_primary' => true,
                'active' => $contabilActive,
                'items' => [
                    $this->item('Painel', route('contabil.dashboard'), 'chart-bar', request()->routeIs('contabil.dashboard'), 'contabil painel'),
                    $this->item('Livro — Serviços', route('contabil.livro_servicos'), 'book-open', request()->routeIs('contabil.livro_servicos'), 'livro servicos nfse'),
                    $this->item('Livro — Cupons', route('contabil.livro_cupons'), 'ticket', request()->routeIs('contabil.livro_cupons'), 'livro cupons nfce'),
                    $this->item('Livro — Entradas', route('contabil.livro_entradas'), 'inbox', request()->routeIs('contabil.livro_entradas'), 'livro entradas nfe'),
                    $this->item('Exportações', route('contabil.exportacoes'), 'download', request()->routeIs('contabil.exportacoes*'), 'export csv zip'),
                    $this->item('Plano de contas', route('contabil.plano.index'), 'list-bullet', request()->routeIs('contabil.plano*'), 'plano contas mapeamento'),
                    $this->item('DRE', route('contabil.dre'), 'presentation-chart', request()->routeIs('contabil.dre'), 'dre resultado'),
                    $this->item('Balanço', route('contabil.balanco'), 'scale', request()->routeIs('contabil.balanco'), 'balanco patrimonial'),
                    $this->item('Cadastro fiscal', route('contabil.cadastro_fiscal'), 'identification', request()->routeIs('contabil.cadastro_fiscal'), 'cadastro fiscal empresa'),
                ],
            ];
        }

        $fiscalServicosItems = [
            $this->item('Notas (NFS-e)', route('notas.index'), 'document-text', request()->routeIs('notas.index', 'notas.show', 'notas.imprimir', 'notas.create', 'notas.edit'), 'nfse notas servico'),
        ];
        if ($podeEscrever) {
            $fiscalServicosItems[] = $this->item('Serviços', route('servicos.index'), 'wrench', request()->routeIs('servicos.*'), 'servicos catalogo');
            $fiscalServicosItems[] = $this->item('Recorrências', route('recorrencias.index'), 'arrow-path', request()->routeIs('recorrencias.*'), 'recorrencias mensal');
        }
        $fiscalServicosItems[] = $this->item('Clientes', route('clientes.index'), 'users', request()->routeIs('clientes.*'), 'clientes');
        if (! $ehContador) {
            $fiscalServicosItems[] = $this->item(
                'Cobranças',
                route('cobrancas.index'),
                'banknotes',
                request()->routeIs('cobrancas.*'),
                'cobrancas asaas',
                $financeiroAsaas ? null : 'Breve',
            );
            $fiscalServicosItems[] = $this->item(
                'Carteira / Saque',
                route('carteira.index'),
                'wallet',
                request()->routeIs('carteira.*'),
                'carteira saque asaas',
                $financeiroAsaas ? null : 'Breve',
            );
        }

        $groups[] = [
            'id' => 'fiscal_servicos',
            'label' => 'Fiscal — Serviços',
            'icon' => 'document-text',
            'mobile_primary' => true,
            'active' => request()->routeIs('notas.*', 'servicos.*', 'recorrencias.*', 'clientes.*', 'cobrancas.*', 'carteira.*'),
            'items' => $fiscalServicosItems,
            'hint' => $ehContador ? 'Consulta de notas e clientes.' : 'Emissão manual — sem estoque/venda.',
        ];

        $fiscalProdutosItems = [
            $this->item('NFC-e — Painel', route('nfces.dashboard'), 'chart-bar', request()->routeIs('nfces.dashboard'), 'nfce painel cupom'),
            $this->item('Cupons (lista)', route('nfces.index'), 'ticket', request()->routeIs('nfces.index', 'nfces.show', 'nfces.imprimir'), 'nfce lista cupons'),
        ];
        if ($podeEscrever) {
            $fiscalProdutosItems[] = $this->item('Inutilizar', route('nfces.inutilizar.form'), 'no-symbol', request()->routeIs('nfces.inutilizar*'), 'inutilizar numeracao');
        }

        $groups[] = [
            'id' => 'fiscal_produtos',
            'label' => 'Fiscal — Produtos',
            'icon' => 'receipt',
            'mobile_primary' => ! $temErp,
            'active' => request()->routeIs('nfces.*'),
            'items' => $fiscalProdutosItems,
            'hint' => $ehContador ? 'Consulta de cupons.' : 'Emissão avulsa — sem estoque/financeiro.',
        ];

        if ($temErp) {
            $vendasItems = [];
            if ($temPdv && $podeEscrever) {
                $vendasItems[] = $this->item('PDV', route('pdv.index'), 'computer-desktop', request()->routeIs('pdv.*'), 'pdv ponto venda caixa');
            }
            $vendasItems[] = $this->item('Documentos', route('documentos.dashboard'), 'folder', request()->routeIs('documentos.dashboard'), 'documentos comerciais');
            $vendasItems[] = $this->item('Lista de documentos', route('documentos.index'), 'queue-list', request()->routeIs('documentos.index', 'documentos.show', 'documentos.create', 'documentos.importar*'), 'documentos lista', null, true);
            $vendasItems[] = $this->item('Produtos', route('produtos.dashboard'), 'cube', request()->routeIs('produtos.dashboard'), 'produtos painel');
            $vendasItems[] = $this->item('Lista de produtos', route('produtos.index'), 'queue-list', request()->routeIs('produtos.index', 'produtos.create', 'produtos.edit'), 'produtos lista', null, true);
            $vendasItems[] = $this->item('Estoque', route('estoque.dashboard'), 'archive-box', request()->routeIs('estoque.dashboard'), 'estoque kardex');
            $vendasItems[] = $this->item('Saldos', route('estoque.saldos'), 'queue-list', request()->routeIs('estoque.saldos', 'estoque.show'), 'estoque saldos', null, true);
            $vendasItems[] = $this->item('Fornecedores', route('fornecedores.dashboard'), 'truck', request()->routeIs('fornecedores.dashboard'), 'fornecedores');
            $vendasItems[] = $this->item('Lista de fornecedores', route('fornecedores.index'), 'queue-list', request()->routeIs('fornecedores.index', 'fornecedores.create', 'fornecedores.edit'), 'fornecedores lista', null, true);

            $groups[] = [
                'id' => 'vendas',
                'label' => 'Vendas',
                'icon' => 'shopping-cart',
                'mobile_primary' => true,
                'active' => request()->routeIs('pdv.*', 'documentos.*', 'produtos.*', 'estoque.*', 'fornecedores.*'),
                'items' => $vendasItems,
            ];
        }

        if ($temErp && $temFinGer) {
            $finItems = [
                $this->item('Painel', route('financeiro.dashboard'), 'chart-bar', request()->routeIs('financeiro.dashboard'), 'financeiro painel'),
                $this->item('Lançamentos', route('lancamentos.index'), 'banknotes', request()->routeIs('lancamentos.*'), 'lancamentos pagar receber'),
            ];
            if ($podeEscrever) {
                $finItems[] = $this->item('Formas de pagamento', route('formas-pagamento.index'), 'credit-card', request()->routeIs('formas-pagamento.*'), 'formas pagamento');
            }

            $groups[] = [
                'id' => 'financeiro',
                'label' => 'Financeiro',
                'icon' => 'currency-dollar',
                'mobile_primary' => true,
                'active' => request()->routeIs('financeiro.dashboard', 'lancamentos.*', 'formas-pagamento.*'),
                'items' => $finItems,
            ];
        }

        $utility = [];
        if ($ehPlatform) {
            $utility[] = [
                'id' => 'vinculos',
                'label' => 'Vínculos',
                'icon' => 'users',
                'href' => route('admin.vinculos.index'),
                'active' => request()->routeIs('admin.vinculos.*'),
            ];
        }
        if ($empresaId && $ehAdmin) {
            $utility[] = [
                'id' => 'config',
                'label' => 'Configurações',
                'icon' => 'cog',
                'href' => route('empresas.configuracao', $empresaId),
                'active' => request()->routeIs('empresas.configuracao'),
            ];
        }
        $utility[] = [
            'id' => 'trocar',
            'label' => 'Trocar empresa',
            'icon' => 'arrows-right-left',
            'href' => route('empresas.selecao'),
            'active' => false,
        ];

        $commandItems = $this->flattenCommandItems($groups, $utility);

        return [
            'empresa' => $empresa,
            'groups' => $groups,
            'utility' => $utility,
            'command_items' => $commandItems,
            'mobile_primary' => $this->mobilePrimary($groups),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @return list<array<string, mixed>>
     */
    private function mobilePrimary(array $groups): array
    {
        return Collection::make($groups)
            ->filter(fn (array $g) => ($g['mobile_primary'] ?? false) === true)
            ->take(4)
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $groups
     * @param  list<array<string, mixed>>  $utility
     * @return list<array{label: string, href: string, group: string, keywords: string}>
     */
    private function flattenCommandItems(array $groups, array $utility): array
    {
        $items = [];
        foreach ($groups as $group) {
            foreach ($group['items'] as $item) {
                $items[] = [
                    'label' => $item['label'],
                    'href' => $item['href'],
                    'group' => $group['label'],
                    'keywords' => strtolower($item['keywords'] ?? $item['label']),
                ];
            }
        }
        foreach ($utility as $u) {
            $items[] = [
                'label' => $u['label'],
                'href' => $u['href'],
                'group' => 'Sistema',
                'keywords' => strtolower($u['label'].' '.$u['id']),
            ];
        }

        return $items;
    }

    /**
     * @return array{label: string, href: string, icon: string, active: bool, badge?: string, keywords: string, nested: bool}
     */
    private function item(
        string $label,
        string $href,
        string $icon,
        bool $active,
        string $keywords = '',
        ?string $badge = null,
        bool $nested = false,
    ): array {
        $row = [
            'label' => $label,
            'href' => $href,
            'icon' => $icon,
            'active' => $active,
            'keywords' => $keywords !== '' ? $keywords : strtolower($label),
            'nested' => $nested,
        ];
        if ($badge !== null) {
            $row['badge'] = $badge;
        }

        return $row;
    }
}
