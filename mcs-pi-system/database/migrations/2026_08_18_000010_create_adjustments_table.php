<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('adjustments', function (Blueprint $table) {
            $table->unsignedInteger('adjustment_number')->primary(); // 5001-5999
            $table->string('adjustment_type'); // IN | OUT
            $table->unsignedInteger('branch_id')->default(201);
            $table->unsignedInteger('item_id');
            $table->integer('quantity');
            $table->string('uom')->nullable();
            $table->date('adjustment_date');
            $table->text('purpose')->nullable();
            $table->string('user_id');
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('adjustments');
    }
};
