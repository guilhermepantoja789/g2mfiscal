<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Cliente;

class NotaFiscalFactory extends Factory
{
    public function definition(): array
    {
        $valor = $this->faker->randomFloat(2, 500, 15000); // Valores maiores para ficar bonito
        $status = $this->faker->randomElement(['autorizada', 'processando', 'autorizada', 'autorizada', 'erro']);

        return [
            'status' => $status,
            'numero_nfse' => $status == 'autorizada' ? $this->faker->unique()->numberBetween(1, 5000) : null,
            'codigo_verificacao' => $status == 'autorizada' ? strtoupper($this->faker->bothify('????-####')) : null,

            // Estes campos serão sobrescritos no Seeder, mas deixamos um fallback
            'tomador_cnpj' => '00000000000000',
            'tomador_nome' => 'Cliente Genérico',

            'valor_servico' => $valor,
            'aliquota_iss' => 5.00,
            'valor_iss' => $valor * 0.05,
            'valor_liquido' => $valor * 0.95,
            'descricao' => 'Consultoria em desenvolvimento de software e cloud computing.',
            'created_at' => $this->faker->dateTimeBetween('-6 months', 'now'), // Histórico maior
        ];
    }
}
