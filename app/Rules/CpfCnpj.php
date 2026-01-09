<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class CpfCnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $c = preg_replace('/\D/', '', $value);

        if (strlen($c) === 11) {
            if (preg_match("/^{$c[0]}{11}$/", $c)) { $fail('CPF inválido.'); return; }
            for ($s = 10, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
            if ($c[9] != ((($n %= 11) < 2) ? 0 : 11 - $n)) { $fail('CPF inválido.'); return; }
            for ($s = 11, $n = 0, $i = 0; $s >= 2; $n += $c[$i++] * $s--);
            if ($c[10] != ((($n %= 11) < 2) ? 0 : 11 - $n)) { $fail('CPF inválido.'); return; }
        } elseif (strlen($c) === 14) {
            if (preg_match("/^{$c[0]}{14}$/", $c)) { $fail('CNPJ inválido.'); return; }
            $b = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
            for ($i = 0, $n = 0; $i < 12; $n += $c[$i] * $b[++$i]);
            if ($c[12] != ((($n %= 11) < 2) ? 0 : 11 - $n)) { $fail('CNPJ inválido.'); return; }
            for ($i = 0, $n = 0; $i <= 12; $n += $c[$i] * $b[$i++]);
            if ($c[13] != ((($n %= 11) < 2) ? 0 : 11 - $n)) { $fail('CNPJ inválido.'); return; }
        } else {
            $fail('O documento deve ter 11 (CPF) ou 14 (CNPJ) dígitos.');
        }
    }
}
