<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            // Gera um CNPJ aleatório (pode não ser válido matematicamente, mas serve pra visual)
            'cnpj' => $this->faker->numerify('##############'),
            'razao_social' => $this->faker->company() . ' ' . $this->faker->companySuffix(),
            'email' => $this->faker->companyEmail(),
            'cep' => '01001000',
            'logradouro' => $this->faker->streetName(),
            'numero' => $this->faker->buildingNumber(),
            'bairro' => 'Centro',
            'uf' => $this->faker->stateAbbr(),
            'cidade_codigo' => '3550308',
        ];
    }
}
