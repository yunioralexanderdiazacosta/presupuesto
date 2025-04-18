<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\CompanyReason;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_term'      => 30,
            'payment_type'      => 1,
            'petty_cash'        => $this->faker->boolean(),
            'team_id'           => 1,
            'supplier_id'       => Supplier::where('team_id', 1)->get()->random()->id,
            'company_reason_id' => CompanyReason::where('team_id', 1)->get()->random()->id,
            'type_document_id'  => 1,
            'number_document'   => 3444444,
            'date'              => '2025-04-18',
            'due_date'          => '2026-04-18',
            'season_id'         => 1
        ];
    }


    public function configure()
    {
        return $this->afterCreating(function (Invoice $invoice) {
            // Will create 3 tasks for each new user
            $total = rand(1, 3);
            for ($i = 0; $i < $total; $i++) {
                $invoice->products()->attach(Product::all()->random()->id, ['unit_price' => rand(5, 100), 'amount' => rand(1, 10)]);
            }

        });
    }
}
