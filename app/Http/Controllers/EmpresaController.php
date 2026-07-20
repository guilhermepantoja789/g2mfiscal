<?php

namespace App\Http\Controllers;

use App\Exceptions\CertificadoA1Exception;
use App\Models\Empresa;
use App\Models\Certificado;
use App\Rules\CpfCnpj;
use App\Services\CertificadoA1Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session; // Importante: Use Facade

class EmpresaController extends Controller
{
    // ... (create e store mantidos iguais, pois não usam sessão) ...

    public function create()
    {
        return view('empresas.create');
    }

    public function store(Request $request)
    {
        // Limpa máscaras
        $input = $request->all();
        $input['cnpj'] = preg_replace('/\D/', '', $input['cnpj'] ?? '');
        $input['cep'] = preg_replace('/\D/', '', $input['cep'] ?? '');
        $request->replace($input);

        $request->validate([
            'cnpj' => ['required', 'unique:empresas,cnpj', new CpfCnpj],
            'razao_social' => 'required|string|max:255',
            'inscricao_municipal' => 'required|string|max:20',
            'regime_tributario' => 'required|integer',
            'cep' => 'required',
            'logradouro' => 'required',
            'numero' => 'required',
            'bairro' => 'required',
            'uf' => 'required|size:2',
            'cod_ibge_mun' => 'required',
            'email' => 'required|email',
        ]);

        DB::transaction(function () use ($request) {
            $empresa = Empresa::create([
                'user_id' => Auth::id(),
                'cnpj' => $request->cnpj,
                'razao_social' => strtoupper($request->razao_social),
                'nome_fantasia' => strtoupper($request->nome_fantasia),
                'inscricao_municipal' => $request->inscricao_municipal,

                'regime_tributario' => $request->regime_tributario,
                'regime_apuracao_sn' => ($request->regime_tributario == 3) ? ($request->regime_apuracao_sn ?? 1) : 0,
                'regime_especial_tributacao' => $request->regime_especial_tributacao ?? 0,

                'cep' => $request->cep,
                'logradouro' => strtoupper($request->logradouro),
                'numero' => $request->numero,
                'complemento' => strtoupper($request->complemento),
                'bairro' => strtoupper($request->bairro),
                'uf' => strtoupper($request->uf),
                'cod_ibge_mun' => $request->cod_ibge_mun,
                'email' => strtolower($request->email),
                'telefone' => $request->telefone,
            ]);

            Auth::user()->empresas()->attach($empresa->id, ['perfil' => 'admin']);
        });

        return redirect()->route('empresas.selecao')
            ->with('success', 'Empresa cadastrada com sucesso!');
    }

    public function configuracao(Empresa $empresa)
    {
        $this->authorizeAcesso($empresa);

        // Carrega o relacionamento
        $empresa->load('certificado', 'users');

        // Extrai o certificado para passar como variável independente para a view
        $certificado = $empresa->certificado;

        return view('empresas.configuracao', compact('empresa', 'certificado'));
    }

    public function edit(Empresa $empresa)
    {
        $this->authorizeAcesso($empresa);

        return view('empresas.edit', compact('empresa'));
    }

    public function update(Request $request, Empresa $empresa)
    {
        $this->authorizeAcesso($empresa);

        // 1. Limpeza de máscaras (CEP e Telefone)
        $input = $request->all();
        $input['cep'] = preg_replace('/\D/', '', $input['cep'] ?? '');
        // Opcional: limpar telefone se quiser salvar apenas números
        // $input['telefone'] = preg_replace('/\D/', '', $input['telefone'] ?? '');
        $request->replace($input);

        // 2. Validação Completa (Igual ao Create)
        $request->validate([
            'razao_social' => 'required|string|max:255',
            'nome_fantasia' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'telefone' => 'required|string|max:20',
            'cep' => 'required',
            'logradouro' => 'required',
            'numero' => 'required',
            'bairro' => 'required',
            'cod_ibge_mun' => 'required',
            'inscricao_municipal' => 'required',
            'regime_tributario' => 'required',
            'inscricao_estadual' => 'nullable|string|max:20',
            'crt' => 'nullable|integer|in:1,2,3',
            'nfce_serie' => 'nullable|integer|min:1|max:999',
            'nfce_csc_id' => 'nullable|string|max:10',
            'nfce_csc_token' => 'nullable|string|max:64',
            'nfce_ambiente' => 'nullable|integer|in:1,2',
            // Validação de certificado (opcional, caso use na config)
            'certificado_pfx' => 'nullable|file|mimes:pfx,p12|max:5120',
            'certificado_senha' => 'nullable|required_with:certificado_pfx|string',
        ]);

        // 3. Atualização dos Dados
        $empresa->update([
            'razao_social' => strtoupper($request->razao_social),
            'nome_fantasia' => strtoupper($request->nome_fantasia),
            'email' => strtolower($request->email),
            'telefone' => $request->telefone,

            // Endereço
            'cep' => $request->cep,
            'logradouro' => strtoupper($request->logradouro),
            'numero' => $request->numero,
            'complemento' => strtoupper($request->complemento),
            'bairro' => strtoupper($request->bairro),
            'uf' => strtoupper($request->uf ?? $empresa->uf),
            'cod_ibge_mun' => $request->cod_ibge_mun,

            // Fiscal
            'inscricao_municipal' => $request->inscricao_municipal,
            'regime_tributario' => $request->regime_tributario,
            'regime_apuracao_sn' => ($request->regime_tributario == 3) ? ($request->regime_apuracao_sn ?? 1) : 0,
            'regime_especial_tributacao' => $request->regime_especial_tributacao ?? 0,

            // NFC-e Amazonas
            'inscricao_estadual' => $request->filled('inscricao_estadual')
                ? preg_replace('/\D/', '', $request->inscricao_estadual)
                : $empresa->inscricao_estadual,
            'crt' => $request->input('crt', $empresa->crt),
            'nfce_serie' => $request->input('nfce_serie', $empresa->nfce_serie ?? 1),
            'nfce_csc_id' => $request->input('nfce_csc_id', $empresa->nfce_csc_id),
            'nfce_csc_token' => $request->filled('nfce_csc_token')
                ? $request->nfce_csc_token
                : $empresa->nfce_csc_token,
            'nfce_ambiente' => $request->input('nfce_ambiente', $empresa->nfce_ambiente ?? 2),
        ]);

        $mensagem = 'Empresa atualizada com sucesso!';

        // 4. Lógica de Certificado (Mantida caso você use esse update na tela de configuração também)
        if ($request->hasFile('certificado_pfx')) {
            try {
                $this->processarCertificado($request, $empresa);
                $mensagem = 'Dados e Certificado atualizados!';
            } catch (\Exception $e) {
                return back()->withErrors(['certificado_pfx' => 'Erro no certificado: ' . $e->getMessage()])->withInput();
            }
        }

        // Redireciona para a seleção para ver a lista atualizada
        return redirect()->route('empresas.selecao')
            ->with('success', $mensagem);
    }

