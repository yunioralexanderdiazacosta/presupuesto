<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\CompanyReason;
use App\Models\Supplier;

class InvoiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CompanyReason::factory(10)->create();
        Supplier::factory(10)->create();
        Product::factory(10)->create();
        Invoice::factory(10000)->create();
    }
}
