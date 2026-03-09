<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'cpf' => $this->generateValidCpf(),
        ];
    }

    /**
     * Generate a mathematically valid Brazilian CPF.
     */
    private function generateValidCpf(): string
    {
        // Generate first 9 digits
        $cpf = '';
        for ($i = 0; $i < 9; $i++) {
            $cpf .= $this->faker->numberBetween(0, 9);
        }

        // Calculate first check digit
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $firstCheckDigit = $remainder < 2 ? 0 : 11 - $remainder;
        $cpf .= $firstCheckDigit;

        // Calculate second check digit
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $secondCheckDigit = $remainder < 2 ? 0 : 11 - $remainder;
        $cpf .= $secondCheckDigit;

        // Format CPF
        return substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9, 2);
    }

    /**
     * Create a customer with a specific domain email.
     */
    public function withEmailDomain(string $domain): static
    {
        return $this->state(fn (array $attributes) => [
            'email' => $this->faker->userName() . '@' . $domain,
        ]);
    }
}
