<?php

namespace Tests\Feature;

use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReceiptSchemaRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_snapshot_columns_roll_back_receipt_and_repair_allows_po_to_retry(): void
    {
        $this->seed();
        $stock = ItemBranch::where('item_id', 1001)->where('branch_id', 201)->firstOrFail();
        $stock->current_stock = 10;
        $stock->unit_cost = 5;
        $stock->save();
        $order = Order::create(['ge_number' => 9801, 'po_number' => 9801,
            'inventory_flag' => 'STOCK', 'supplier_id' => 5001, 'branch_id' => 201,
            'order_date' => now()->toDateString(), 'user_id' => 'po1',
            'item_id' => 1001, 'item' => 'Receipt repair pens', 'uom' => 'BOX',
            'quantity' => 2, 'unit_cost' => 6, 'total_cost' => 12, 'status' => 'Ordered']);
        $count = Receipt::count();
        Schema::table('receipts', fn ($table) => $table->dropColumn([
            'stock_before', 'cost_before', 'purchase_unit_cost', 'stock_after', 'cost_after',
        ]));
        $data = ['order_line_id' => $order->getKey(), 'quantity_received' => 1,
            'received_by' => 'Alpha PO', 'date_received' => now()->toDateString()];
        $this->actingAs(User::findOrFail('po1'))->post('/receipts', $data)->assertStatus(500);
        $this->assertSame($count, Receipt::count());
        $this->assertSame(10, $stock->fresh()->current_stock);
        $this->assertEquals(5, (float) $stock->fresh()->unit_cost);

        $migration = require database_path('migrations/2026_10_07_000018_repair_receipt_valuation_columns.php');
        $migration->up();
        $migration->up();
        $this->artisan('mcs:check-schema')->assertSuccessful();
        $this->assertSame($count, Receipt::count());
        $this->assertSame(10, $stock->fresh()->current_stock);
        $this->post('/receipts', $data)->assertRedirect(route('receipts.index'))->assertSessionHasNoErrors();
        $this->assertSame(11, $stock->fresh()->current_stock);
        $this->assertEqualsWithDelta(5.0909, (float) $stock->fresh()->unit_cost, 0.0001);
        $this->assertSame($count + 1, Receipt::count());
        $this->assertDatabaseHas('receipts', ['order_line_id' => $order->getKey(),
            'stock_before' => 10, 'stock_after' => 11]);
        $this->assertSame('Ordered', $order->fresh()->status);
    }

    public function test_repair_preserves_existing_receipt_snapshots(): void
    {
        $this->seed();
        $before = Receipt::orderBy('receipt_id')->get()->toArray();
        $migration = require database_path('migrations/2026_10_07_000018_repair_receipt_valuation_columns.php');
        $migration->up();
        $this->assertSame($before, Receipt::orderBy('receipt_id')->get()->toArray());
    }
}
