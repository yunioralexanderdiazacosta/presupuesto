<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\CompanyReason;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CompanyReason>
 */
class CompanyReasonFactory extends Factory
{
    protected $model = CompanyReason::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rut' => '32323333',
            'name' => $this->faker->company(),
            'legal_representative' => $this->faker->name(),
            'rut_representative' => '3444222',
            'address' => $this->faker->streetAddress(),
            'team_id' => 1
        ];
    }
}
