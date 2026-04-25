<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotaFiscalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $camposMonetarios = [
            'valor_servico',
            'v_tot_trib_fed', 'v_tot_trib_est', 'v_tot_trib_mun',
            'p_tot_trib_fed', 'p_tot_trib_est', 'p_tot_trib_mun',
            'aliquota_iss'
        ];

        $data = $this->all();

        foreach ($camposMonetarios as $campo) {
            if (!empty($data[$campo])) {
                $data[$campo] = str_replace('.', '', $data[$campo]);
                $data[$campo] = str_replace(',', '.', $data[$campo]);
            } else {
                $data[$campo] = 0;
            }
        }

        if (!empty($data['tomador_cnpj'])) {
            $data['tomador_cnpj'] = preg_replace('/\D/', '', $data['tomador_cnpj']);
        }

        // AUTO-CÁLCULO: Se % preenchida mas valor zerado, calcula automaticamente
        $valorBase = (float)($data['valor_servico'] ?? 0);
        $paresToTrib = [
            ['p_tot_trib_fed', 'v_tot_trib_fed'],
            ['p_tot_trib_est', 'v_tot_trib_est'],
            ['p_tot_trib_mun', 'v_tot_trib_mun'],
        ];

        foreach ($paresToTrib as [$campoPct, $campoVal]) {
            $pct = (float)($data[$campoPct] ?? 0);
            $val = (float)($data[$campoVal] ?? 0);
            if ($pct > 0 && $val == 0 && $valorBase > 0) {
                $data[$campoVal] = round($valorBase * $pct / 100, 2);
            }
        }

        $this->merge($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tomador_cnpj'   => 'required|digits_between:11,14',
            'tomador_nome'   => 'required|string|max:255',
            'valor_servico'  => 'required|numeric|min:0.01',
            'emissao'        => 'required|date',
            'descricao'      => 'required|string|min:5',
            'trib_issqn'     => 'required|integer',
            'tp_ret_issqn'   => 'required|integer',
            'vencimento'     => 'required_if:gerar_cobranca,1|date|nullable|after_or_equal:today',
        ];
    }
}
