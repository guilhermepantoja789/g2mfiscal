<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recorrencia;
use App\Models\NotaFiscal;
use App\Models\Cliente;
use App\Services\NfseNacionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarRecorrencias extends Command
{
    protected $signature = 'fiscal:processar-recorrencias';
    protected $description = 'Processa as recorrências agendadas para hoje, calcula impostos e emite se configurado.';

    public function handle()
    {
        $hoje = Carbon::today();
        $this->info("=== Iniciando processamento: " . $hoje->format('d/m/Y') . " ===");

        // 1. Buscar Recorrências Vencendo Hoje
        $recorrencias = Recorrencia::where('ativo', true)
            ->whereDate('proxima_execucao', '<=', $hoje)
            ->where(function($q) use ($hoje) {
                $q->whereNull('data_fim')->orWhereDate('data_fim', '>=', $hoje);
            })
            ->with(['empresa', 'servico'])
            ->get();

        if ($recorrencias->isEmpty()) {
            $this->info("Nenhuma recorrência para processar hoje.");
            return;
        }

        foreach ($recorrencias as $rec) {
            $this->info("Processando ID {$rec->id} - {$rec->tomador_nome}");

            try {
                // ---------------------------------------------------------
                // PASSO 0: GARANTIR DADOS DO CLIENTE (CRUCIAL PARA O ENDEREÇO)
                // ---------------------------------------------------------
                $clienteId = $rec->cliente_id;

                if ($rec->tomador_cnpj) {
                    // CORREÇÃO AQUI: Trocado 'documento' por 'cnpj'
                    $cliente = Cliente::updateOrCreate(
                        ['empresa_id' => $rec->empresa_id, 'cnpj' => $rec->tomador_cnpj],
                        [
                            'razao_social' => $rec->tomador_nome,
                            'email' => $rec->tomador_email,
                            'inscricao_municipal' => $rec->tomador_im,
                            'telefone' => $rec->tomador_telefone,

                            // Endereço Completo
                            'cep' => $rec->tomador_cep,
                            'logradouro' => $rec->tomador_endereco,
                            'numero' => $rec->tomador_numero,
                            'complemento' => $rec->tomador_complemento,
                            'bairro' => $rec->tomador_bairro,
                            'cidade_codigo' => $rec->tomador_cidade, // IBGE
                            'uf' => $rec->tomador_uf,
                        ]
                    );
                    $clienteId = $cliente->id;
                }

                // ---------------------------------------------------------
                // PASSO A: CÁLCULO DE IMPOSTOS
                // ---------------------------------------------------------
                $valorServico = (float) $rec->valor_servico;

                $pFed = (float) ($rec->p_tot_trib_fed ?? 0);
                $pEst = (float) ($rec->p_tot_trib_est ?? 0);
                $pMun = (float) ($rec->p_tot_trib_mun ?? 0);

                $vFed = ($valorServico * $pFed) / 100;
                $vEst = ($valorServico * $pEst) / 100;
                $vMun = ($valorServico * $pMun) / 100;

                // ---------------------------------------------------------
                // PASSO B: DESCRIÇÃO
                // ---------------------------------------------------------
                $descricao = str_replace(
                    ['{MES}', '{ANO}', '{MES_EXTENSO}'],
                    [date('m'), date('Y'), $hoje->translatedFormat('F')],
                    $rec->descricao_servico
                );

                // ---------------------------------------------------------
                // PASSO C: CRIAR A NOTA
                // ---------------------------------------------------------
                $nota = NotaFiscal::create([
                    'empresa_id' => $rec->empresa_id,
                    'cliente_id' => $clienteId,
                    'servico_id' => $rec->servico_id,

                    'status'     => $rec->emitir_automaticamente ? 'processando' : 'criada',
                    'ambiente'   => config('app.env') === 'production' ? 'producao' : 'homologacao',

                    'tomador_cnpj'  => $rec->tomador_cnpj,
                    'tomador_nome'  => $rec->tomador_nome,
                    'tomador_email' => $rec->tomador_email,

                    'valor_servico' => $valorServico,
                    'descricao'     => $descricao,
                    'emissao'       => now(),

                    'trib_issqn'    => $rec->trib_issqn,
                    'tp_ret_issqn'  => $rec->tp_ret_issqn,

                    // Salva Porcentagens e Valores
                    'p_tot_trib_fed' => $pFed,
                    'p_tot_trib_est' => $pEst,
                    'p_tot_trib_mun' => $pMun,
                    'v_tot_trib_fed' => $vFed,
                    'v_tot_trib_est' => $vEst,
                    'v_tot_trib_mun' => $vMun,
                ]);

                // Recarrega relacionamentos e casts
                $nota->refresh();
                $nota->load(['cliente', 'servico']);

                $this->info("   -> Nota #{$nota->id} criada.");

                // ---------------------------------------------------------
                // PASSO D: EMITIR (SE AUTO)
                // ---------------------------------------------------------
                if ($rec->emitir_automaticamente) {
                    $this->emitirNotaAutomaticamente($nota);
                }

                // ---------------------------------------------------------
                // PASSO E: PRÓXIMA DATA
                // ---------------------------------------------------------
                $this->atualizarProximaData($rec);

            } catch (\Exception $e) {
                Log::error("ERRO Recorrência {$rec->id}: " . $e->getMessage());
                $this->error("   -> Erro: " . $e->getMessage());
            }
        }
    }

    private function emitirNotaAutomaticamente(NotaFiscal $nota)
    {
        $this->info("   -> Emitindo na API...");

        try {
            if (!$nota->servico) throw new \Exception("Serviço não vinculado.");

            $codMun = $nota->servico->codigo_tributacao_municipal;
            $codNbs = $nota->servico->codigo_tributacao_nacional;

            // Instancia Service
            $service = new NfseNacionalService($nota->empresa);

            // Monta dados IDÊNTICO ao NotaFiscalController
            $dados = [
                'numero' => $nota->id,
                'serie' => '1',
                'competencia' => $nota->emissao->format('Y-m-d'),

                'tomador_doc' => $nota->tomador_cnpj,
                'tomador_nome' => $nota->tomador_nome,
                'tomador_email' => $nota->tomador_email,

                // Endereço via relacionamento cliente (garantido no passo 0)
                'tomador_endereco' => $nota->cliente->logradouro ?? '',
                'tomador_numero'   => $nota->cliente->numero ?? 'S/N',
                'tomador_bairro'   => $nota->cliente->bairro ?? 'Centro',
                'tomador_cep'      => $nota->cliente->cep ?? '',
                'tomador_cidade_codigo' => $nota->cliente->cidade_codigo ?? '1302603',
                'tomador_uf'       => $nota->cliente->uf ?? 'AM',

                'valor' => $nota->valor_servico,
                'discriminacao' => $nota->descricao,
                'tributacao_iss' => $nota->trib_issqn,
                'retencao_iss' => $nota->tp_ret_issqn,

                'servico_nbs' => $codNbs,
                'servico_municipal' => $codMun,

                'aliquota' => ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00,

                'v_tot_trib_fed' => $nota->v_tot_trib_fed,
                'v_tot_trib_est' => $nota->v_tot_trib_est,
                'v_tot_trib_mun' => $nota->v_tot_trib_mun,
            ];

            $retorno = $service->emitirNota($dados);

            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado']
                ]);
                $this->info("   -> SUCESSO! Nota: " . $retorno['numero_nota']);
            } else {
                $msg = $retorno['mensagem'];
                if(isset($retorno['erros']) && is_array($retorno['erros'])) {
                    $msgs = [];
                    foreach($retorno['erros'] as $e) {
                        $msgs[] = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                    }
                    $msg = implode(' | ', $msgs);
                }

                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $msg,
                    'xml_enviado' => $retorno['xml_dps_enviado'] ?? null
                ]);
                $this->error("   -> FALHA: " . $msg);
            }

        } catch (\Exception $e) {
            $nota->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);
            $this->error("   -> EXCEPTION: " . $e->getMessage());
        }
    }

    private function atualizarProximaData(Recorrencia $rec)
    {
        $proxima = Carbon::parse($rec->proxima_execucao);
        switch($rec->frequencia) {
            case 'mensal': $proxima->addMonth(); break;
            case 'semanal': $proxima->addWeek(); break;
            case 'anual': $proxima->addYear(); break;
            case 'unico':
                $rec->update(['ativo' => false]);
                return;
        }
        $rec->update(['proxima_execucao' => $proxima]);
        $this->info("   -> Próximo: " . $proxima->format('d/m/Y'));
    }
}
