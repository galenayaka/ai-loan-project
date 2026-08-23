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
        Schema::create('risk_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained('loan_applications')->cascadeOnDelete();
            $table->decimal('default_probability', 6, 5)->comment('Probability of default (0.00 - 1.00)');
            $table->string('credit_grade', 3)->comment('Credit grade from AAA to D');
            $table->enum('approval_signal', ['AUTO_APPROVE', 'MANUAL_REVIEW', 'AUTO_REJECT'])
                ->default('MANUAL_REVIEW')
                ->index();
            $table->json('key_risk_drivers')->nullable()->comment('Top positive/negative risk factors from ML/SHAP');
            $table->enum('status', ['PROCESSING', 'COMPLETED', 'FAILED'])->default('PROCESSING')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risk_assessments');
    }
};