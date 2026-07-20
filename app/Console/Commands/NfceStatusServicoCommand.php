<?php

namespace App\Console\Commands;

use App\Contracts\FiscalIssuerInterface;
use App\Models\Empresa;
use Illuminate\Console\Command;

class NfceStatusServicoCommand extends Command
{
    protected $signature = 'nfce:status-servico {empresa : ID da empresa}';

    protected $description = 'Consulta status do serviço NFC-e na SEFAZ-AM';

    public function handle(FiscalIssuerInterface $issuer): int
    {
        $empresa = Empresa::with('certificado')->find($this->argument('empresa'));
        if (! $empresa) {
            $this->error('Empresa não encontrada.');

            return self::FAILURE;
        }

        try {
            $ret = $issuer->statusServico($empresa);
            $this->info("cStat: {$ret['cStat']}");
            $this->line("xMotivo: {$ret['xMotivo']}");

            return $ret['cStat'] === '107' ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
