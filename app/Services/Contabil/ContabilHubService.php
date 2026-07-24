<?php

namespace App\Services\Contabil;

use App\Models\DocumentoComercial;
use App\Models\Nfce;
use App\Models\NotaFiscal;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class ContabilHubService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function resolvePeriod(Request $request): array
    {
        $inicio = $request->filled('data_inicio')
            ? Carbon::parse($request->data_inicio)->startOfDay()
            : Carbon::now()->startOfMonth();

        $fim = $request->filled('data_fim')
            ? Carbon::parse($request->data_fim)->endOfDay()
            : Carbon::now()->endOfMonth();

        return [$inicio, $fim];
    }

    public function painel(int $empresaId, Carbon $inicio, Carbon $fim): array
    {
        $nfseBase = NotaFiscal::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('emissao', [$inicio, $fim]);

        $nfseAutorizadas = (clone $nfseBase)->where('status', 'autorizada');
        $nfseCanceladas = (clone $nfseBase)->where('status', 'cancelada');

        $nfceBase = Nfce::query()
            ->where('empresa_id', $empresaId);
        $this->aplicarCompetenciaNfce($nfceBase, $inicio, $fim);

        $nfceAutorizadas = (clone $nfceBase)->where('status', 'autorizada');
        $nfceCanceladas = (clone $nfceBase)->where('status', 'cancelada');

        $compras = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', DocumentoComercial::TIPO_COMPRA)
            ->whereIn('status', [
                DocumentoComercial::STATUS_CONFIRMADO,
                DocumentoComercial::STATUS_AUTORIZADO,
            ]);
        $this->aplicarCompetenciaDocumento($compras, $inicio, $fim);

        $issAprox = (float) (clone $nfseAutorizadas)->get()->sum(function (NotaFiscal $n) {
            $base = (float) $n->valor_servico;
            $aliq = (float) ($n->aliquota_iss ?? 0);

            return round($base * ($aliq / 100), 2);
        });

        return [
            'nfse_qtd' => (clone $nfseAutorizadas)->count(),
            'nfse_valor' => (float) (clone $nfseAutorizadas)->sum('valor_servico'),
            'nfse_canceladas' => (clone $nfseCanceladas)->count(),
            'nfse_iss' => $issAprox,
            'nfce_qtd' => (clone $nfceAutorizadas)->count(),
            'nfce_valor' => (float) (clone $nfceAutorizadas)->sum('valor_total'),
            'nfce_canceladas' => (clone $nfceCanceladas)->count(),
            'compras_qtd' => (clone $compras)->count(),
            'compras_valor' => (float) (clone $compras)->sum('valor_total'),
            'faturamento' => (float) (clone $nfseAutorizadas)->sum('valor_servico')
                + (float) (clone $nfceAutorizadas)->sum('valor_total'),
        ];
    }

    public function livroServicos(int $empresaId, Carbon $inicio, Carbon $fim, ?string $status = null)
    {
        $q = NotaFiscal::query()
            ->where('empresa_id', $empresaId)
            ->whereBetween('emissao', [$inicio, $fim])
            ->orderByDesc('emissao');

        if ($status) {
            $q->where('status', $status);
        }

        return $q->paginate(30)->withQueryString();
    }

    public function livroCupons(int $empresaId, Carbon $inicio, Carbon $fim, ?string $status = null)
    {
        $q = Nfce::query()
            ->where('empresa_id', $empresaId);
        $this->aplicarCompetenciaNfce($q, $inicio, $fim);
        $q->orderByDesc('data_emissao')->orderByDesc('created_at');

        if ($status) {
            $q->where('status', $status);
        }

        return $q->paginate(30)->withQueryString();
    }

    public function livroEntradas(int $empresaId, Carbon $inicio, Carbon $fim)
    {
        $q = DocumentoComercial::query()
            ->where('empresa_id', $empresaId)
            ->where('tipo', DocumentoComercial::TIPO_COMPRA);
        $this->aplicarCompetenciaDocumento($q, $inicio, $fim);

        return $q->with('fornecedor')
            ->orderByDesc('data_competencia')
            ->orderByDesc('created_at')
            ->paginate(30)
            ->withQueryString();
    }

    public function exportCsv(int $empresaId, Carbon $inicio, Carbon $fim, string $tipo): StreamedResponse
    {
        $filename = sprintf('contabil_%s_%s_%s.csv', $tipo, $inicio->format('Ymd'), $fim->format('Ymd'));

        return response()->streamDownload(function () use ($empresaId, $inicio, $fim, $tipo) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            if ($tipo === 'nfse') {
                fputcsv($out, ['chave', 'numero', 'data', 'valor', 'status', 'tomador'], ';');
                NotaFiscal::query()
                    ->where('empresa_id', $empresaId)
                    ->whereBetween('emissao', [$inicio, $fim])
                    ->orderBy('emissao')
                    ->chunk(200, function (Collection $rows) use ($out) {
                        foreach ($rows as $n) {
                            fputcsv($out, [
                                $n->chave_acesso,
                                $n->numero_nfse ?: $n->numero_dps,
                                optional($n->emissao)->format('Y-m-d H:i:s'),
                                number_format((float) $n->valor_servico, 2, '.', ''),
                                $n->status,
                                $n->tomador_nome,
                            ], ';');
                        }
                    });
            } elseif ($tipo === 'nfce') {
                fputcsv($out, ['chave', 'numero', 'serie', 'data', 'valor', 'status'], ';');
                $q = Nfce::query()->where('empresa_id', $empresaId);
                $this->aplicarCompetenciaNfce($q, $inicio, $fim);
                $q->orderBy('data_emissao')->orderBy('created_at')
                    ->chunk(200, function (Collection $rows) use ($out) {
                        foreach ($rows as $n) {
                            $data = $n->data_emissao
                                ? $n->data_emissao->format('Y-m-d')
                                : optional($n->created_at)->format('Y-m-d H:i:s');
                            fputcsv($out, [
                                $n->chave,
                                $n->numero,
                                $n->serie,
                                $data,
                                number_format((float) $n->valor_total, 2, '.', ''),
                                $n->status,
                            ], ';');
                        }
                    });
            } else {
                fputcsv($out, ['chave_nfe', 'numero', 'serie', 'data', 'valor', 'status'], ';');
                $q = DocumentoComercial::query()
                    ->where('empresa_id', $empresaId)
                    ->where('tipo', DocumentoComercial::TIPO_COMPRA);
                $this->aplicarCompetenciaDocumento($q, $inicio, $fim);
                $q->orderBy('data_competencia')->orderBy('created_at')
                    ->chunk(200, function (Collection $rows) use ($out) {
                        foreach ($rows as $d) {
                            $data = $d->data_competencia
                                ? $d->data_competencia->format('Y-m-d')
                                : optional($d->created_at)->format('Y-m-d H:i:s');
                            fputcsv($out, [
                                $d->chave_nfe,
                                $d->numero_nfe,
                                $d->serie_nfe,
                                $data,
                                number_format((float) $d->valor_total, 2, '.', ''),
                                $d->status,
                            ], ';');
                        }
                    });
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function exportZipXml(int $empresaId, Carbon $inicio, Carbon $fim, string $tipo): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $tmp = tempnam(sys_get_temp_dir(), 'contabil_zip_');
        $zipPath = $tmp.'.zip';
        @unlink($tmp);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Não foi possível criar o arquivo ZIP.');
        }

        $added = 0;

        if ($tipo === 'nfse') {
            NotaFiscal::query()
                ->where('empresa_id', $empresaId)
                ->whereBetween('emissao', [$inicio, $fim])
                ->whereNotNull('xml_autorizado')
                ->orderBy('emissao')
                ->chunk(100, function (Collection $rows) use ($zip, &$added) {
                    foreach ($rows as $n) {
                        $name = sprintf('nfse_%s_%s.xml', $n->id, $n->chave_acesso ?: $n->numero_nfse ?: 'semchave');
                        $zip->addFromString($name, (string) $n->xml_autorizado);
                        $added++;
                    }
                });
        } elseif ($tipo === 'nfce') {
            $q = Nfce::query()
                ->where('empresa_id', $empresaId)
                ->where(function ($q) {
                    $q->whereNotNull('xml_autorizado')->orWhereNotNull('xml_enviado');
                });
            $this->aplicarCompetenciaNfce($q, $inicio, $fim);
            $q->orderBy('data_emissao')->orderBy('created_at')
                ->chunk(100, function (Collection $rows) use ($zip, &$added) {
                    foreach ($rows as $n) {
                        $xml = $n->xml_autorizado ?: $n->xml_enviado;
                        $name = sprintf('nfce_%s_%s.xml', $n->id, $n->chave ?: $n->numero);
                        $zip->addFromString($name, (string) $xml);
                        $added++;
                    }
                });
        } else {
            $q = DocumentoComercial::query()
                ->where('empresa_id', $empresaId)
                ->where('tipo', DocumentoComercial::TIPO_COMPRA)
                ->whereNotNull('xml_nfe');
            $this->aplicarCompetenciaDocumento($q, $inicio, $fim);
            $q->orderBy('data_competencia')->orderBy('created_at')
                ->chunk(100, function (Collection $rows) use ($zip, &$added) {
                    foreach ($rows as $d) {
                        $name = sprintf('nfe_entrada_%s_%s.xml', $d->id, $d->chave_nfe ?: $d->numero_nfe ?: 'semchave');
                        $zip->addFromString($name, (string) $d->xml_nfe);
                        $added++;
                    }
                });
        }

        if ($added === 0) {
            $zip->addFromString('README.txt', "Nenhum XML encontrado no período.\n");
        }

        $zip->close();

        $filename = sprintf('xmls_%s_%s_%s.zip', $tipo, $inicio->format('Ymd'), $fim->format('Ymd'));

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Competência NFC-e: data_emissao (preferencial) ou created_at.
     */
    private function aplicarCompetenciaNfce(Builder $query, Carbon $inicio, Carbon $fim): void
    {
        $ini = $inicio->toDateString();
        $fimD = $fim->toDateString();

        $query->where(function (Builder $q) use ($inicio, $fim, $ini, $fimD) {
            $q->where(function (Builder $q2) use ($ini, $fimD) {
                $q2->whereNotNull('data_emissao')
                    ->whereDate('data_emissao', '>=', $ini)
                    ->whereDate('data_emissao', '<=', $fimD);
            })->orWhere(function (Builder $q2) use ($inicio, $fim) {
                $q2->whereNull('data_emissao')
                    ->whereBetween('created_at', [$inicio, $fim]);
            });
        });
    }

    /**
     * Competência documento (compras): data_competencia ou created_at.
     */
    private function aplicarCompetenciaDocumento(Builder $query, Carbon $inicio, Carbon $fim): void
    {
        $ini = $inicio->toDateString();
        $fimD = $fim->toDateString();

        $query->where(function (Builder $q) use ($inicio, $fim, $ini, $fimD) {
            $q->where(function (Builder $q2) use ($ini, $fimD) {
                $q2->whereNotNull('data_competencia')
                    ->whereDate('data_competencia', '>=', $ini)
                    ->whereDate('data_competencia', '<=', $fimD);
            })->orWhere(function (Builder $q2) use ($inicio, $fim) {
                $q2->whereNull('data_competencia')
                    ->whereBetween('created_at', [$inicio, $fim]);
            });
        });
    }
}
