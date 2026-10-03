<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Align with Specification v4:
 * - ITEM.SupplierID
 * - ORDERS.Item (line description; formerly item_description)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('items') && !Schema::hasColumn('items', 'supplier_id')) {
            Schema::table('items', function (Blueprint $table) {
                $table->unsignedInteger('supplier_id')->nullable();
                $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
            });
        }

        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'item_description') && !Schema::hasColumn('orders', 'item')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'pgsql') {
                DB::statement('ALTER TABLE orders RENAME COLUMN item_description TO item');
            } elseif ($driver === 'sqlite') {
                // SQLite: recreate is complex; add item and copy
                Schema::table('orders', function (Blueprint $table) {
                    $table->string('item')->nullable();
                });
                DB::statement('UPDATE orders SET item = item_description');
            } else {
                DB::statement('ALTER TABLE orders CHANGE item_description item VARCHAR(255) NOT NULL');
            }
        }
    }

    public function down(): void
    {
        // non-destructive reverse omitted
    }
};
