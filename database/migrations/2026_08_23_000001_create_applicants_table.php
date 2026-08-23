<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applicants', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->decimal('monthly_income', 14, 2);
            $table->decimal('employment_years', 4, 1)->default(0);
            $table->enum('home_ownership', ['RENT', 'OWN', 'MORTGAGE'])->default('RENT');
            $table->unsignedSmallInteger('credit_history_length')->comment('Credit history length in years');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applicants');
    }
};