<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_branches', function (Blueprint $table) {
            $table->unsignedInteger('item_id');
            $table->unsignedInteger('branch_id')->default(201);
            $table->string('uom');
            $table->integer('current_stock')->default(0);
            $table->decimal('unit_cost', 12, 4)->default(0);
            $table->string('location');
            $table->integer('reorder_level')->default(0);
            $table->integer('reorder_quantity')->default(0);
            $table->timestamps();

            $table->primary(['item_id', 'branch_id']);
            $table->foreign('item_id')->references('item_id')->on('items')->onDelete('cascade');
            $table->foreign('branch_id')->references('branch_id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_branches');
    }
};
