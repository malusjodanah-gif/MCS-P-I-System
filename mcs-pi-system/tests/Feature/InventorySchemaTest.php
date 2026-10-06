<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_schema_check_passes_after_migration(): void
    {
        $this->artisan('mcs:check-schema')->assertSuccessful();
    }

    public function test_missing_revision_is_detected_and_repaired_without_changing_stock(): void
    {
        $this->seed();
        $before = \Illuminate\Support\Facades\DB::table('item_branches')->get()->map(fn ($r) => [$r->item_id, $r->branch_id, $r->current_stock, $r->unit_cost])->all();
        Schema::table('item_branches', fn ($table) => $table->dropColumn('stock_revision'));
        $this->artisan('mcs:check-schema')->expectsOutputToContain('item_branches.stock_revision')->assertFailed();
        $migration = require database_path('migrations/2026_10_06_000016_repair_missing_stock_revision.php');
        $migration->up();
        $migration->up();
        $this->artisan('mcs:check-schema')->assertSuccessful();
        $after = \Illuminate\Support\Facades\DB::table('item_branches')->get()->map(fn ($r) => [$r->item_id, $r->branch_id, $r->current_stock, $r->unit_cost])->all();
        $this->assertSame($before, $after);
    }
}
