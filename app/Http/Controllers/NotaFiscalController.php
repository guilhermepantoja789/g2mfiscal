<?php

namespace App\Http\Controllers;

use App\Models\NotaFiscal;
use App\Services\NfseNacionalService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class NotaFiscalController extends Controller
{
    public function index(Request $request)
    {
        $query = \App\Models\NotaFiscal::where('empresa_id', session('empresa_ativa'));

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('tomador_nome', 'like', "%{$search}%")
                    ->orWhere('tomador_cnpj', 'like', "%{$search}%")
                    ->orWhere('numero_nfse', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $notas = $query->latest()->paginate(10)->withQueryString();

        return view('notas.index', compact('notas'));
    }

    public function create()
    {
        $empresaId = session('empresa_ativa');
        $empresa = \App\Models\Empresa::find($empresaId);

        if (!$empresa->certificado || !$empresa->certificado->ativo) {
            return redirect()->route('empresas.configuracao')
                ->withErrors(['erro' => 'Configure seu certificado antes de emitir.']);
        }

        $servicos = \App\Models\Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = \App\Models\Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();

        return view('notas.criar', compact('servicos', 'clientes'));
    }

    public function store(Request $request)
    {
        // 1. Validação Completa
        $validated = $request->validate([
            'tomador_cnpj'   => 'required|numeric|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:1',
            'codigo_servico' => 'required|string',
            'descricao'      => 'required|string',

            // Campos do Tomador
            'tomador_email'       => 'nullable|email',
            'tomador_telefone'    => 'nullable|string',
            'tomador_im'          => 'nullable|string',
            'tomador_cep'         => 'nullable|string',
            'tomador_endereco'    => 'nullable|string',
            'tomador_numero'      => 'nullable|string',
            'tomador_complemento' => 'nullable|string',
            'tomador_bairro'      => 'nullable|string',
            'tomador_uf'          => 'nullable|string|size:2',
            'tomador_cidade'      => 'nullable|string',

            // Dados do Serviço
            'municipio_prestacao' => 'nullable|string',
            'tributacao_iss'      => 'nullable|integer',
            'iss_retido'          => 'nullable'
        ]);

        $empresa = \App\Models\Empresa::find(session('empresa_ativa'));

        if (!$empresa->certificado || !$empresa->certificado->ativo) {
            return back()->withErrors(['erro' => 'Certificado digital não configurado.']);
        }

        // 2. ATUALIZA OU CRIA O CLIENTE COM DADOS COMPLETOS
        // Isso garante que da próxima vez o select tenha os dados
        // e que o PDF consiga pegar o endereço pelo relacionamento
        $clienteDados = [
            'razao_social'        => $validated['tomador_nome'],
            'email'               => $request->tomador_email,
            'telefone'            => $request->tomador_telefone,
            'inscricao_municipal' => $request->tomador_im,
            'cep'                 => $request->tomador_cep,
            'logradouro'          => $request->tomador_endereco,
            'numero'              => $request->tomador_numero,
            'complemento'         => $request->tomador_complemento,
            'bairro'              => $request->tomador_bairro,
            'uf'                  => $request->tomador_uf,
            'cidade_codigo'       => $request->tomador_cidade,
        ];

        $cliente = \App\Models\Cliente::updateOrCreate(
            ['empresa_id' => $empresa->id, 'cnpj' => $validated['tomador_cnpj']],
            $clienteDados
        );

        // 3. CÁLCULO DE VALORES
        $aliquota = 5.00; // Poderia vir do serviço ou da empresa
        $valorServico = $validated['valor_servico'];
        $valorIss = $valorServico * ($aliquota / 100);
        $valorLiquido = $valorServico - $valorIss;

        // 4. SALVAR A NOTA
        $nota = \App\Models\NotaFiscal::create([
            'empresa_id'     => $empresa->id,
            'cliente_id'     => $cliente->id,
            'status'         => 'processando',
            'numero_nfse'    => null,
            'ambiente'       => config('app.env') === 'production' ? 'producao' : 'homologacao',
            'tomador_cnpj'   => $validated['tomador_cnpj'],
            'tomador_nome'   => $validated['tomador_nome'],
            'tomador_email'  => $request->tomador_email,
            'codigo_servico' => $validated['codigo_servico'],
            'descricao'      => $validated['descricao'],
            'valor_servico'  => $valorServico,
            'aliquota_iss'   => $aliquota,
            'valor_iss'      => $valorIss,
            'valor_liquido'  => $valorLiquido,
        ]);

        // 5. EMISSÃO NA API NACIONAL
        try {
            $nfseService = new NfseNacionalService($empresa);

            // Passamos TODOS os dados para o serviço XML
            $dadosEmissao = [
                'numero'          => $nota->id,
                'serie'           => '1',
                'competencia'     => date('Y-m-d'),
                'tomador_doc'     => $cliente->cnpj,
                'tomador_nome'    => $cliente->razao_social,
                'tomador_endereco'      => $cliente->logradouro,
                'tomador_numero'        => $cliente->numero,
                'tomador_complemento'   => $cliente->complemento,
                'tomador_bairro'        => $cliente->bairro,
                'tomador_cep'           => $cliente->cep,
                'tomador_uf'            => $cliente->uf,
                'tomador_cidade_codigo' => $cliente->cidade_codigo,
                'tomador_telefone'      => $cliente->telefone,
                'tomador_email'         => $cliente->email,

                'servico_nbs'       => '010501',
                'servico_municipal' => $validated['codigo_servico'],
                'discriminacao'     => $validated['descricao'],
                'valor'             => $valorServico,
                'tributacao_iss'    => $request->tributacao_iss ?? 1,
                'retencao_iss'      => $request->has('iss_retido') ? '2' : '1',
            ];

            $retorno = $nfseService->emitirNota($dadosEmissao);

            if ($retorno['sucesso']) {
                $nota->update([
                    'status'             => 'autorizada',
                    'numero_nfse'        => $retorno['numero_nota'] ?? null,
                    'codigo_verificacao' => $retorno['codigo_verificacao'] ?? null,
                    'link_pdf'           => $retorno['link_pdf'] ?? null,
                    'xml_autorizado'     => $retorno['xml_autorizado'] ?? ($retorno['xml_nacional'] ?? null),
                    'xml_enviado'        => $retorno['xml_dps_enviado'] ?? null,
                    'mensagem_erro'      => null
                ]);

                return redirect()->route('notas.show', $nota->id)
                    ->with('success', 'Nota Fiscal emitida! Nº ' . ($retorno['numero_nota'] ?? 'S/N'));
            } else {
                $msgErro = is_array($retorno['erros'] ?? null)
                    ? implode(' | ', $retorno['erros'])
                    : ($retorno['mensagem'] ?? 'Erro desconhecido');

                $nota->update([
                    'status'        => 'erro',
                    'mensagem_erro' => $msgErro,
                    'xml_enviado'   => $retorno['xml_dps_enviado'] ?? null
                ]);

                return redirect()->route('notas.show', $nota->id)->withErrors(['erro' => $msgErro]);
            }

        } catch (\Exception $e) {
            $nota->update(['status' => 'erro', 'mensagem_erro' => $e->getMessage()]);
            return redirect()->route('notas.show', $nota->id)->with('error', $e->getMessage());
        }
    }

    public function show($id)
    {
        $nota = \App\Models\NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->with('cliente')
            ->findOrFail($id);

        return view('notas.detalhe', compact('nota'));
    }

    public function imprimir($id)
    {
        // 1. BUSCA DADOS
        $nota = \App\Models\NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);
        $empresaIdSessao = session('empresa_ativa');

        if (!$empresaIdSessao) {
            return redirect()->route('dashboard')->withErrors(['erro' => 'Sessão expirada.']);
        }

        if ($nota->empresa_id != $empresaIdSessao) {
            if (empty($nota->empresa_id)) {
                $nota->empresa_id = $empresaIdSessao;
                $nota->save();
            } else {
                abort(403, "Permissão negada.");
            }
        }

        $empresa = $nota->empresa ?? \App\Models\Empresa::find($empresaIdSessao);
        $cliente = $nota->cliente; // Agora o cliente estará completo

        $xmlObject = null;
        $chaveAcesso = null;
        $dataEmissao = $nota->created_at;

        // --- TRATAMENTO DO XML E CHAVE ---
        if ($nota->status === 'autorizada' && !empty($nota->xml_autorizado)) {
            try {
                $content = $nota->xml_autorizado;
                if (str_starts_with($content, "\x1f\x8b")) {
                    $content = gzdecode($content);
                } elseif (!str_starts_with(trim($content), '<')) {
                    $decoded = base64_decode($content, true);
                    if ($decoded && str_starts_with($decoded, "\x1f\x8b")) $content = gzdecode($decoded);
                    elseif ($decoded) $content = $decoded;
                }

                $xmlObject = simplexml_load_string(str_replace(['ns1:', 'nfse:'], '', $content));

                // Regex para Chave
                if (preg_match('/<chvAcesso>(.*?)<\/chvAcesso>/', $content, $matches)) {
                    $chaveAcesso = $matches[1];
                } elseif (preg_match('/Id="NFS([0-9]{50})"/', $content, $matches)) {
                    $chaveAcesso = $matches[1];
                }

                // Regex para Data
                if (preg_match('/<dhEmi>(.*?)<\/dhEmi>/', $content, $matches)) {
                    $dataEmissao = new \DateTime($matches[1]);
                }
            } catch (\Exception $e) { }
        }

        // --- QR CODE (Versão HTTP Client do Laravel) ---
        $qrBase64 = null;
        $fallbackImage = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=';

        if (!empty($chaveAcesso)) {
            try {
                $urlConsulta = "https://www.nfse.gov.br/ConsultaPublica/";
                $qrLink = "{$urlConsulta}?tpc=1&chave={$chaveAcesso}";

                $apiUrl = "https://quickchart.io/qr?text=" . urlencode($qrLink) . "&size=300&ecLevel=M&margin=1";

                $response = Http::withOptions(['verify' => false])->timeout(5)->get($apiUrl);

                if ($response->successful()) {
                    $qrBase64 = 'data:image/png;base64,' . base64_encode($response->body());
                } else {
                    $qrBase64 = $fallbackImage;
                }
            } catch (\Exception $e) {
                $qrBase64 = $fallbackImage;
            }
        } else {
            $chaveAcesso = 'PENDENTE';
            $qrBase64 = $fallbackImage;
        }

        // --- OBJETOS PARA O PDF ---
        $emitente = (object) [
            'razao_social' => $empresa->razao_social,
            'cnpj' => $empresa->cnpj,
            'inscricao_municipal' => $empresa->inscricao_municipal,
            'endereco' => $empresa->logradouro,
            'numero' => $empresa->numero,
            'complemento' => $empresa->complemento,
            'bairro' => $empresa->bairro,
            'cidade' => 'Manaus',
            'uf' => $empresa->uf,
            'cep' => $empresa->cep,
            'telefone' => $empresa->telefone,
            'email' => $empresa->email,
            'regime_tributario' => 'Simples Nacional'
        ];

        // Tomador usando dados do Cliente do banco (agora completo)
        $tomador = (object) [
            'razao_social'        => $cliente?->razao_social ?? $nota->tomador_nome,
            'documento'           => $cliente?->cnpj ?? $nota->tomador_cnpj,
            'inscricao_municipal' => $cliente?->inscricao_municipal ?? '',
            'endereco'            => $cliente?->logradouro ?? '',
            'numero'              => $cliente?->numero ?? '',
            'complemento'         => $cliente?->complemento ?? '',
            'bairro'              => $cliente?->bairro ?? '',
            'cidade'              => $cliente?->cidade_codigo ?? '',
            'uf'                  => $cliente?->uf ?? '',
            'cep'                 => $cliente?->cep ?? '',
            'email'               => $cliente?->email ?? ($nota->tomador_email ?? ''),
            'telefone'            => $cliente?->telefone ?? ''
        ];

        $dadosNota = (object) [
            'numero' => $nota->numero_nfse,
            'serie' => '1',
            'chave' => $chaveAcesso,
            'data_emissao' => $dataEmissao,
            'codigo_verificacao' => $nota->codigo_verificacao,
            'competencia' => $dataEmissao,
            'local_prestacao' => 'Manaus/AM'
        ];

        $dadosServico = (object) [
            'discriminacao' => $nota->descricao,
            'codigo_nbs' => '01.05.01',
            'codigo_cnae' => '',
            'item_lista_servico' => $nota->codigo_servico,
            'valor_servico' => (float)$nota->valor_servico,
            'valor_deducoes' => 0.00,
            'valor_pis' => 0.00,
            'valor_cofins' => 0.00,
            'valor_inss' => 0.00,
            'valor_ir' => 0.00,
            'valor_csll' => 0.00,
            'iss_retido' => 2,
            'valor_iss' => (float)$nota->valor_iss,
            'valor_liquido' => (float)$nota->valor_liquido,
            'aliquota_iss' => (float)$nota->aliquota_iss
        ];

        $outras_informacoes = "Documento emitido por ME ou EPP optante pelo Simples Nacional.";

        $pdf = Pdf::loadView('pdf.danfse', [
            'emitente' => $emitente,
            'tomador'  => $tomador,
            'nota'     => $dadosNota,
            'servico'  => $dadosServico,
            'outras_informacoes' => $outras_informacoes,
            'xml'      => $xmlObject,
            'chaveAcesso' => $chaveAcesso,
            'qrCodeBase64' => $qrBase64
        ]);

        return $pdf->stream("NFSe-{$nota->numero_nfse}.pdf");
    }

    /**
     * Faz o download do DANFSe oficial direto da API Nacional
     */
    public function baixarDanfseOficial($id)
    {
        $nota = \App\Models\NotaFiscal::where('empresa_id', session('empresa_ativa'))
            ->findOrFail($id);

        // Verificação de segurança da empresa
        if ($nota->empresa_id != session('empresa_ativa')) {
            abort(403);
        }

        if ($nota->status !== 'autorizada' || empty($nota->xml_autorizado)) {
            return back()->withErrors(['erro' => 'Esta nota não possui XML autorizado para gerar o DANFSe.']);
        }

        // 1. Tenta obter a chave de acesso (Prioridade: Coluna no banco -> Extração do XML)
        $chaveAcesso = $nota->chave_acesso ?? null;

        if (empty($chaveAcesso)) {
            // Lógica de extração segura do XML (Reutilizando a lógica do seu método imprimir)
            $content = $nota->xml_autorizado;

            // Decodifica GZIP/Base64 se necessário
            if (str_starts_with($content, "\x1f\x8b")) {
                $content = gzdecode($content);
            } elseif (!str_starts_with(trim($content), '<')) {
                $decoded = base64_decode($content, true);
                if ($decoded && str_starts_with($decoded, "\x1f\x8b")) $content = gzdecode($decoded);
                elseif ($decoded) $content = $decoded;
            }

            // Busca a tag <chvAcesso> ou atributo Id
            if (preg_match('/<chvAcesso>(.*?)<\/chvAcesso>/', $content, $matches)) {
                $chaveAcesso = $matches[1];
            } elseif (preg_match('/Id="NFS([0-9]{50})"/', $content, $matches)) {
                $chaveAcesso = $matches[1];
            }
        }

        if (empty($chaveAcesso)) {
            return back()->withErrors(['erro' => 'Não foi possível identificar a Chave de Acesso desta nota.']);
        }

        try {
            // 2. Chama o Service para baixar o PDF do Governo
            $service = new NfseNacionalService($nota->empresa);
            $pdfContent = $service->downloadDanfse($chaveAcesso);

            // 3. Retorna o PDF para o navegador
            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="DANFSe_Oficial_' . $nota->numero_nfse . '.pdf"');

        } catch (\Exception $e) {
            return back()->withErrors(['erro' => 'Erro ao baixar do governo: ' . $e->getMessage()]);
        }
    }
}
