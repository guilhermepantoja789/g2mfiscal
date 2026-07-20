<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Empresa;
use Illuminate\Support\Facades\Http;

class ConsultarCnc extends Command
{
    protected $signature = 'teste:consultar_cnc {empresa_id}';
    protected $description = 'Consulta o cadastro da empresa no CNC Nacional para descobrir a IM correta';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (!$empresa || !$empresa->certificado) {
            $this->error("Empresa ou certificado não encontrados.");
            return;
        }

        $this->info("1. Preparando certificado...");
        try {
            $result = app(\App\Services\CertificadoA1Service::class)
                ->loadFromModel($empresa->certificado);
            $tempPemPath = tempnam(sys_get_temp_dir(), 'cert_cnc_') . '.pem';
            file_put_contents($tempPemPath, $result->toPem());
        } catch (\Exception $e) {
            $this->error($e->getMessage()); return;
        }

        // Dados para consulta
        $codMun = '1302603'; // Manaus
        $cnpj = preg_replace('/[^0-9]/', '', $empresa->cnpj); // 09279540000120

        $url = config('services.nfse_nacional.url_adn') . '/cnc/consulta/cad';

        $this->info("2. Consultando CNC para CNPJ: $cnpj em Manaus ($codMun)...");

        try {
            $response = Http::withOptions([
                'cert' => $tempPemPath,
                'verify' => false,
            ])->get($url, [
                'codMunicipio' => $codMun,
                'inscricaoFederal' => $cnpj,
                // 'indicadorMunicipal' => '' // Opcional
            ]);

            $body = $response->json();

            if ($response->ok()) {
                $this->info("SUCESSO! Cadastro Encontrado:");

                if (!empty($body['ListaCadastroMunicipal'])) {
                    foreach ($body['ListaCadastroMunicipal'] as $idx => $cad) {
                        $im = $cad['InfCad']['InscricaoMunicipal'] ?? 'N/A';
                        $situacao = $cad['InfCad']['SituacaoEmissaoNFSe'] ?? 'N/A';
                        $razao = $cad['InfCad']['RazaoSocial'] ?? 'N/A';

                        $this->line("-----------------------------------------");
                        $this->info("Cadastro #{$idx}");
                        $this->info("RAZÃO SOCIAL: $razao");
                        $this->comment("INSCRIÇÃO MUNICIPAL (IM): $im");
                        $this->info("SITUAÇÃO: $situacao");

                        if ($situacao == 'HABILITADO' || $situacao == 'ATIVO') {
                            $this->alert(">>> USE ESTA IM NO SEU BANCO DE DADOS: $im <<<");
                        }
                    }
                } else {
                    $this->warn("Nenhum cadastro retornado na lista.");
                }
            } else {
                $this->error("Erro na consulta: " . $response->status());
                $this->line($response->body());
            }

        } catch (\Exception $e) {
            $this->error("Falha: " . $e->getMessage());
        } finally {
            @unlink($tempPemPath);
        }
    }
}
