<?php
use PHPUnit\Framework\TestCase;

class CartInventoryTest extends TestCase
{
    /** @test */
    public function test_cart_total_price_calculation()
    {
        $cart = [
            ['price' => 15.50, 'qty' => 2],
            ['price' => 10.00, 'qty' => 1],
            ['price' => 5.25,  'qty' => 4]
        ];

        $total = 0;
        foreach($cart as $item) 
        {
            $total += ($item['price'] * $item['qty']);
        }

        $this->assertEquals(62.00, $total, "Total calculation should equal 62.00");
    }

    /** @test */
    public function test_stock_availability_check()
    {
        $stockAvailable = 10;
        $requestedAmount = 15;

        $canAdd = ($requestedAmount <= $stockAvailable);
        
        $this->assertFalse($canAdd, "Users should not be able to add more than available stock.");
    }
}