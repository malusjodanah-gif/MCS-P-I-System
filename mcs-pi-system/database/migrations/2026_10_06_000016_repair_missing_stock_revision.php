<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Repair a deployment where the earlier migration is recorded but this column is absent.
        if (! Schema::hasColumn('item_branches', 'stock_revision')) {
            Schema::table('item_branches', fn (Blueprint $table) => $table->unsignedBigInteger('stock_revision')->default(0));
        }
    }

    public function down(): void
    {
        // Keep the revision column: it belongs to the earlier migration and is required for safe reversals.
    }
};
