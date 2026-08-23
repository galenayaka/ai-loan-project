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
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained('applicants')->cascadeOnDelete();
            $table->decimal('loan_amount', 14, 2);
            $table->string('loan_purpose', 120);
            $table->decimal('interest_rate', 5, 2)->comment('Annual interest rate in percent');
            $table->unsignedSmallInteger('term_months');
            $table->decimal('debt_to_income_ratio', 6, 3)->comment('DTI as a ratio, e.g. 0.43 = 43%');
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'])->default('PENDING')->index();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_applications');
    }
};