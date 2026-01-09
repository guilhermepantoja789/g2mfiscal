<?php

namespace App\Http\Controllers;

use App\Models\Certificado;
use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CertificadoController extends Controller
{
    // Tela de Configuração
    public function index()
    {
        $empresaId = session('empresa_ativa');
        $empresa = Empresa::findOrFail($empresaId);

        // Carrega o certificado atual (se existir)
        $certificado = $empresa->certificado;

        return view('empresas.configuracao', compact('empresa', 'certificado'));
    }

    // Processar Upload
    public function store(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:2048',
            'senha' => 'required|string',
        ]);

        $extensao = strtolower($request->file('arquivo')->getClientOriginalExtension());
        if (!in_array($extensao, ['pfx', 'p12'])) {
            return back()->withErrors(['arquivo' => 'O arquivo deve ter extensão .pfx ou .p12']);
        }

        try {
            // 1. Tenta ler o certificado ANTES de salvar (Validação de Senha)
            $pfxContent = file_get_contents($request->file('arquivo')->getRealPath());
            $certs = [];

            if (!openssl_pkcs12_read($pfxContent, $certs, $request->senha)) {
                return back()->withErrors(['senha' => 'A senha informada está incorreta ou o arquivo está corrompido.']);
            }

            // 2. Extrai a data de validade
            $dados = openssl_x509_parse($certs['cert']);
            $validade = date('Y-m-d H:i:s', $dados['validTo_time_t']);

            // 3. Salva o arquivo no disco
            $caminho = $request->file('arquivo')->store('certificados');

            // 4. Salva no banco com a data extraída
            $empresa = \App\Models\Empresa::find(session('empresa_ativa'));

            $empresa->certificado()->updateOrCreate(
                ['empresa_id' => $empresa->id],
                [
                    'nome_arquivo' => $caminho,
                    'senha' => $request->senha,
                    'ativo' => true,
                    'valido_ate' => $validade // <--- AQUI ESTÁ A MÁGICA
                ]
            );

            return redirect()->route('empresas.configuracao', $empresa->id)
                ->with('success', "Certificado salvo! Válido até " . date('d/m/Y', $dados['validTo_time_t']));

        } catch (\Exception $e) {
            return back()->withErrors(['arquivo' => 'Erro ao processar certificado: ' . $e->getMessage()]);
        }
    }
}
