<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->unsignedInteger('item_id')->primary(); // 1001-1999
            $table->string('item');
            $table->string('uom');
            $table->string('category'); // ASSET | CONSUMABLE
            $table->string('sub_category');
            $table->unsignedInteger('supplier_id')->nullable(); // Spec: SupplierID
            $table->timestamps();

            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};