    public function selecao()
    {
        $empresas = Auth::user()->empresas;
        return view('empresas.selecao', compact('empresas'));
    }

    public function entrar(Empresa $empresa)
    {
        $this->authorizeAcesso($empresa);

        // CORREÇÃO: Usando Facade e salvando APENAS O ID para evitar erros de objeto
        Session::put('empresa_ativa', $empresa->id);

        return redirect()->route('dashboard');
    }

    public function destroy(Empresa $empresa)
    {
        $this->authorizeAcesso($empresa);

        // CORREÇÃO: Recuperação segura via Facade
        $idSessao = Session::get('empresa_ativa');

        // Se o valor na sessão for um objeto (legado), tenta pegar o ID
        if (is_object($idSessao) && isset($idSessao->id)) {
            $idSessao = $idSessao->id;
        }

        if ($idSessao == $empresa->id) {
            Session::forget('empresa_ativa');
        }

        $empresa->delete();

        return redirect()->route('empresas.selecao')
            ->with('success', 'Empresa excluída.');
    }

    public function buscarImComCertificado(Request $request)
    {
        try {
            $empresa = \App\Models\Empresa::find(session('empresa_ativa'));

            if (!$empresa) {
                return response()->json(['erro' => 'Empresa não selecionada.'], 404);
            }

            // Validação crítica: Tem certificado?
            if (!$empresa->certificado || !$empresa->certificado->ativo) {
                return response()->json([
                    'erro' => 'Certificado Digital não encontrado. Faça o upload abaixo primeiro.'
                ], 400);
            }

            // Chama o serviço
            $service = new \App\Services\NfseNacionalService($empresa);
            $resultado = $service->consultarImViaCnc();

            // Salva automaticamente no banco
            $empresa->inscricao_municipal = $resultado['im'];
            $empresa->save();

            return response()->json([
                'sucesso' => true,
                'im' => $resultado['im'],
                'mensagem' => "Inscrição Municipal {$resultado['im']} encontrada e salva! (Situação: {$resultado['situacao']})"
            ]);

        } catch (\Exception $e) {
            return response()->json(['erro' => $e->getMessage()], 500);
        }
    }

    private function processarCertificado(Request $request, Empresa $empresa)
    {
        $file = $request->file('certificado_pfx');
        $password = $request->input('certificado_senha');
        $pfxContent = file_get_contents($file->getRealPath());

        if ($pfxContent === false) {
            throw new \Exception('Não foi possível ler o arquivo do certificado.');
        }

        try {
            $result = app(CertificadoA1Service::class)->read($pfxContent, (string) $password);
        } catch (CertificadoA1Exception $e) {
            throw new \Exception($e->getMessage(), previous: $e);
        }

        $path = 'certificados/'.uniqid('cert_'.$empresa->id.'_', true).'.pfx';
        Storage::put($path, $result->pfxContent);

        Certificado::where('empresa_id', $empresa->id)->update(['ativo' => false]);

        Certificado::create([
            'empresa_id' => $empresa->id,
            'nome_arquivo' => $path,
            'nome_original' => $file->getClientOriginalName(),
            'senha' => $password,
            'valido_ate' => $result->validoAte->format('Y-m-d H:i:s'),
            'ativo' => true,
        ]);
    }

    private function authorizeAcesso($empresa)
    {
        if (!Auth::user()->empresas->contains($empresa->id)) {
            abort(403, 'Acesso não autorizado a esta empresa.');
        }
    }
}
