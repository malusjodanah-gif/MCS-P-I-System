<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->unsignedInteger('asset_id')->primary(); // 3001-3999
            $table->string('asset');
            $table->string('serial_number')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('branch_id')->default(201);
            $table->string('status')->default('new'); // new, used, under repair, out-of-service
            $table->unsignedInteger('po_number')->nullable();
            $table->unsignedInteger('item_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('item_id')->references('item_id')->on('items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
