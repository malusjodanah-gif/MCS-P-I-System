<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->unsignedInteger('supplier_id')->primary(); // 5001-5999
            $table->string('supplier');
            $table->text('address')->nullable();
            $table->string('contact')->nullable();
            $table->string('payment_term')->default('CREDIT'); // CASH | CREDIT
            $table->string('currency')->default('PGK');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
