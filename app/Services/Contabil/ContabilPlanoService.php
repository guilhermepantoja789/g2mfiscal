<?php

namespace App\Services\Contabil;

use App\Models\ContaContabil;
use App\Models\Empresa;
use App\Models\MapeamentoContabil;
use App\Models\PlanoContas;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ContabilPlanoService
{
    public function __construct(
        private ContabilPostingService $posting,
    ) {}

    public function garantirPadrao(Empresa $empresa): void
    {
        $this->posting->garantirPlanoPadrao($empresa);
    }

    /**
     * @return Collection<int, ContaContabil>
     */
    public function listarContas(Empresa $empresa): Collection
    {
        return ContaContabil::query()
            ->where('empresa_id', $empresa->id)
            ->orderBy('codigo')
            ->get();
    }

    /**
     * @return Collection<int, MapeamentoContabil>
     */
    public function listarMapeamentos(Empresa $empresa): Collection
    {
        return MapeamentoContabil::query()
            ->where('empresa_id', $empresa->id)
            ->with('conta')
            ->orderBy('origem')
            ->get();
    }

    /**
     * @param  array{codigo: string, nome: string, tipo: string, natureza: string, ativo?: bool}  $dados
     */
    public function criarConta(Empresa $empresa, array $dados): ContaContabil
    {
        $this->garantirPadrao($empresa);

        $codigo = trim($dados['codigo']);
        if ($codigo === '') {
            throw new InvalidArgumentException('Código obrigatório.');
        }

        $exists = ContaContabil::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo', $codigo)
            ->exists();

        if ($exists) {
            throw new InvalidArgumentException('Já existe conta com este código.');
        }

        $plano = PlanoContas::query()
            ->where('empresa_id', $empresa->id)
            ->where('codigo', 'PADRAO')
            ->first();

        return ContaContabil::create([
            'empresa_id' => $empresa->id,
            'plano_id' => $plano?->id,
            'codigo' => $codigo,
            'nome' => $dados['nome'],
            'tipo' => $dados['tipo'],
            'natureza' => strtoupper($dados['natureza']),
            'ativo' => $dados['ativo'] ?? true,
        ]);
    }

    /**
     * @param  array{codigo?: string, nome?: string, tipo?: string, natureza?: string, ativo?: bool}  $dados
     */
    public function atualizarConta(ContaContabil $conta, array $dados): ContaContabil
    {
        $payload = [];
        foreach (['codigo', 'nome', 'tipo'] as $key) {
            if (array_key_exists($key, $dados)) {
                $payload[$key] = $dados[$key];
            }
        }
        if (array_key_exists('natureza', $dados)) {
            $payload['natureza'] = strtoupper((string) $dados['natureza']);
        }
        if (array_key_exists('ativo', $dados)) {
            $payload['ativo'] = (bool) $dados['ativo'];
        }

        if (isset($payload['codigo'])) {
            $dup = ContaContabil::query()
                ->where('empresa_id', $conta->empresa_id)
                ->where('codigo', $payload['codigo'])
                ->where('id', '!=', $conta->id)
                ->exists();
            if ($dup) {
                throw new InvalidArgumentException('Já existe conta com este código.');
            }
        }

        $conta->update($payload);

        return $conta->fresh();
    }

    /**
     * @param  array{origem: string, conta_id: int, papel: string}  $dados
     */
    public function salvarMapeamento(Empresa $empresa, array $dados): MapeamentoContabil
    {
        $origem = $dados['origem'];
        $papel = strtoupper($dados['papel']);
        if (! in_array($papel, ['D', 'C'], true)) {
            throw new InvalidArgumentException('Papel deve ser D ou C.');
        }

        $conta = ContaContabil::query()
            ->where('empresa_id', $empresa->id)
            ->whereKey($dados['conta_id'])
            ->firstOrFail();

        // Um papel por origem: substitui mapeamento anterior do mesmo papel.
        $anteriores = MapeamentoContabil::query()
            ->where('empresa_id', $empresa->id)
            ->where('origem', $origem)
            ->get();

        foreach ($anteriores as $ant) {
            if (strtoupper((string) ($ant->metadados['papel'] ?? '')) === $papel) {
                if ((int) $ant->conta_id === (int) $conta->id) {
                    return $ant;
                }
                $ant->delete();
            }
        }

        return MapeamentoContabil::create([
            'empresa_id' => $empresa->id,
            'origem' => $origem,
            'conta_id' => $conta->id,
            'metadados' => ['papel' => $papel],
        ]);
    }

    public function removerMapeamento(MapeamentoContabil $mapa): void
    {
        $mapa->delete();
    }
}
