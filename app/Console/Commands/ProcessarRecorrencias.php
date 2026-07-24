<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recorrencia;
use App\Models\NotaFiscal;
use App\Models\Cliente;
use App\Services\NfseNacionalService;
use App\Services\FinanceiroService;
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
                $servico = $rec->servico;
                $nota = NotaFiscal::create([
                    'empresa_id' => $rec->empresa_id,
                    'cliente_id' => $clienteId,
                    'servico_id' => $rec->servico_id,

                    'status'     => $rec->emitir_automaticamente ? 'processando' : 'criada',
                    'ambiente'   => \App\Services\NfseAmbiente::label(),
                    'numero_dps' => $rec->emitir_automaticamente
                        ? \App\Services\NfseDpsNumero::reservar($rec->empresa)
                        : null,

                    'tomador_cnpj'  => $rec->tomador_cnpj,
                    'tomador_nome'  => $rec->tomador_nome,
                    'tomador_email' => $rec->tomador_email,

                    'valor_servico' => $valorServico,
                    'descricao'     => $descricao,
                    'emissao'       => now(),

                    'trib_issqn'    => $rec->trib_issqn,
                    'tp_ret_issqn'  => $rec->tp_ret_issqn,

                    'fin_nfse' => $servico?->fin_nfse ?: \App\Services\NfseIbscbsBuilder::DEFAULT_FIN_NFSE,
                    'c_ind_op' => $servico?->c_ind_op ?: \App\Services\NfseIbscbsBuilder::DEFAULT_C_IND_OP,
                    'cst_ibscbs' => $servico?->cst_ibscbs ?: \App\Services\NfseIbscbsBuilder::DEFAULT_CST,
                    'c_class_trib' => $servico?->c_class_trib ?: \App\Services\NfseIbscbsBuilder::DEFAULT_C_CLASS_TRIB,
                    'ind_dest' => \App\Services\NfseIbscbsBuilder::DEFAULT_IND_DEST,

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
                // PASSO D: GERAR COBRANÇA (RASCUNHO)
                // ---------------------------------------------------------
                // Gap 1 Corrigido: Agora geramos a cobrança vinculada
                $financeiroService = app(FinanceiroService::class);
                try {
                    // Define vencimento padrão (ex: 5 dias após emissão ou usar configuração da recorrência se houver)
                    // Por simplicidade, vamos usar proxima_execucao + 5 dias, ou hoje + 5 dias
                    $vencimento = Carbon::parse($rec->proxima_execucao)->addDays(5);
                    
                    $financeiroService->gerarCobrancaDeNota($nota, $vencimento);
                    $this->info("   -> Cobrança (Rascunho) gerada.");
                } catch (\Exception $e) {
                    Log::error("ERRO ao gerar cobrança para nota {$nota->id}: " . $e->getMessage());
                    $this->error("   -> Falha ao gerar cobrança: " . $e->getMessage());
                }

                // ---------------------------------------------------------
                // PASSO E: EMITIR (SE AUTO)
                // ---------------------------------------------------------
                if ($rec->emitir_automaticamente) {
                    $this->emitirNotaAutomaticamente($nota);
                }

                // ---------------------------------------------------------
                // PASSO F: PRÓXIMA DATA
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
        $this->info('   -> Emitindo na API...');

        try {
            $nota->loadMissing(['cliente', 'servico', 'empresa.certificado', 'cobranca']);
            $dados = \App\Services\NfseEmitPayloadBuilder::fromNota($nota);

            $service = new NfseNacionalService($nota->empresa);
            $retorno = $service->emitirNota($dados, $nota);

            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'ambiente' => \App\Services\NfseAmbiente::label(),
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado'],
                    'xml_enviado' => $retorno['xml_dps_enviado'] ?? $nota->xml_enviado,
                ]);

                if ($nota->cobranca) {
                    $financeiroService = app(FinanceiroService::class);
                    $financeiroService->ativarCobranca($nota->cobranca);

                    $nota->cobranca->update(['descricao' => 'Ref. NFS-e Nº '.$retorno['numero_nota']]);
                    $this->info('   -> Cobrança ativada (PENDING).');
                }

                $this->info('   -> SUCESSO! Nota: '.$retorno['numero_nota']);
            } else {
                $msg = $retorno['mensagem'];
                if (isset($retorno['erros']) && is_array($retorno['erros'])) {
                    $msgs = [];
                    foreach ($retorno['erros'] as $e) {
                        $msgs[] = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                    }
                    $msg = implode(' | ', $msgs);
                }

                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $msg,
                    'xml_enviado' => $retorno['xml_dps_enviado'] ?? $nota->xml_enviado,
                ]);
                $this->error('   -> FALHA: '.$msg);
            }

        } catch (\Exception $e) {
            $nota->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);
            $this->error('   -> EXCEPTION: '.$e->getMessage());
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
