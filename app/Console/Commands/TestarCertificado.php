<?php

namespace App\Console\Commands;

use App\Exceptions\CertificadoA1Exception;
use App\Models\Empresa;
use App\Services\CertificadoA1Service;
use Illuminate\Console\Command;

class TestarCertificado extends Command
{
    protected $signature = 'teste:certificado {empresa_id}';

    protected $description = 'Testa se o sistema consegue ler e descriptografar o PFX da empresa (com fallback OpenSSL legacy)';

    public function handle(CertificadoA1Service $certificadoA1): int
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (! $empresa) {
            $this->error("Empresa ID {$id} não encontrada.");

            return self::FAILURE;
        }

        $certificado = $empresa->certificado;

        if (! $certificado) {
            $this->error('Essa empresa não tem certificado cadastrado no banco.');

            return self::FAILURE;
        }

        $this->line('OpenSSL (PHP): '.$certificadoA1->opensslVersion());
        $this->line('OpenSSL (CLI): '.($certificadoA1->opensslBinary() ?? 'não encontrado'));
        $this->line('Arquivo: '.$certificado->nome_arquivo);
        $this->newLine();

        $this->info('1. Lendo certificado via CertificadoA1Service...');

        try {
            $result = $certificadoA1->loadFromModel($certificado);
        } catch (CertificadoA1Exception $e) {
            $this->error('FALHA: '.$e->getMessage());
            if ($e->opensslError) {
                $this->warn('Detalhe OpenSSL: '.$e->opensslError);
            }

            return self::FAILURE;
        }

        $this->info('SUCESSO: Certificado desbloqueado!');
        if ($result->convertedFromLegacy) {
            $this->warn('Conversão legada (RC2/3DES → AES) foi necessária e o arquivo em storage foi reescrito.');
        }

        $this->line('------------------------------------------------');
        $this->info('DADOS DO CERTIFICADO:');
        $this->line('Emitido para: '.($result->commonName() ?? 'Desconhecido'));
        $from = $result->x509['validFrom_time_t'] ?? null;
        if (is_int($from)) {
            $this->line('Válido de: '.date('d/m/Y H:i:s', $from));
        }
        $this->line('Válido até: '.$result->validoAte->format('d/m/Y H:i:s'));
        $this->line('Emissor: '.($result->x509['issuer']['CN'] ?? 'Desconhecido'));
        $this->line('------------------------------------------------');

        if ($result->isExpired()) {
            $this->error('ATENÇÃO: Este certificado está VENCIDO!');

            return self::FAILURE;
        }

        if ($result->isNotYetValid()) {
            $this->error('ATENÇÃO: Este certificado ainda não é válido (data futura).');

            return self::FAILURE;
        }

        $this->info('STATUS: VÁLIDO e pronto para uso.');

        return self::SUCCESS;
    }
}
