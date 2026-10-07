<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('receipts')) {
            Schema::table('receipts', function (Blueprint $table) {
                if (! Schema::hasColumn('receipts', 'stock_after')) {
                    $table->integer('stock_after')->nullable();
                }
                if (! Schema::hasColumn('receipts', 'cost_after')) {
                    $table->decimal('cost_after', 12, 4)->nullable();
                }
            });

            if (Schema::hasColumn('receipts', 'stock_before')
                && Schema::hasColumn('receipts', 'cost_before')
                && Schema::hasColumn('receipts', 'purchase_unit_cost')) {
                DB::table('receipts')
                    ->whereNotNull('stock_before')
                    ->whereNotNull('cost_before')
                    ->whereNotNull('purchase_unit_cost')
                    ->orderBy('receipt_id')
                    ->chunkById(100, function ($receipts): void {
                        foreach ($receipts as $receipt) {
                            $stockAfter = $receipt->stock_before + $receipt->quantity_received;
                            $costAfter = $stockAfter > 0
                                ? round(($receipt->stock_before * (float) $receipt->cost_before
                                    + $receipt->quantity_received * (float) $receipt->purchase_unit_cost) / $stockAfter, 4)
                                : (float) $receipt->cost_before;

                            DB::table('receipts')->where('receipt_id', $receipt->receipt_id)->update([
                                'stock_after' => $stockAfter,
                                'cost_after' => $costAfter,
                            ]);
                        }
                    }, 'receipt_id', 'receipt_id');
            }

            if (Schema::hasColumn('receipts', 'stock_revision_after')) {
                Schema::table('receipts', fn (Blueprint $table) => $table->dropColumn('stock_revision_after'));
            }
        }

        if (Schema::hasTable('item_branches') && Schema::hasColumn('item_branches', 'stock_revision')) {
            Schema::table('item_branches', fn (Blueprint $table) => $table->dropColumn('stock_revision'));
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_branches') && ! Schema::hasColumn('item_branches', 'stock_revision')) {
            Schema::table('item_branches', fn (Blueprint $table) => $table->unsignedBigInteger('stock_revision')->default(0));
        }

        if (Schema::hasTable('receipts')) {
            if (! Schema::hasColumn('receipts', 'stock_revision_after')) {
                Schema::table('receipts', fn (Blueprint $table) => $table->unsignedBigInteger('stock_revision_after')->nullable());
            }

            $columnsToDrop = array_values(array_filter(
                ['stock_after', 'cost_after'],
                fn (string $column) => Schema::hasColumn('receipts', $column)
            ));
            if ($columnsToDrop !== []) {
                Schema::table('receipts', fn (Blueprint $table) => $table->dropColumn($columnsToDrop));
            }
        }
    }
};
