<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_check_passes_after_migration(): void
    {
        $this->artisan('mcs:check-schema')->assertSuccessful();
    }

    public function test_revision_columns_are_absent_and_reversal_snapshots_are_present(): void
    {
        $this->artisan('mcs:check-schema')->assertSuccessful();
        $this->assertFalse(Schema::hasColumn('item_branches', 'stock_revision'));
        $this->assertFalse(Schema::hasColumn('receipts', 'stock_revision_after'));
        $this->assertTrue(Schema::hasColumn('receipts', 'stock_after'));
        $this->assertTrue(Schema::hasColumn('receipts', 'cost_after'));
    }

    public function test_revision_removal_migration_preserves_inventory_and_backfills_receipt_state(): void
    {
        $this->seed();
        Schema::table('item_branches', fn ($table) => $table->unsignedBigInteger('stock_revision')->default(0));
        Schema::table('receipts', fn ($table) => $table->unsignedBigInteger('stock_revision_after')->nullable());

        DB::table('receipts')->insert([
            'po_number' => 9001,
            'branch_id' => 201,
            'item_id' => 1001,
            'supplier_id' => 5001,
            'quantity_ordered' => 5,
            'quantity_received' => 5,
            'received_by' => 'Migration Test',
            'date_received' => now()->toDateString(),
            'stock_before' => 10,
            'cost_before' => 2,
            'purchase_unit_cost' => 3,
            'stock_revision_after' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $stockBefore = DB::table('item_branches')->where('item_id', 1001)->where('branch_id', 201)->value('current_stock');

        $migration = require database_path('migrations/2026_10_07_000017_remove_non_spec_stock_revision.php');
        $migration->up();

        $receipt = DB::table('receipts')->where('po_number', 9001)->first();
        $this->assertSame(15, (int) $receipt->stock_after);
        $this->assertEqualsWithDelta(2.3333, (float) $receipt->cost_after, 0.00005);
        $this->assertSame($stockBefore, DB::table('item_branches')->where('item_id', 1001)->where('branch_id', 201)->value('current_stock'));
        $this->assertFalse(Schema::hasColumn('item_branches', 'stock_revision'));
        $this->assertFalse(Schema::hasColumn('receipts', 'stock_revision_after'));
    }
}
