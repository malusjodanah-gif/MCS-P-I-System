<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->unsignedInteger('branch_id')->primary();
            $table->string('branch')->default('School of MCS');
            $table->string('faculty')->default('Faculty of Sciences');
            // HoS is informational only — not used for approval workflow
            $table->string('hos')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
