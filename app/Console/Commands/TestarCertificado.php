<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\Empresa;

class TestarCertificado extends Command
{
    // O nome que você vai digitar no terminal
    protected $signature = 'teste:certificado {empresa_id}';
    protected $description = 'Testa se o sistema consegue ler e descriptografar o PFX da empresa';

    public function handle()
    {
        $id = $this->argument('empresa_id');
        $empresa = Empresa::find($id);

        if (!$empresa) {
            $this->error("Empresa ID $id não encontrada.");
            return;
        }

        $certificado = $empresa->certificado;

        if (!$certificado) {
            $this->error("Essa empresa não tem certificado cadastrado no banco.");
            return;
        }

        $this->info("1. Buscando arquivo...");

        // Verifica se o arquivo existe no disco
        // Nota: Ajuste o path conforme onde você salvou no CertificadoController
        // Se usou $path = $request->file(...)->store('certificados'), ele está em storage/app/certificados
        if (!Storage::exists($certificado->caminho_arquivo)) {
            $this->error("Arquivo não encontrado em: " . $certificado->caminho_arquivo);
            return;
        }

        $pfxContent = Storage::get($certificado->caminho_arquivo);
        $password = $certificado->senha;

        $this->info("2. Tentando desbloquear o PFX com a senha...");

        $certs = [];
        // Tenta ler o PFX
        if (!openssl_pkcs12_read($pfxContent, $certs, $password)) {
            $this->error("FALHA: Não foi possível ler o certificado. A senha está correta?");
            $this->error("Erro OpenSSL: " . openssl_error_string());
            return;
        }

        $this->info("SUCESSO: Certificado desbloqueado!");

        // Extrai dados públicos do certificado
        $dados = openssl_x509_parse($certs['cert']);

        $this->line("------------------------------------------------");
        $this->info("DADOS DO CERTIFICADO EXTRAÍDOS:");
        $this->line("Emitido para: " . ($dados['subject']['CN'] ?? 'Desconhecido'));
        $this->line("Válido de: " . date('d/m/Y H:i:s', $dados['validFrom_time_t']));
        $this->line("Válido até: " . date('d/m/Y H:i:s', $dados['validTo_time_t']));
        $this->line("Emissor: " . ($dados['issuer']['CN'] ?? 'Desconhecido'));
        $this->line("------------------------------------------------");

        // Verifica validade
        if (time() > $dados['validTo_time_t']) {
            $this->error("ATENÇÃO: Este certificado está VENCIDO!");
        } elseif (time() < $dados['validFrom_time_t']) {
            $this->error("ATENÇÃO: Este certificado ainda não é válido (Data futura).");
        } else {
            $this->info("STATUS: VÁLIDO e pronto para uso.");
        }
    }
}
