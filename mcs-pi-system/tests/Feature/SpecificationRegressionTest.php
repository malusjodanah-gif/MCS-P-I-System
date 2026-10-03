<?php

namespace Tests\Feature;

use App\Models\Adjustment;
use App\Models\Branch;
use App\Models\ItemBranch;
use App\Models\Order;
use App\Models\Receipt;
use App\Models\User;
use App\Support\IdGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SpecificationRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function stock(int $item = 1001, int $branch = 201): ItemBranch
    {
        return ItemBranch::where('item_id', $item)->where('branch_id', $branch)->firstOrFail();
    }

    private function order(string $flag = 'STOCK', int $quantity = 10): Order
    {
        return Order::create([
            'ge_number' => 9001, 'po_number' => 9001, 'inventory_flag' => $flag,
            'supplier_id' => 5001, 'order_date' => now()->toDateString(),
            'user_id' => 'po1', 'branch_id' => 201, 'account_code' => '6100',
            'item_id' => $flag === 'STOCK' ? 1001 : null,
            'item' => $flag === 'STOCK' ? 'Paper' : 'Service', 'uom' => 'REAM',
            'quantity' => $quantity, 'unit_cost' => 30, 'total_cost' => $quantity * 30,
            'status' => 'Ordered',
        ]);
    }

    private function receipt(Order $order, int $quantity)
    {
        return $this->actingAs(User::findOrFail('po1'))->post('/receipts', [
            'order_line_id' => $order->getKey(), 'quantity_received' => $quantity,
            'received_by' => 'Test Officer', 'grn' => 'INV-TEST',
        ]);
    }

    public function test_seeded_stock_movements_are_applied_to_the_correct_rows(): void
    {
        $this->assertSame(28, $this->stock(1005)->current_stock);
        $this->assertSame(15, $this->stock(1006)->current_stock);
    }

    public function test_composite_key_save_refresh_and_delete_are_scoped_to_one_branch(): void
    {
        Branch::create(['branch_id' => 202, 'branch' => 'Other', 'faculty' => 'Sciences']);
        ItemBranch::create(['item_id' => 1001, 'branch_id' => 202, 'uom' => 'REAM',
            'current_stock' => 7, 'unit_cost' => 20, 'location' => 'Store B',
            'reorder_level' => 0, 'reorder_quantity' => 0]);
        $otherItem = $this->stock(1002)->current_stock;
        $row = $this->stock();
        $row->current_stock = 99;
        $row->save();
        $this->assertSame(99, $row->fresh()->current_stock);
        $this->assertSame(7, $this->stock(1001, 202)->current_stock);
        $this->assertSame($otherItem, $this->stock(1002)->current_stock);
        $this->stock(1001, 202)->delete();
        $this->assertDatabaseMissing('item_branches', ['item_id' => 1001, 'branch_id' => 202]);
        $this->assertDatabaseHas('item_branches', ['item_id' => 1001, 'branch_id' => 201, 'current_stock' => 99]);
    }

    public function test_database_sessions_support_string_user_ids_and_real_login(): void
    {
        $this->assertTrue(Schema::hasTable('sessions'));
        config(['session.driver' => 'database']);
        $this->post('/login', ['user_id' => 'admin', 'password' => 'admin123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs(User::findOrFail('admin'));
        $this->assertDatabaseHas('sessions', ['user_id' => 'admin']);
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_purchasing_officer_can_issue_and_adjust_inventory(): void
    {
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('po1'))->post('/issues', [
            'item_id' => 1001, 'branch_id' => 201, 'customer_id' => 4001,
            'quantity' => 2, 'purpose' => 'Classroom',
        ])->assertRedirect(route('issues.index'))->assertSessionHasNoErrors();
        $this->post('/adjustments', ['item_id' => 1001, 'branch_id' => 201,
            'quantity' => 3, 'adjustment_type' => 'IN'])->assertSessionHasNoErrors();
        $this->assertSame($before + 1, $this->stock()->current_stock);
        $this->assertTrue(User::findOrFail('po1')->canWriteInventory());
    }

    public function test_enduser_cannot_write_inventory_or_purchasing(): void
    {
        $this->actingAs(User::findOrFail('eu1'));
        foreach (['issues', 'adjustments', 'items', 'item-branch', 'assets', 'orders', 'receipts', 'users', 'branches'] as $path) {
            $this->post('/'.$path, [])->assertForbidden();
        }
    }

    public function test_inventory_officer_cannot_write_purchasing_and_has_no_receipt_button(): void
    {
        $this->actingAs(User::findOrFail('io1'))->post('/orders', [])->assertForbidden();
        $this->post('/receipts', [])->assertForbidden();
        $this->get('/receipts')->assertOk()->assertDontSee('Add Receipt');
    }

    public function test_partial_and_full_receipts_keep_status_manual_and_reject_overreceipt(): void
    {
        $order = $this->order();
        $before = $this->stock()->current_stock;
        $this->receipt($order, 4)->assertSessionHasNoErrors();
        $this->assertSame('Ordered', $order->fresh()->status);
        $this->receipt($order, 7)->assertSessionHasErrors('quantity_received');
        $this->assertSame($before + 4, $this->stock()->current_stock);
        $this->receipt($order, 6)->assertSessionHasNoErrors();
        $this->receipt($order, 1)->assertSessionHasErrors('quantity_received');
        $this->assertSame($before + 10, $this->stock()->current_stock);
        $this->assertSame(2, $order->receipts()->count());
        $this->assertSame('Ordered', $order->fresh()->status);
    }

    public function test_nonstock_receipts_do_not_change_any_inventory(): void
    {
        $before = DB::table('item_branches')->orderBy('item_id')->orderBy('branch_id')->get()->toJson();
        $this->receipt($this->order('NON-STOCK', 2), 2)->assertSessionHasNoErrors();
        $after = DB::table('item_branches')->orderBy('item_id')->orderBy('branch_id')->get()->toJson();
        $this->assertSame($before, $after);
        $this->assertDatabaseHas('receipts', ['quantity_received' => 2, 'item_id' => null]);
    }

    public function test_zero_receipt_and_missing_item_branch_are_validation_errors(): void
    {
        $order = $this->order();
        $this->receipt($order, 0)->assertSessionHasErrors('quantity_received');
        DB::table('item_branches')->where('item_id', 1001)->where('branch_id', 201)->delete();
        $this->receipt($order, 1)->assertSessionHasErrors('order_line_id');
        $this->assertSame(0, $order->receipts()->count());
    }

    public function test_issue_and_out_adjustment_cannot_make_stock_negative(): void
    {
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('io1'))->post('/issues', [
            'item_id' => 1001, 'customer_id' => 4001, 'quantity' => $before + 1,
        ])->assertSessionHasErrors('quantity');
        $this->post('/adjustments', ['item_id' => 1001, 'adjustment_type' => 'OUT',
            'quantity' => $before + 1])->assertSessionHasErrors('quantity');
        $this->assertSame($before, $this->stock()->current_stock);
    }

    public function test_issue_cancellation_restores_stock_once(): void
    {
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('io1'))->post('/issues', [
            'issue_number' => 7998, 'item_id' => 1001, 'quantity' => 2, 'customer_id' => 4001,
        ])->assertSessionHasNoErrors();
        $this->delete('/issues/7998')->assertSessionHasNoErrors();
        $this->assertSame($before, $this->stock()->current_stock);
        $this->delete('/issues/7998')->assertNotFound();
        $this->assertSame($before, $this->stock()->current_stock);
    }

    public function test_cancelling_used_in_adjustment_preserves_stock_and_transaction(): void
    {
        $row = $this->stock(1006);
        $row->current_stock = 2;
        $row->save();
        $this->actingAs(User::findOrFail('io1'))->delete('/adjustments/5101')->assertSessionHasErrors('delete');
        $this->assertSame(2, $this->stock(1006)->current_stock);
        $this->assertDatabaseHas('adjustments', ['adjustment_number' => 5101, 'quantity' => 3]);
    }

    public function test_cancelling_unused_in_adjustment_reverses_the_exact_quantity(): void
    {
        $this->actingAs(User::findOrFail('io1'))->delete('/adjustments/5101')->assertSessionHasNoErrors();
        $this->assertSame(12, $this->stock(1006)->current_stock);
        $this->assertDatabaseMissing('adjustments', ['adjustment_number' => 5101]);
    }

    public function test_pending_order_cannot_skip_approval_or_receive_a_po(): void
    {
        $order = $this->order();
        $order->update(['status' => 'Pending', 'po_number' => null]);
        $this->actingAs(User::findOrFail('po1'))->patch(route('orders.status', $order), ['status' => 'Ordered'])
            ->assertSessionHasErrors('status');
        $this->assertSame('Pending', $order->fresh()->status);
        $this->assertNull($order->fresh()->po_number);
        $this->patch(route('orders.status', $order), ['status' => 'Approved'])->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Ordered'])->assertSessionHasNoErrors();
        $this->assertSame(9001, (int) $order->fresh()->po_number);
    }

    public function test_manual_completion_requires_full_receipt(): void
    {
        $order = $this->order();
        $this->receipt($order, 4)->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Received'])->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Complete'])->assertSessionHasErrors('status');
        $this->patch(route('orders.status', $order), ['status' => 'Backorder'])->assertSessionHasNoErrors();
        $this->receipt($order, 6)->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Complete'])->assertSessionHasNoErrors();
        $this->assertSame('Complete', $order->fresh()->status);
    }

    public function test_whole_ge_status_change_rolls_back_if_any_line_is_invalid(): void
    {
        $first = $this->order();
        $first->update(['status' => 'Pending', 'po_number' => null]);
        $second = $this->order();
        $this->actingAs(User::findOrFail('po1'))->patch(route('orders.status', $first), [
            'status' => 'Approved', 'update_whole_ge' => 1,
        ])->assertSessionHasErrors('status');
        $this->assertSame('Pending', $first->fresh()->status);
        $this->assertSame('Ordered', $second->fresh()->status);
    }

    public function test_ge_cannot_mix_suppliers_or_accept_new_lines_after_approval(): void
    {
        $this->actingAs(User::findOrFail('po1'));
        $payload = ['ge_number' => 9900, 'inventory_flag' => 'STOCK', 'supplier_id' => 5001,
            'item_id' => 1001, 'item' => 'Forged description', 'quantity' => 2, 'unit_cost' => 20];
        $this->post('/orders', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('orders', ['ge_number' => 9900, 'po_number' => null, 'status' => 'Pending']);
        $line = Order::where('ge_number', 9900)->firstOrFail();
        $this->assertSame($line->stockItem->item, $line->item);
        $this->post('/orders', array_replace($payload, ['supplier_id' => 5002]))->assertSessionHasErrors('ge_number');
        $this->post('/orders', array_replace($payload, ['item_id' => 1002]))->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $line), ['status' => 'Approved', 'update_whole_ge' => 1])->assertSessionHasNoErrors();
        $this->post('/orders', $payload)->assertSessionHasErrors('ge_number');
        $this->assertSame(2, Order::where('ge_number', 9900)->count());
    }

    public function test_item_deletion_does_not_erase_stock_or_operational_links(): void
    {
        $this->order();
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('admin'))->delete('/items/1001')->assertSessionHasErrors('delete');
        $this->assertSame($before, $this->stock()->current_stock);
        $this->assertDatabaseHas('items', ['item_id' => 1001]);
        $this->delete('/item-branch/1001/201')->assertSessionHasErrors('delete');
        $this->assertSame($before, $this->stock()->current_stock);
    }

    public function test_itembranch_stock_and_uom_are_system_managed(): void
    {
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('io1'))->put('/item-branch/1001/201', [
            'current_stock' => 999, 'uom' => 'FORGED', 'unit_cost' => 25,
            'location' => 'Store A', 'reorder_level' => 5, 'reorder_quantity' => 10,
        ])->assertSessionHasNoErrors();
        $this->assertSame($before, $this->stock()->current_stock);
        $this->assertSame($this->stock()->item->uom, $this->stock()->uom);
    }

    public function test_assets_require_asset_items_stocked_at_the_selected_branch(): void
    {
        $payload = ['asset_id' => 3998, 'asset' => 'Test', 'branch_id' => 201, 'status' => 'new'];
        $this->actingAs(User::findOrFail('io1'))->post('/assets', $payload)->assertSessionHasErrors('item_id');
        $this->post('/assets', $payload + ['item_id' => 1001])->assertSessionHasErrors('item_id');
        Branch::create(['branch_id' => 202, 'branch' => 'Other', 'faculty' => 'Sciences']);
        $this->post('/assets', array_replace($payload, ['branch_id' => 202, 'item_id' => 1003]))->assertSessionHasErrors('item_id');
        $this->post('/assets', $payload + ['item_id' => 1003])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('assets', ['asset_id' => 3998, 'item_id' => 1003, 'branch_id' => 201]);
    }

    public function test_reorder_report_includes_stock_equal_to_reorder_level(): void
    {
        $row = $this->stock();
        $row->current_stock = $row->reorder_level;
        $row->save();
        $this->actingAs(User::findOrFail('eu1'))->get('/reports/reorder')->assertOk()
            ->assertViewHas('rows', fn ($rows) => $rows->contains(fn ($record) => $record->item_id == 1001));
    }

    public function test_pending_ge_line_can_be_edited_without_changing_its_header(): void
    {
        $order = $this->order();
        $order->update(['status' => 'Pending', 'po_number' => null]);
        $this->actingAs(User::findOrFail('po1'))->get(route('orders.edit', $order))->assertOk();
        $this->put(route('orders.update', $order), [
            'item_id' => 1002, 'quantity' => 3, 'unit_cost' => 12.5,
            'supplier_id' => 5003, 'ge_number' => 9999,
        ])->assertSessionHasNoErrors();
        $after = $order->fresh();
        $this->assertSame(9001, (int) $after->ge_number);
        $this->assertSame(5001, (int) $after->supplier_id);
        $this->assertSame(1002, (int) $after->item_id);
        $this->assertSame('37.50', $after->total_cost);
        $this->assertSame($after->stockItem->item, $after->item);
        $this->patch(route('orders.status', $order), ['status' => 'Approved'])->assertSessionHasNoErrors();
        $this->put(route('orders.update', $order), ['item_id' => 1001, 'quantity' => 1, 'unit_cost' => 1])
            ->assertSessionHasErrors('order');
        $this->assertSame(3, (int) $order->fresh()->quantity);
    }

    public function test_issue_edit_reconciles_the_old_and_new_quantities(): void
    {
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('po1'))->post('/issues', [
            'issue_number' => 7998, 'item_id' => 1001, 'quantity' => 2, 'customer_id' => 4001,
        ])->assertSessionHasNoErrors();
        $this->get('/issues/7998/edit')->assertOk()->assertSee('Branch');
        $this->put('/issues/7998', ['item_id' => 1001, 'branch_id' => 201,
            'quantity' => 5, 'customer_id' => 4001, 'purpose' => 'Revised'])
            ->assertSessionHasNoErrors();
        $this->assertSame($before - 5, $this->stock()->current_stock);
        $this->put('/issues/7998', ['item_id' => 1001, 'branch_id' => 201,
            'quantity' => $before + 1, 'customer_id' => 4001])->assertSessionHasErrors('quantity');
        $this->assertSame($before - 5, $this->stock()->current_stock);
        $this->assertDatabaseHas('issues', ['issue_number' => 7998, 'quantity' => 5]);
    }

    public function test_issue_edit_can_move_to_another_branch_atomically(): void
    {
        Branch::create(['branch_id' => 202, 'branch' => 'Other', 'faculty' => 'Sciences']);
        ItemBranch::create(['item_id' => 1001, 'branch_id' => 202, 'uom' => 'REAM',
            'current_stock' => 7, 'unit_cost' => 20, 'location' => 'Store B',
            'reorder_level' => 0, 'reorder_quantity' => 0]);
        $before = $this->stock()->current_stock;
        $this->actingAs(User::findOrFail('io1'))->post('/issues', [
            'issue_number' => 7998, 'item_id' => 1001, 'quantity' => 2, 'customer_id' => 4001,
        ])->assertSessionHasNoErrors();
        $this->put('/issues/7998', ['item_id' => 1001, 'branch_id' => 202,
            'quantity' => 3, 'customer_id' => 4001])->assertSessionHasNoErrors();
        $this->assertSame($before, $this->stock()->current_stock);
        $this->assertSame(4, $this->stock(1001, 202)->current_stock);
        $this->assertDatabaseHas('issues', ['issue_number' => 7998, 'branch_id' => 202, 'quantity' => 3]);
    }

    public function test_adjustment_edit_applies_only_the_net_stock_change(): void
    {
        $before = $this->stock(1006)->current_stock;
        $this->actingAs(User::findOrFail('io1'))->get('/adjustments/5101/edit')->assertOk()->assertSee('Branch');
        $this->put('/adjustments/5101', ['item_id' => 1006, 'branch_id' => 201,
            'quantity' => 5, 'adjustment_type' => 'IN'])->assertSessionHasNoErrors();
        $this->assertSame($before + 2, $this->stock(1006)->current_stock);
        $this->put('/adjustments/5101', ['item_id' => 1006, 'branch_id' => 201,
            'quantity' => 2, 'adjustment_type' => 'OUT'])->assertSessionHasNoErrors();
        $this->assertSame(10, $this->stock(1006)->current_stock);
        $this->put('/adjustments/5101', ['item_id' => 1006, 'branch_id' => 201,
            'quantity' => 100, 'adjustment_type' => 'OUT'])->assertSessionHasErrors('quantity');
        $this->assertSame(10, $this->stock(1006)->current_stock);
        $this->assertDatabaseHas('adjustments', ['adjustment_number' => 5101, 'quantity' => 2, 'adjustment_type' => 'OUT']);
    }

    public function test_transaction_create_and_edit_forms_obey_role_permissions(): void
    {
        $this->actingAs(User::findOrFail('po1'))->get('/issues/create')->assertOk()->assertSee('Branch');
        $this->get('/adjustments/create')->assertOk()->assertSee('Branch');
        $this->get('/orders/create')->assertOk();
        $this->actingAs(User::findOrFail('eu1'))->get('/issues/7001/edit')->assertForbidden();
        $this->put('/issues/7001', [])->assertForbidden();
        $this->get('/adjustments/5101/edit')->assertForbidden();
        $this->put('/adjustments/5101', [])->assertForbidden();
    }

    public function test_stock_receipt_edit_recalculates_cost_and_cancel_restores_the_snapshot(): void
    {
        $order = $this->order();
        $before = $this->stock();
        $quantity = $before->current_stock;
        $cost = (float) $before->unit_cost;
        $this->receipt($order, 4)->assertSessionHasNoErrors();
        $receipt = $order->receipts()->firstOrFail();
        $this->get(route('receipts.edit', $receipt))->assertOk();
        $this->put(route('receipts.update', $receipt), ['quantity_received' => 6,
            'received_by' => 'Corrected', 'date_received' => now()->toDateString(), 'grn' => 'CORRECTED'])
            ->assertSessionHasNoErrors();
        $this->assertSame($quantity + 6, $this->stock()->current_stock);
        $this->assertEqualsWithDelta(round(($quantity * $cost + 6 * 30) / ($quantity + 6), 4),
            (float) $this->stock()->unit_cost, 0.0001);
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasNoErrors();
        $this->assertSame($quantity, $this->stock()->current_stock);
        $this->assertEqualsWithDelta($cost, (float) $this->stock()->unit_cost, 0.0001);
        $this->assertDatabaseMissing('receipts', ['receipt_id' => $receipt->getKey()]);
        $this->assertSame('Ordered', $order->fresh()->status);
    }

    public function test_receipt_stock_changes_are_blocked_after_issue_but_metadata_can_be_edited(): void
    {
        $order = $this->order();
        $this->receipt($order, 4)->assertSessionHasNoErrors();
        $receipt = $order->receipts()->firstOrFail();
        $this->post('/issues', ['item_id' => 1001, 'quantity' => 1, 'customer_id' => 4001])
            ->assertSessionHasNoErrors();
        $before = $this->stock()->current_stock;
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasErrors('quantity_received');
        $this->put(route('receipts.update', $receipt), ['quantity_received' => 5,
            'received_by' => 'Test', 'date_received' => now()->toDateString()])->assertSessionHasErrors('quantity_received');
        $this->assertSame($before, $this->stock()->current_stock);
        $this->put(route('receipts.update', $receipt), ['quantity_received' => 4,
            'received_by' => 'Metadata corrected', 'date_received' => now()->toDateString(), 'grn' => 'INV-C'])
            ->assertSessionHasNoErrors();
        $this->assertSame('INV-C', $receipt->fresh()->grn);
        $this->assertSame($before, $this->stock()->current_stock);
    }

    public function test_nonstock_receipt_can_be_edited_and_cancelled_without_stock_changes(): void
    {
        $order = $this->order('NON-STOCK', 3);
        $before = $this->stock()->current_stock;
        $this->receipt($order, 1)->assertSessionHasNoErrors();
        $receipt = $order->receipts()->firstOrFail();
        $this->put(route('receipts.update', $receipt), ['quantity_received' => 3,
            'received_by' => 'Test', 'date_received' => now()->toDateString()])->assertSessionHasNoErrors();
        $this->put(route('receipts.update', $receipt), ['quantity_received' => 4,
            'received_by' => 'Test', 'date_received' => now()->toDateString()])->assertSessionHasErrors('quantity_received');
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasNoErrors();
        $this->assertSame($before, $this->stock()->current_stock);
        $this->assertSame(0, $order->receipts()->count());
    }

    public function test_legacy_receipt_cannot_be_reversed_without_a_valuation_snapshot(): void
    {
        $order = $this->order();
        $this->receipt($order, 1)->assertSessionHasNoErrors();
        $receipt = $order->receipts()->firstOrFail();
        $receipt->forceFill(['stock_before' => null, 'stock_revision_after' => null])->save();
        $before = $this->stock()->current_stock;
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasErrors('quantity_received');
        $this->assertSame($before, $this->stock()->current_stock);
        $this->assertDatabaseHas('receipts', ['receipt_id' => $receipt->getKey()]);
    }

    public function test_completed_receipt_requires_manual_status_correction_before_cancellation(): void
    {
        $order = $this->order('NON-STOCK', 1);
        $this->receipt($order, 1)->assertSessionHasNoErrors();
        $receipt = $order->receipts()->firstOrFail();
        $this->patch(route('orders.status', $order), ['status' => 'Received'])->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Complete'])->assertSessionHasNoErrors();
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasErrors('quantity_received');
        $this->patch(route('orders.status', $order), ['status' => 'Received'])->assertSessionHasNoErrors();
        $this->delete(route('receipts.destroy', $receipt))->assertSessionHasNoErrors();
        $this->patch(route('orders.status', $order), ['status' => 'Cancelled'])->assertSessionHasNoErrors();
        $this->assertSame('Cancelled', $order->fresh()->status);
    }

    public function test_receipts_can_be_cancelled_in_reverse_order_with_exact_cost_restoration(): void
    {
        $order = $this->order();
        $before = $this->stock();
        $quantity = $before->current_stock;
        $cost = (float) $before->unit_cost;
        $this->receipt($order, 4)->assertSessionHasNoErrors();
        $first = $order->receipts()->firstOrFail();
        $this->receipt($order, 2)->assertSessionHasNoErrors();
        $last = $order->receipts()->orderByDesc('receipt_id')->firstOrFail();
        $this->delete(route('receipts.destroy', $first))->assertSessionHasErrors('quantity_received');
        $this->delete(route('receipts.destroy', $last))->assertSessionHasNoErrors();
        $this->delete(route('receipts.destroy', $first))->assertSessionHasNoErrors();
        $this->assertSame($quantity, $this->stock()->current_stock);
        $this->assertEqualsWithDelta($cost, (float) $this->stock()->unit_cost, 0.0001);
    }

    public function test_admin_can_load_operational_and_management_screens(): void
    {
        $this->actingAs(User::findOrFail('admin'));
        foreach (['dashboard', 'orders', 'orders/create', 'suppliers', 'suppliers/create',
            'suppliers/5001/edit', 'receipts', 'receipts/create', 'items', 'items/create',
            'items/1001/edit', 'item-branch', 'item-branch/create', 'item-branch/1001/201/edit',
            'customers', 'customers/create', 'customers/4001/edit', 'assets', 'assets/create',
            'assets/3001/edit', 'issues', 'adjustments', 'reports', 'reports/stock',
            'reports/purchases', 'reports/issues', 'reports/reorder', 'users', 'users/create',
            'users/po1/edit', 'branches', 'branches/create', 'branches/201/edit'] as $path) {
            $this->get('/'.$path)->assertOk();
        }
    }

    public function test_referenced_users_cannot_be_deleted_and_receive_a_clear_error(): void
    {
        $this->actingAs(User::findOrFail('admin'))->delete('/users/po1')->assertSessionHasErrors('delete');
        $this->assertDatabaseHas('users', ['user_id' => 'po1']);
        $this->assertDatabaseHas('orders', ['user_id' => 'po1']);
    }

    public function test_automatic_ids_are_allocated_within_the_creation_transaction(): void
    {
        $this->actingAs(User::findOrFail('po1'));
        $payload = ['customer' => 'Test customer', 'customer_type' => 'MCS'];
        $before = DB::table('customers')->max('customer_id');
        $this->post('/customers', $payload)->assertSessionHasNoErrors();
        $this->post('/customers', $payload)->assertSessionHasNoErrors();
        $this->assertDatabaseHas('customers', ['customer_id' => $before + 1]);
        $this->assertDatabaseHas('customers', ['customer_id' => $before + 2]);
        $firstGe = (Order::max('ge_number') ?? 1000) + 1;
        $order = ['inventory_flag' => 'STOCK', 'supplier_id' => 5001,
            'item_id' => 1001, 'quantity' => 1, 'unit_cost' => 2];
        $this->post('/orders', $order)->assertSessionHasNoErrors();
        $this->post('/orders', $order)->assertSessionHasNoErrors();
        $this->assertSame(1, Order::where('ge_number', $firstGe)->count());
        $this->assertSame(1, Order::where('ge_number', $firstGe + 1)->count());
    }

    public function test_id_allocator_rechecks_requested_ids_after_acquiring_its_lock(): void
    {
        $this->expectException(ValidationException::class);
        IdGenerator::create('customers', 'customer_id', 4001, 4999, 4001,
            fn ($id) => DB::table('customers')->insert(['customer_id' => $id, 'customer' => 'Duplicate', 'customer_type' => 'MCS']));
    }

    public function test_exhausted_id_range_returns_validation_instead_of_a_server_error(): void
    {
        $this->expectException(ValidationException::class);
        IdGenerator::next('customers', 'customer_id', 4001, 4001);
    }
}
