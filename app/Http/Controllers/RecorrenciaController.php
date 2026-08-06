<?php

namespace App\Http\Controllers;

use App\Models\Recorrencia;
use App\Models\NotaFiscal;
use App\Models\Empresa;
use App\Models\Servico;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecorrenciaController extends Controller
{
    /**
     * Exibe o Calendário e a Lista de Recorrências
     */
    public function index(Request $request)
    {
        $empresaId = session('empresa_ativa');

        // Data base para o calendário (default: mês atual)
        $dataBase = $request->get('mes_ano')
            ? Carbon::createFromFormat('Y-m', $request->get('mes_ano'))
            : Carbon::now();

        // 1. LISTA DE RECORRÊNCIAS ATIVAS
        $recorrencias = Recorrencia::where('empresa_id', $empresaId)
            ->with('cliente')
            ->orderBy('proxima_execucao')
            ->get();

        // 2. MONTAGEM DOS DADOS DO CALENDÁRIO
        $eventos = [];

        // A) Eventos Passados (Notas já emitidas)
        $notasDoMes = NotaFiscal::where('empresa_id', $empresaId)
            ->whereMonth('emissao', $dataBase->month)
            ->whereYear('emissao', $dataBase->year)
            ->get();

        foreach ($notasDoMes as $nota) {
            $eventos[] = [
                'id' => $nota->id,
                'titulo' => $nota->tomador_nome,
                'data' => $nota->emissao->format('Y-m-d'),
                'tipo' => 'nota',
                'status' => $nota->status,
                'valor' => $nota->valor_servico
            ];
        }

        // B) Eventos Futuros (Projeção Inteligente)
        // Precisamos projetar a recorrência para ver se ela cai neste mês visualizado
        $inicioMesView = $dataBase->copy()->startOfMonth();
        $fimMesView = $dataBase->copy()->endOfMonth();

        foreach ($recorrencias as $rec) {
            if (!$rec->ativo) continue;

            // Começamos a projeção a partir da próxima execução gravada no banco
            $dataSimulada = Carbon::parse($rec->proxima_execucao);

            // Se a próxima execução já passou (ex: cron não rodou), consideramos ela
            // Mas o loop deve continuar avançando até passar do fim do mês visualizado

            // LOOP DE PROJEÇÃO: Enquanto a data simulada for menor ou igual ao fim do mês que estamos vendo
            while ($dataSimulada->lte($fimMesView)) {

                // Verificamos se data_fim do contrato existe e se já passamos dela
                if ($rec->data_fim && $dataSimulada->gt($rec->data_fim)) {
                    break;
                }

                // Se a data cair DENTRO do mês visualizado, adicionamos ao evento
                if ($dataSimulada->month == $dataBase->month && $dataSimulada->year == $dataBase->year) {
                    $eventos[] = [
                        'id' => $rec->id,
                        'titulo' => 'Agendado: ' . $rec->tomador_nome,
                        'data' => $dataSimulada->format('Y-m-d'),
                        'tipo' => 'agendamento',
                        'status' => 'aguardando',
                        'valor' => $rec->valor_servico,
                        'automatico' => $rec->emitir_automaticamente
                    ];
                }

                // Avança para a próxima data baseada na frequência
                switch ($rec->frequencia) {
                    case 'mensal': $dataSimulada->addMonth(); break;
                    case 'semanal': $dataSimulada->addWeek(); break;
                    case 'anual': $dataSimulada->addYear(); break;
                    case 'unico':
                        // Se for único, incrementamos algo impossível para sair do loop logo após a primeira verificação
                        $dataSimulada->addYears(100);
                        break;
                }
            }
        }

        return view('recorrencias.index', compact('recorrencias', 'eventos', 'dataBase'));
    }

    /**
     * Formulário de Criação (Pode vir com data pré-selecionada do calendário)
     */
    public function create(Request $request)
    {
        $empresaId = session('empresa_ativa');

        // Se o usuário clicou numa data do calendário
        $dataPreSelecionada = $request->get('data');

        $servicos = Servico::where('empresa_id', $empresaId)->orderBy('nome')->get();
        $clientes = Cliente::where('empresa_id', $empresaId)->orderBy('razao_social')->get();

        return view('recorrencias.criar', compact('servicos', 'clientes', 'dataPreSelecionada'));
    }

    /**
     * Salvar a Nova Regra
     */
    public function store(Request $request)
    {
        $empresaId = session('empresa_ativa');
        $data = $request->all();

        // Limpeza de máscaras (Adicionado tomador_cnpj aqui por segurança)
        $camposMonetarios = ['valor_servico', 'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun'];
        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                $data[$campo] = str_replace('.', '', $data[$campo]); // Tira ponto de milhar
                $data[$campo] = str_replace(',', '.', $data[$campo]); // Troca vírgula por ponto
            } else {
                $data[$campo] = 0;
            }
        }

        if(!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }
        $request->replace($data);

        $request->validate([
            'tomador_nome' => 'required',
            'valor_servico' => 'required|numeric|min:0.01',
            'proxima_execucao' => 'required|date|after_or_equal:today',
            'frequencia' => 'required',
        ]);

        // Lógica de Cliente
        $clienteId = $request->cliente_id;
        if (!$clienteId && !empty($data['tomador_cnpj'])) {
            $cliente = Cliente::updateOrCreate(
                ['empresa_id' => $empresaId, 'cnpj' => $data['tomador_cnpj']],
                [
                    'razao_social' => $data['tomador_nome'],
                    'email' => $data['tomador_email'] ?? null,
                    'inscricao_municipal' => $data['tomador_im'] ?? null,
                    'telefone' => $data['tomador_telefone'] ?? null,
                    'cep' => $data['tomador_cep'] ?? null,
                    'logradouro' => $data['tomador_endereco'] ?? null,
                    'numero' => $data['tomador_numero'] ?? null,
                    'complemento' => $data['tomador_complemento'] ?? null,
                    'bairro' => $data['tomador_bairro'] ?? null,
                    'cidade_codigo' => $data['tomador_cidade'] ?? null,
                    'uf' => $data['tomador_uf'] ?? null,
                ]
            );
            $clienteId = $cliente->id;
        } elseif ($clienteId) {
            $cliente = Cliente::where('empresa_id', $empresaId)->find($clienteId);
            if ($cliente) {
                $cliente->update([
                    'razao_social' => $data['tomador_nome'] ?? $cliente->razao_social,
                    'email' => $data['tomador_email'] ?? $cliente->email,
                    'inscricao_municipal' => $data['tomador_im'] ?? $cliente->inscricao_municipal,
                    'telefone' => $data['tomador_telefone'] ?? $cliente->telefone,
                    'cep' => $data['tomador_cep'] ?? $cliente->cep,
                    'logradouro' => $data['tomador_endereco'] ?? $cliente->logradouro,
                    'numero' => $data['tomador_numero'] ?? $cliente->numero,
                    'complemento' => $data['tomador_complemento'] ?? $cliente->complemento,
                    'bairro' => $data['tomador_bairro'] ?? $cliente->bairro,
                    'cidade_codigo' => $data['tomador_cidade'] ?? $cliente->cidade_codigo,
                    'uf' => $data['tomador_uf'] ?? $cliente->uf,
                ]);
            }
        }

        // Salvar Recorrência (CORRIGIDO: INCLUINDO TODOS OS CAMPOS)
        Recorrencia::create([
            'empresa_id' => $empresaId,
            'cliente_id' => $clienteId,
            'servico_id' => $request->servico_id,
            'descricao_recorrencia' => $request->descricao_recorrencia ?? 'Agendamento para ' . $data['tomador_nome'],
            'frequencia' => $request->frequencia,
            'proxima_execucao' => $request->proxima_execucao,
            'data_fim' => $request->data_fim,
            'ativo' => true,
            'emitir_automaticamente' => $request->has('emitir_automaticamente'),

            // Dados Tomador
            'tomador_cnpj' => $data['tomador_cnpj'],
            'tomador_nome' => $data['tomador_nome'],
            'tomador_email' => $data['tomador_email'],
            'tomador_telefone' => $data['tomador_telefone'] ?? null,
            'tomador_im' => $data['tomador_im'] ?? null,
            'tomador_cep' => $data['tomador_cep'] ?? null,
            'tomador_endereco' => $data['tomador_endereco'] ?? null,
            'tomador_numero' => $data['tomador_numero'] ?? null,
            'tomador_complemento' => $data['tomador_complemento'] ?? null,
            'tomador_bairro' => $data['tomador_bairro'] ?? null,
            'tomador_cidade' => $data['tomador_cidade'] ?? null,
            'tomador_uf' => $data['tomador_uf'] ?? null,

            // Valores e Impostos (CORRIGIDO)
            'valor_servico' => $data['valor_servico'],
            'descricao_servico' => $request->descricao_servico,
            'trib_issqn' => $request->trib_issqn ?? 1,
            'tp_ret_issqn' => $request->tp_ret_issqn ?? 1,

            'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
            'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
            'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,
        ]);

        return redirect()->route('recorrencias.index')
            ->with('success', 'Agendamento criado com sucesso!');
    }

    /**
     * Editar Recorrência
     */
    public function edit($id)
    {
        $recorrencia = Recorrencia::where('empresa_id', session('empresa_ativa'))->findOrFail($id);
        $servicos = Servico::where('empresa_id', session('empresa_ativa'))->get();
        $clientes = Cliente::where('empresa_id', session('empresa_ativa'))->get();

        return view('recorrencias.editar', compact('recorrencia', 'servicos', 'clientes'));
    }

    /**
     * Atualizar
     */
    public function update(Request $request, $id)
    {
        $recorrencia = Recorrencia::where('empresa_id', session('empresa_ativa'))->findOrFail($id);
        $data = $request->all();

        // Limpeza de máscaras
        $camposMonetarios = ['valor_servico', 'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun'];
        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                $data[$campo] = str_replace('.', '', $data[$campo]);
                $data[$campo] = str_replace(',', '.', $data[$campo]);
            } else {
                $data[$campo] = 0;
            }
        }

        if(!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }
        $request->replace($data);

        $recorrencia->update([
            'cliente_id' => $request->cliente_id,
            'servico_id' => $request->servico_id,
            'descricao_recorrencia' => $request->descricao_recorrencia,
            'frequencia' => $request->frequencia,
            'proxima_execucao' => $request->proxima_execucao,
            'data_fim' => $request->data_fim,
            'emitir_automaticamente' => $request->has('emitir_automaticamente'),
            'ativo' => $request->has('ativo'),

            // Tomador
            'tomador_cnpj' => $data['tomador_cnpj'],
            'tomador_nome' => $data['tomador_nome'],
            'tomador_email' => $data['tomador_email'],
            'tomador_telefone' => $data['tomador_telefone'] ?? null,
            'tomador_im' => $data['tomador_im'] ?? null,
            'tomador_cep' => $data['tomador_cep'] ?? null,
            'tomador_endereco' => $data['tomador_endereco'] ?? null,
            'tomador_numero' => $data['tomador_numero'] ?? null,
            'tomador_complemento' => $data['tomador_complemento'] ?? null,
            'tomador_bairro' => $data['tomador_bairro'] ?? null,
            'tomador_cidade' => $data['tomador_cidade'] ?? null, // Codigo IBGE
            'tomador_uf' => $data['tomador_uf'] ?? null,

            // Valores
            'valor_servico' => $data['valor_servico'],
            'descricao_servico' => $request->descricao_servico,

            // Fiscal
            'trib_issqn' => $request->trib_issqn,
            'tp_ret_issqn' => $request->tp_ret_issqn,
            'p_tot_trib_fed' => $data['p_tot_trib_fed'] ?? 0,
            'p_tot_trib_est' => $data['p_tot_trib_est'] ?? 0,
            'p_tot_trib_mun' => $data['p_tot_trib_mun'] ?? 0,
        ]);

        return redirect()->route('recorrencias.index')->with('success', 'Recorrência atualizada com sucesso!');
    }

    /**
     * Pausar/Excluir
     */
    public function destroy($id)
    {
        $rec = Recorrencia::where('empresa_id', session('empresa_ativa'))->findOrFail($id);
        $rec->delete();
        return redirect()->route('recorrencias.index')->with('success', 'Removido com sucesso.');
    }
}
