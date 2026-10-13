<?php

namespace Tests\Unit;

use App\Http\Requests\ApplicationOrders\StoreApplicationOrderRequest;
use App\Http\Requests\ApplicationOrders\UpdateApplicationOrderRequest;
use App\Models\ApplicationOrderProduct;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ApplicationOrderProductTest extends TestCase
{
    public function test_it_preserves_four_decimal_places_for_doses_and_calculated_quantities(): void
    {
        $orderProduct = new ApplicationOrderProduct([
            'dosis_por_100' => 0.015,
            'dosis_por_hectarea' => 0.0125,
            'cantidad_por_hectarea' => 0.0075,
            'cantidad_total' => 0.0375,
        ]);

        $this->assertSame('0.0150', $orderProduct->dosis_por_100);
        $this->assertSame('0.0125', $orderProduct->dosis_por_hectarea);
        $this->assertSame('0.0075', $orderProduct->cantidad_por_hectarea);
        $this->assertSame('0.0375', $orderProduct->cantidad_total);
    }

    public function test_store_and_update_validation_accept_four_decimal_places(): void
    {
        foreach ([StoreApplicationOrderRequest::class, UpdateApplicationOrderRequest::class] as $requestClass) {
            $rules = (new $requestClass())->rules();

            $validator = Validator::make(
                ['dose' => 0.015],
                ['dose' => $rules['products.*.dosis_por_hectarea']]
            );

            $this->assertTrue($validator->passes(), $requestClass);
        }
    }

    public function test_store_and_update_validation_reject_more_than_four_decimal_places(): void
    {
        foreach ([StoreApplicationOrderRequest::class, UpdateApplicationOrderRequest::class] as $requestClass) {
            $rules = (new $requestClass())->rules();

            $validator = Validator::make(
                ['dose' => 0.00015],
                ['dose' => $rules['products.*.dosis_por_hectarea']]
            );

            $this->assertTrue($validator->fails(), $requestClass);
        }
    }
}
