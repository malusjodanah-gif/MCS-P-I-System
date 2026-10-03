<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id('receipt_id');
            $table->unsignedInteger('po_number');
            $table->unsignedInteger('branch_id')->default(201);
            $table->unsignedInteger('item_id')->nullable();
            $table->unsignedInteger('supplier_id');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received');
            $table->string('uom')->nullable();
            $table->string('received_by');
            $table->date('date_received');
            $table->string('grn')->nullable();
            $table->string('account_code')->nullable();
            $table->unsignedBigInteger('order_line_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('item_id')->references('item_id')->on('items')->nullOnDelete();
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers');
            $table->foreign('order_line_id')->references('order_line_id')->on('orders')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
