<?php

namespace Tests\Feature;

use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McsPiSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $user = User::find('admin');
        $this->actingAs($user)
            ->get('/dashboard')
            ->assertStatus(200)
            ->assertSee('Dashboard');
    }

    public function test_enduser_cannot_create_order(): void
    {
        $user = User::find('eu1');
        $this->actingAs($user)
            ->get('/orders/create')
            ->assertStatus(403);
    }

    public function test_inventory_officer_can_create_issue(): void
    {
        $user = User::find('io1');
        $this->actingAs($user)
            ->post('/issues', [
                'item_id' => 1001,
                'branch_id' => 201,
                'quantity' => 2,
                'customer_id' => 4001,
                'purpose' => 'Workshop materials',
            ])
            ->assertRedirect(route('issues.index'));

        $this->assertDatabaseHas('issues', [
            'item_id' => 1001,
            'branch_id' => 201,
            'customer_id' => 4001,
            'quantity' => 2,
        ]);
    }

    public function test_purchasing_officer_can_create_ge_order(): void
    {
        $user = User::find('po1');
        $this->actingAs($user)
            ->post('/orders', [
                'inventory_flag' => 'STOCK',
                'supplier_id' => 5001,
                'item_id' => 1001,
                'quantity' => 5,
                'unit_cost' => 25,
                'account_code' => '6100',
            ])
            ->assertRedirect(route('orders.index'));

        $this->assertDatabaseHas('orders', [
            'item_id' => 1001,
            'status' => 'Pending',
            'inventory_flag' => 'STOCK',
        ]);
    }

    public function test_manual_status_update(): void
    {
        $user = User::find('po1');
        $order = Order::create([
            'ge_number' => 2001,
            'inventory_flag' => 'STOCK',
            'po_number' => 2001,
            'supplier_id' => 5001,
            'order_date' => now()->toDateString(),
            'user_id' => 'po1',
            'branch_id' => 201,
            'item_id' => 1001,
            'item' => 'A4 Copy Paper',
            'uom' => 'REAM',
            'quantity' => 2,
            'unit_cost' => 25,
            'total_cost' => 50,
            'status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->patch(route('orders.status', $order), ['status' => 'Approved'])
            ->assertRedirect();

        $this->assertEquals('Approved', $order->fresh()->status);
    }

    public function test_receipt_updates_stock_and_unit_cost(): void
    {
        $user = User::find('po1');
        $order = Order::create([
            'ge_number' => 2002,
            'inventory_flag' => 'STOCK',
            'po_number' => 2002,
            'supplier_id' => 5001,
            'order_date' => now()->toDateString(),
            'user_id' => 'po1',
            'branch_id' => 201,
            'item_id' => 1001,
            'item' => 'A4 Copy Paper',
            'uom' => 'REAM',
            'quantity' => 10,
            'unit_cost' => 30,
            'total_cost' => 300,
            'status' => 'Ordered',
        ]);

        $before = ItemBranch::where('item_id', 1001)->where('branch_id', 201)->first();
        $stockBefore = $before->current_stock;

        $this->actingAs($user)
            ->post('/receipts', [
                'order_line_id' => $order->order_line_id,
                'quantity_received' => 4,
                'received_by' => 'Test',
                'grn' => 'INV-1',
            ])
            ->assertRedirect(route('receipts.index'));

        $after = ItemBranch::where('item_id', 1001)->where('branch_id', 201)->first();
        $this->assertEquals($stockBefore + 4, $after->current_stock);
        $this->assertContains($order->fresh()->status, ['Backorder', 'Complete', 'Received']);
    }

    public function test_hos_is_not_a_valid_role_constant(): void
    {
        $this->assertNotContains('HoS', User::ROLES);
        $this->assertContains('Administrator', User::ROLES);
        $this->assertContains('Purchasing Officer', User::ROLES);
        $this->assertContains('Inventory Officer', User::ROLES);
        $this->assertContains('EndUser', User::ROLES);
    }

    public function test_receipt_blocked_while_pending(): void
    {
        $user = User::find('po1');
        $order = Order::create([
            'ge_number' => 2003,
            'inventory_flag' => 'NON-STOCK',
            'po_number' => 2003,
            'supplier_id' => 5001,
            'order_date' => now()->toDateString(),
            'user_id' => 'po1',
            'branch_id' => 201,
            'item' => 'Consulting',
            'quantity' => 1,
            'unit_cost' => 100,
            'total_cost' => 100,
            'status' => 'Pending',
        ]);

        $this->actingAs($user)
            ->post('/receipts', [
                'order_line_id' => $order->order_line_id,
                'quantity_received' => 1,
                'received_by' => 'X',
            ])
            ->assertSessionHasErrors();
    }
}
