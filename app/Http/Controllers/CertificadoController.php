<?php

namespace App\Http\Controllers;

use App\Exceptions\CertificadoA1Exception;
use App\Models\Empresa;
use App\Services\CertificadoA1Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificadoController extends Controller
{
    public function __construct(
        protected CertificadoA1Service $certificadoA1,
    ) {}

    public function index()
    {
        $empresaId = session('empresa_ativa');
        $empresa = Empresa::findOrFail($empresaId);
        $certificado = $empresa->certificado;

        return view('empresas.configuracao', compact('empresa', 'certificado'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'arquivo' => 'required|file|max:2048',
            'senha' => 'required|string',
        ]);

        $extensao = strtolower($request->file('arquivo')->getClientOriginalExtension());
        if (! in_array($extensao, ['pfx', 'p12'], true)) {
            return back()->withErrors(['arquivo' => 'O arquivo deve ter extensão .pfx ou .p12']);
        }

        try {
            $pfxContent = file_get_contents($request->file('arquivo')->getRealPath());
            if ($pfxContent === false) {
                return back()->withErrors(['arquivo' => 'Não foi possível ler o arquivo enviado.']);
            }

            $result = $this->certificadoA1->read($pfxContent, $request->senha);

            $empresa = Empresa::findOrFail(session('empresa_ativa'));

            $caminho = 'certificados/'.uniqid('cert_'.$empresa->id.'_', true).'.pfx';
            Storage::put($caminho, $result->pfxContent);

            $certificadoAnterior = $empresa->certificado;
            if ($certificadoAnterior?->nome_arquivo && $certificadoAnterior->nome_arquivo !== $caminho) {
                Storage::delete($certificadoAnterior->nome_arquivo);
            }

            $empresa->certificado()->updateOrCreate(
                ['empresa_id' => $empresa->id],
                [
                    'nome_arquivo' => $caminho,
                    'nome_original' => $request->file('arquivo')->getClientOriginalName(),
                    'senha' => $request->senha,
                    'ativo' => true,
                    'valido_ate' => $result->validoAte->format('Y-m-d H:i:s'),
                ]
            );

            $mensagem = 'Certificado salvo! Válido até '.$result->validoAte->format('d/m/Y').'.';
            if ($result->convertedFromLegacy) {
                $mensagem .= ' O arquivo usava criptografia legada e foi convertido automaticamente para um formato compatível com OpenSSL 3.';
            }

            return redirect()->route('empresas.configuracao', $empresa->id)
                ->with('success', $mensagem);
        } catch (CertificadoA1Exception $e) {
            return back()->withErrors([$e->formField() => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['arquivo' => 'Erro ao processar certificado: '.$e->getMessage()]);
        }
    }
}
