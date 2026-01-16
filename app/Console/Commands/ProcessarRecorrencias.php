<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recorrencia;
use App\Models\NotaFiscal;
use App\Services\NfseNacionalService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ProcessarRecorrencias extends Command
{
    protected $signature = 'fiscal:processar-recorrencias';
    protected $description = 'Processa as recorrências agendadas para hoje';

    public function handle()
    {
        $hoje = Carbon::today();
        $this->info("Iniciando processamento para: " . $hoje->format('d/m/Y'));

        // Busca tudo que está ativo E vence hoje (ou antes, caso o cron tenha falhado ontem)
        $recorrencias = Recorrencia::where('ativo', true)
            ->whereDate('proxima_execucao', '<=', $hoje)
            ->where(function($q) use ($hoje) {
                $q->whereNull('data_fim')->orWhereDate('data_fim', '>=', $hoje);
            })
            ->get();

        foreach ($recorrencias as $rec) {
            $this->info("Processando ID: {$rec->id} - {$rec->tomador_nome}");

            // 1. Substituir Variáveis na Descrição ({MES}, {ANO})
            $descricao = str_replace(
                ['{MES}', '{ANO}', '{MES_EXTENSO}'],
                [date('m'), date('Y'), $hoje->translatedFormat('F')],
                $rec->descricao_servico
            );

            // 2. Criar a Nota Fiscal
            try {
                $nota = NotaFiscal::create([
                    'empresa_id' => $rec->empresa_id,
                    'cliente_id' => $rec->cliente_id,
                    'servico_id' => $rec->servico_id,
                    'status'     => $rec->emitir_automaticamente ? 'processando' : 'criada', // Se auto, já nasce tentando
                    'ambiente'   => config('app.env') === 'production' ? 'producao' : 'homologacao',

                    'tomador_cnpj' => $rec->tomador_cnpj,
                    'tomador_nome' => $rec->tomador_nome,
                    'tomador_email' => $rec->tomador_email,

                    'valor_servico' => $rec->valor_servico,
                    'descricao' => $descricao,
                    'emissao' => now(), // Data de hoje

                    'trib_issqn' => $rec->trib_issqn,
                    'tp_ret_issqn' => $rec->tp_ret_issqn,
                    'p_tot_trib_mun' => $rec->p_tot_trib_mun,
                ]);

                // 3. Se for Automático, Tentar Emitir
                if ($rec->emitir_automaticamente) {
                    $service = new NfseNacionalService($rec->empresa);
                    // Aqui você chamaria a lógica de emissão (pode refatorar o Controller para um Service method para reutilizar)
                    // Para simplificar, assumimos que o status fica 'processando' e um Job processa,
                    // ou chamamos direto o emitir se você extraiu a lógica do Controller.
                }

                // 4. Calcular Próxima Data
                $proxima = Carbon::parse($rec->proxima_execucao);

                switch($rec->frequencia) {
                    case 'mensal': $proxima->addMonth(); break;
                    case 'semanal': $proxima->addWeek(); break;
                    case 'anual': $proxima->addYear(); break;
                    case 'unico':
                        $rec->update(['ativo' => false]); // Desativa
                        continue 2; // Pula atualização de data
                }

                $rec->update(['proxima_execucao' => $proxima]);
                $this->info("Nota Gerada! Próxima execução: " . $proxima->format('d/m/Y'));

            } catch (\Exception $e) {
                Log::error("Falha na recorrencia {$rec->id}: " . $e->getMessage());
                $this->error("Erro: " . $e->getMessage());
            }
        }
    }
}
