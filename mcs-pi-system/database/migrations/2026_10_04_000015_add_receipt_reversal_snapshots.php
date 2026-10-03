<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_branches', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_revision')->default(0);
        });
        Schema::table('receipts', function (Blueprint $table) {
            $table->integer('stock_before')->nullable();
            $table->decimal('cost_before', 12, 4)->nullable();
            $table->decimal('purchase_unit_cost', 12, 4)->nullable();
            $table->unsignedBigInteger('stock_revision_after')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn(['stock_before', 'cost_before', 'purchase_unit_cost', 'stock_revision_after']);
        });
        Schema::table('item_branches', fn (Blueprint $table) => $table->dropColumn('stock_revision'));
    }
};
