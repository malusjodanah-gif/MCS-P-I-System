<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptGuidanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_receipt_form_exposes_each_lines_actual_ordered_and_received_quantities(): void
    {
        $this->seed();
        $order = Order::create(['ge_number' => 9601, 'po_number' => 9601, 'inventory_flag' => 'NON-STOCK',
            'supplier_id' => 5001, 'branch_id' => 201, 'order_date' => now()->toDateString(),
            'user_id' => 'po1', 'item' => 'Guidance service', 'quantity' => 6,
            'unit_cost' => 6, 'total_cost' => 36, 'status' => 'Ordered']);
        $this->actingAs(User::findOrFail('po1'))->post('/receipts', [
            'order_line_id' => $order->getKey(), 'quantity_received' => 1, 'received_by' => 'Alpha',
        ])->assertSessionHasNoErrors();
        $this->get('/receipts/create')->assertOk()->assertSee('data-ordered="6"', false)
            ->assertSee('data-received="1"', false)->assertSee('Remaining 5')
            ->assertSee('receipt-quantity-note', false);
        $this->post('/receipts', ['order_line_id' => $order->getKey(), 'quantity_received' => 6,
            'received_by' => 'Alpha'])->assertSessionHasErrors('quantity_received');
        $this->assertEquals(1, $order->receipts()->sum('quantity_received'));
    }
}
