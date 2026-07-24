<?php

namespace App\Enums;

enum EmpresaPerfil: string
{
    case Admin = 'admin';
    case Operador = 'operador';
    case Contador = 'contador';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Papéis atribuíveis pela UI de equipe / vínculos (admins de empresa são imutáveis).
     *
     * @return list<string>
     */
    public static function assignableValues(): array
    {
        return [self::Operador->value, self::Contador->value];
    }

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Operador => 'Operador',
            self::Contador => 'Contador',
        };
    }
}
