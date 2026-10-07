<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair partially migrated existing databases without changing their balances.
        foreach (['stock_before', 'cost_before', 'purchase_unit_cost', 'stock_after', 'cost_after'] as $column) {
            if (! Schema::hasColumn('receipts', $column)) {
                Schema::table('receipts', function (Blueprint $table) use ($column) {
                    if (in_array($column, ['stock_before', 'stock_after'], true)) {
                        $table->integer($column)->nullable();
                    } else {
                        $table->decimal($column, 12, 4)->nullable();
                    }
                });
            }
        }
        // Legacy snapshots stay null: historical valuations cannot be inferred safely.
    }

    public function down(): void
    {
        // These columns are owned by earlier migrations and required by receipt processing.
        // Rolling back this repair must not discard existing valuation snapshots.
    }
};
