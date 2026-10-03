<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop default Laravel users and recreate for MCS roles
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->string('user_id')->primary(); // e.g. admin, po1
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password');
            $table->unsignedInteger('branch_id')->default(201);
            // Allowed: Administrator, Purchasing Officer, Inventory Officer, EndUser
            // HoS is NOT a valid role
            $table->string('role');
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
