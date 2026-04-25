<?php

namespace App\Jobs;

use App\Models\NotaFiscal;
use App\Services\NfseNacionalService;
use App\Services\FinanceiroService;
use App\Notifications\EmissaoNotaConcluidaNotification;
use App\Notifications\EmissaoNotaFalhaNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class EmitirNotaFiscalJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [120, 300]; // Tentar novamente após 2 min, depois 5 min

    public NotaFiscal $nota;

    /**
     * Create a new job instance.
     */
    public function __construct(NotaFiscal $nota)
    {
        $this->nota = $nota;
    }

    /**
     * Execute the job.
     */
    public function handle(FinanceiroService $financeiroService): void
    {
        $nota = $this->nota;
        
        // Se a nota já foi autorizada ou cancelada, não processa
        if (!in_array($nota->status, ['processando', 'criada', 'erro'])) {
            return;
        }

        $codMun = $nota->servico->codigo_tributacao_municipal;
        $codNbs = $nota->servico->codigo_tributacao_nacional;
        $aliqVal = ($nota->p_tot_trib_mun > 0) ? $nota->p_tot_trib_mun : 2.00;

        $dados = [
            'numero' => $nota->id,
            'serie' => config('app.env') === 'production' ? '1' : '99',
            'competencia' => $nota->emissao->format('Y-m-d'),

            'tomador_doc' => $nota->tomador_cnpj,
            'tomador_nome' => $nota->tomador_nome,
            'tomador_email' => $nota->tomador_email,

            'tomador_endereco' => $nota->cliente->logradouro ?? 'Endereço não inf.',
            'tomador_numero' => $nota->cliente->numero ?? 'S/N',
            'tomador_bairro' => $nota->cliente->bairro ?? 'Centro',
            'tomador_cep' => $nota->cliente->cep ?? '69000000',
            'tomador_cidade_codigo' => $nota->cliente->cidade_codigo ?? '1302603',
            'tomador_uf' => $nota->cliente->uf ?? 'AM',
            'tomador_complemento' => $nota->cliente->complemento ?? '',

            'valor' => $nota->valor_servico,
            'discriminacao' => $nota->descricao,
            'tributacao_iss' => $nota->trib_issqn,
            'retencao_iss' => $nota->tp_ret_issqn,

            'servico_nbs' => $codNbs,
            'servico_municipal' => $codMun,

            'aliquota' => $aliqVal,

            'v_tot_trib_fed' => $nota->v_tot_trib_fed,
            'v_tot_trib_est' => $nota->v_tot_trib_est,
            'v_tot_trib_mun' => $nota->v_tot_trib_mun,
        ];

        try {
            $service = new NfseNacionalService($nota->empresa);
            $retorno = $service->emitirNota($dados);

            if (isset($retorno['xml_dps_enviado'])) {
                $nota->xml_enviado = $retorno['xml_dps_enviado'];
                $nota->save();
            }

            if ($retorno['sucesso']) {
                $nota->update([
                    'status' => 'autorizada',
                    'numero_nfse' => $retorno['numero_nota'],
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'chave_acesso' => $retorno['chave_acesso'] ?? null,
                    'xml_autorizado' => $retorno['xml_autorizado'],
                    'mensagem_erro' => null
                ]);

                if ($nota->cobranca) {
                    $financeiroService->ativarCobranca($nota->cobranca);
                    if ($nota->cobranca->descricao != 'Ref. NFS-e Nº ' . $retorno['numero_nota']) {
                        $nota->cobranca->update(['descricao' => 'Ref. NFS-e Nº ' . $retorno['numero_nota']]);
                    }
                }

                // Disparar Notificação
                if ($nota->empresa && $nota->empresa->dono) {
                    $nota->empresa->dono->notify(new EmissaoNotaConcluidaNotification($nota));
                }

            } else {
                $msg = $retorno['mensagem'];
                $isBusinessError = false;
                
                if (isset($retorno['erros']) && is_array($retorno['erros'])) {
                    $msgs = [];
                    foreach ($retorno['erros'] as $e) {
                        $detalhe = is_array($e) ? ($e['Descricao'] ?? json_encode($e)) : $e;
                        $msgs[] = $detalhe;
                        $isBusinessError = true;
                    }
                    $msg = implode(' | ', $msgs);
                }

                if (!$isBusinessError) {
                    // Falha de comunicação, vamos jogar Exception para forçar o retry do Job
                    throw new \Exception("Falha na API Nacional: " . $msg);
                }

                $nota->update([
                    'status' => 'erro',
                    'mensagem_erro' => $msg
                ]);

                // Notificar erro
                if ($nota->empresa && $nota->empresa->dono) {
                    $nota->empresa->dono->notify(new EmissaoNotaFalhaNotification($nota, $msg));
                }
            }
        } catch (\Exception $e) {
            // Se for timeout ou erro interno, o worker irá retentar se ainda tiver tries
            throw $e; 
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->nota->update([
            'status' => 'erro',
            'mensagem_erro' => 'Erro interno ao processar nota: ' . $exception->getMessage()
        ]);

        if ($this->nota->empresa && $this->nota->empresa->dono) {
            $this->nota->empresa->dono->notify(new EmissaoNotaFalhaNotification($this->nota, $exception->getMessage()));
        }
    }
}
