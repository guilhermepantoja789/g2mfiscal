<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpresaModulo extends Model
{
    public const MODULO_ERP = 'erp';

    public const MODULO_PDV = 'pdv';

    public const MODULO_FINANCEIRO_GERENCIAL = 'financeiro_gerencial';

    public const MODULO_CONTABIL = 'contabil';

    /**
     * Módulos opt-in: sem registro = desabilitado.
     *
     * @var list<string>
     */
    public const OPT_IN = [
        self::MODULO_CONTABIL,
    ];

    protected $fillable = [
        'empresa_id',
        'modulo',
        'ativo',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
