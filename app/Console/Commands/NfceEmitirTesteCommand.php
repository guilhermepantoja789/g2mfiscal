<?php

namespace App\Console\Commands;

use App\Core\FiscalEngine\Dto\NfceItem;
use App\Core\FiscalEngine\Dto\NfcePayment;
use App\Jobs\EmitirNfceJob;
use App\Models\Empresa;
use App\Models\Nfce;
use App\Services\Fiscal\NfceEmitRequest;
use App\Services\Fiscal\RawNativeNfceIssuer;
use Illuminate\Console\Command;

class NfceEmitirTesteCommand extends Command
{
    protected $signature = 'nfce:emitir-teste
        {empresa : ID da empresa}
        {--sync : Emite de forma síncrona (sem fila)}
        {--profile=homolog_nac : Perfil de endpoint (homolog_nac|homolog|producao)}
        {--force : Confirma uso do perfil producao}
        {--valor=1.00 : Valor unitário do item de teste}';

    protected $description = 'Smoke test de emissão NFC-e (homolog-nac primeiro)';

    public function handle(RawNativeNfceIssuer $issuer): int
    {
        $empresa = Empresa::with('certificado')->find($this->argument('empresa'));
        if (! $empresa) {
            $this->error('Empresa não encontrada.');

            return self::FAILURE;
        }

        $valor = (float) $this->option('valor');
        $profile = (string) $this->option('profile');

        if ($profile === 'producao' && ! $this->option('force')) {
            $this->error('Perfil producao bloqueado. Use --force para confirmar emissão real.');

            return self::FAILURE;
        }

        [$numero, $serie, $ambiente] = $issuer->reservarNumero($empresa);

        $payload = [
            'itens' => [[
                'descricao' => 'NOTA FISCAL EMITIDA EM AMBIENTE DE HOMOLOGACAO - SEM VALOR FISCAL',
                'ncm' => '22021000',
                'cfop' => '5102',
                'csosn' => '102',
                'unidade' => 'UN',
                'quantidade' => 1,
                'valor_unitario' => $valor,
                'pis_cst' => '49',
                'cofins_cst' => '49',
            ]],
            'pagamentos' => [[
                't_pag' => '01',
                'v_pag' => $valor,
                'v_troco' => null,
            ]],
            'dest_doc' => null,
            'dest_nome' => null,
            'natureza' => 'VENDA',
            'endpoint_profile' => $profile,
        ];

        $nfce = Nfce::create([
            'empresa_id' => $empresa->id,
            'numero' => $numero,
            'serie' => $serie,
            'ambiente' => $ambiente,
            'tp_emis' => 1,
            'status' => 'processando',
            'payload' => $payload,
            'valor_total' => $valor,
        ]);

        $this->info("NFC-e #{$nfce->id} número {$numero} série {$serie} (perfil {$profile})");

        if ($this->option('sync')) {
            try {
                $request = new NfceEmitRequest(
                    itens: [
                        new NfceItem(
                            descricao: $payload['itens'][0]['descricao'],
                            ncm: '22021000',
                            cfop: '5102',
                            unidade: 'UN',
                            quantidade: 1,
                            valorUnitario: $valor,
                        ),
                    ],
                    pagamentos: [new NfcePayment('01', $valor)],
                    numeroOverride: $numero,
                    endpointProfile: $profile,
                );
                $result = $issuer->emit($empresa, $request, $nfce);
                $this->info("Autorizada: chave {$result->chave} protocolo {$result->protocolo}");

                return self::SUCCESS;
            } catch (\Throwable $e) {
                $this->error($e->getMessage());

                return self::FAILURE;
            }
        }

        EmitirNfceJob::dispatch($nfce);
        $this->info('Job EmitirNfceJob despachado.');

        return self::SUCCESS;
    }
}
