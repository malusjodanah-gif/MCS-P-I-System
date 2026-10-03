<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->unsignedInteger('issue_number')->primary(); // 7001-7999
            $table->unsignedInteger('branch_id')->default(201);
            $table->unsignedInteger('item_id');
            $table->integer('quantity');
            $table->string('uom')->nullable();
            $table->unsignedInteger('customer_id');
            $table->text('purpose')->nullable();
            $table->string('user_id');
            $table->date('issue_date');
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches');
            $table->foreign('item_id')->references('item_id')->on('items');
            $table->foreign('customer_id')->references('customer_id')->on('customers');
            $table->foreign('user_id')->references('user_id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
