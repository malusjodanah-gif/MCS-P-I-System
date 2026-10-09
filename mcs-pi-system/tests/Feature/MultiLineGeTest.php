<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MultiLineGeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->actingAs(User::findOrFail('po1'));
    }

    private function header(): array
    {
        return ['ge_number' => 9701, 'inventory_flag' => 'STOCK', 'supplier_id' => 5001,
            'branch_id' => 201, 'order_date' => '2026-10-09', 'account_code' => 'BULK'];
    }

    public function test_bulk_ge_uses_one_header_ignores_blank_lines_and_recalculates_totals(): void
    {
        Branch::create(['branch_id' => 202, 'branch' => 'Bulk branch', 'faculty' => 'Sciences']);
        $stock = ItemBranch::sum('current_stock');
        $payload = $this->header();
        $payload['branch_id'] = 202;
        $payload['lines'] = array_fill(0, 20, ['item_id' => '', 'item' => '', 'quantity' => '', 'unit_cost' => '']);
        $payload['lines'][0] = ['item_id' => 1001, 'item' => 'Forged name', 'quantity' => 2, 'unit_cost' => 6, 'total_cost' => 9999];
        $payload['lines'][19] = ['item_id' => 1002, 'quantity' => 3, 'unit_cost' => 4];
        $this->post('/orders', $payload)->assertRedirect(route('orders.index'))->assertSessionHasNoErrors();
        $lines = Order::where('ge_number', 9701)->get();
        $this->assertCount(2, $lines);
        $this->assertEquals(24, $lines->sum('total_cost'));
        foreach ($lines as $line) {
            $this->assertSame(202, $line->branch_id);
            $this->assertSame(5001, $line->supplier_id);
            $this->assertSame('2026-10-09', $line->order_date->toDateString());
            $this->assertSame('Pending', $line->status);
            $this->assertSame($line->stockItem->item, $line->item);
            $this->assertSame($line->stockItem->uom, $line->uom);
        }
        $this->assertEquals($stock, ItemBranch::sum('current_stock'));
    }

    public function test_invalid_entered_line_saves_none_of_the_batch(): void
    {
        $this->post('/orders', $this->header() + ['lines' => [
            ['item_id' => 1001, 'quantity' => 2, 'unit_cost' => 6],
            ['item_id' => 1002, 'quantity' => 0, 'unit_cost' => 4],
        ]])->assertSessionHasErrors('lines.1.quantity');
        $this->assertDatabaseMissing('orders', ['ge_number' => 9701]);
    }

    public function test_empty_batch_requires_at_least_one_line(): void
    {
        $this->post('/orders', $this->header() + ['lines' => array_fill(0, 10, [])])
            ->assertSessionHasErrors('lines');
        $this->assertDatabaseMissing('orders', ['ge_number' => 9701]);
    }

    public function test_nonstock_batch_has_no_item_links_or_stock_effects(): void
    {
        $payload = $this->header();
        $payload['inventory_flag'] = 'NON-STOCK';
        $payload['lines'] = [['item_id' => 1001, 'item' => 'Service A', 'quantity' => 1, 'unit_cost' => 100],
            ['item' => 'Service B', 'quantity' => 2, 'unit_cost' => 50]];
        $stock = ItemBranch::sum('current_stock');
        $this->post('/orders', $payload)->assertSessionHasNoErrors();
        $lines = Order::where('ge_number', 9701)->get();
        $this->assertCount(2, $lines);
        $this->assertTrue($lines->every(fn ($line) => $line->item_id === null && $line->uom === null));
        $this->assertEquals(200, $lines->sum('total_cost'));
        $this->assertEquals($stock, ItemBranch::sum('current_stock'));
    }

    public function test_existing_ge_date_or_supplier_cannot_be_changed_by_appending_lines(): void
    {
        $this->post('/orders', $this->header() + ['lines' => [['item_id' => 1001, 'quantity' => 1, 'unit_cost' => 1]]])->assertSessionHasNoErrors();
        $payload = $this->header();
        $payload['order_date'] = '2026-10-10';
        $payload['lines'] = [['item_id' => 1002, 'quantity' => 1, 'unit_cost' => 1]];
        $this->post('/orders', $payload)->assertSessionHasErrors('ge_number');
        $this->assertSame(1, Order::where('ge_number', 9701)->count());
    }

    public function test_create_screen_starts_with_ten_optional_line_sections(): void
    {
        $this->get('/orders/create')->assertOk()->assertSee('Order header')->assertSee('+ Add 10 more lines')
            ->assertSee('lines[9][quantity]', false)->assertSee('ge-grand-total', false);
    }
}
