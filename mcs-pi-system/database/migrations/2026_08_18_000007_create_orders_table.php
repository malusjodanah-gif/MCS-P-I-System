<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Order lines; GE number is shared across lines of the same request
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_line_id');
            $table->unsignedInteger('ge_number');
            $table->string('inventory_flag'); // STOCK | NON-STOCK
            $table->unsignedInteger('po_number')->nullable();
            $table->unsignedInteger('supplier_id');
            $table->date('order_date');
            $table->string('user_id');
            $table->unsignedInteger('branch_id')->default(201);
            $table->string('account_code')->nullable();
            $table->unsignedInteger('item_id')->nullable(); // null for NON-STOCK
            $table->string('item'); // Spec attribute: Item (line description)
            $table->string('uom')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_cost', 12, 4);
            $table->decimal('total_cost', 14, 2);
            // Manual status flow (GE approval not automated):
            // Pending → Approved/Rejected/Cancelled → Ordered → Received → Backorder/Complete
            $table->string('status')->default('Pending');
            $table->timestamps();

            $table->index('ge_number');
            $table->index('status');
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers');
            $table->foreign('user_id')->references('user_id')->on('users');
            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('item_id')->references('item_id')->on('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
