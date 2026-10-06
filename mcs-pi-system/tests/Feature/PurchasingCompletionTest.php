<?php

namespace Tests\Feature;

use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasingCompletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::findOrFail('po1'));
    }

    private function order(int $ge = 9901, int $supplier = 5001, string $status = 'Approved'): Order
    {
        return Order::create(['ge_number' => $ge, 'inventory_flag' => 'NON-STOCK',
            'supplier_id' => $supplier, 'branch_id' => 201, 'order_date' => '2026-10-06',
            'user_id' => 'po1', 'item' => 'Alpha service', 'quantity' => 2,
            'unit_cost' => 10, 'total_cost' => 20, 'status' => $status]);
    }

    public function test_preallocated_po_can_be_assigned_after_approval(): void
    {
        $order = $this->order();
        $this->patch(route('orders.status', $order), ['status' => 'Ordered', 'po_number' => 8801])
            ->assertSessionHasNoErrors();
        $this->assertSame(8801, $order->fresh()->po_number);
        $this->assertSame('Ordered', $order->fresh()->status);
    }

    public function test_po_cannot_belong_to_different_suppliers(): void
    {
        $first = $this->order();
        $first->update(['po_number' => 8801, 'status' => 'Ordered']);
        $other = $this->order(9902, 5002);
        $this->patch(route('orders.status', $other), ['status' => 'Ordered', 'po_number' => 8801])
            ->assertSessionHasErrors('po_number');
        $this->assertSame('Approved', $other->fresh()->status);
        $this->assertNull($other->fresh()->po_number);
    }

    public function test_receipt_uses_supplied_date_and_hides_fully_received_lines(): void
    {
        $order = $this->order();
        $before = ItemBranch::sum('current_stock');
        $this->post('/receipts', ['order_line_id' => $order->getKey(), 'quantity_received' => 2,
            'received_by' => 'Alpha', 'date_received' => '2026-10-04'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('receipts', ['order_line_id' => $order->getKey(), 'date_received' => '2026-10-04']);
        $this->assertEquals($before, ItemBranch::sum('current_stock'));
        $this->get('/receipts/create')->assertOk()
            ->assertViewHas('lines', fn ($lines) => !$lines->contains('order_line_id', $order->getKey()));
    }

    public function test_invalid_receipt_date_creates_no_transaction(): void
    {
        $order = $this->order();
        $this->post('/receipts', ['order_line_id' => $order->getKey(), 'quantity_received' => 1,
            'received_by' => 'Alpha', 'date_received' => 'invalid'])->assertSessionHasErrors('date_received');
        $this->assertDatabaseMissing('receipts', ['order_line_id' => $order->getKey()]);
    }

    public function test_receipted_po_number_cannot_be_changed(): void
    {
        $order = $this->order();
        $this->post('/receipts', ['order_line_id' => $order->getKey(), 'quantity_received' => 1,
            'received_by' => 'Alpha'])->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Ordered', 'po_number' => 8801])
            ->assertSessionHasErrors('po_number');
        $this->assertSame('Approved', $order->fresh()->status);
    }

    public function test_receipt_fallback_po_cannot_collide_with_another_supplier(): void
    {
        $first = $this->order();
        $first->update(['po_number' => 9902]);
        $other = $this->order(9902, 5002);
        $this->post('/receipts', ['order_line_id' => $other->getKey(), 'quantity_received' => 1,
            'received_by' => 'Alpha'])->assertSessionHasErrors('order_line_id');
        $this->assertDatabaseMissing('receipts', ['order_line_id' => $other->getKey()]);
    }
}
