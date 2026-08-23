<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LoanApplicationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_application_and_returns_json_risk_assessment(): void
    {
        Http::fake([
            '*api/v1/assess/risk' => Http::response([
                'default_probability' => 0.18432,
                'credit_score' => 735,
                'credit_grade' => 'AA',
                'approval_signal' => 'AUTO_APPROVE',
                'key_risk_drivers' => [
                    [
                        'factor' => 'Employment stability',
                        'direction' => 'positive',
                        'impact' => 0.20,
                        'description' => '7+ years stable employment boosts score',
                    ],
                ],
                'model_source' => 'xgboost',
            ], 200),
        ]);

        $response = $this->postJson('/api/v1/loan-applications', [
            'full_name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'monthly_income' => 8500,
            'employment_years' => 8.5,
            'home_ownership' => 'MORTGAGE',
            'credit_history_length' => 12,
            'loan_amount' => 35000,
            'loan_purpose' => 'Debt consolidation',
            'interest_rate' => 9.75,
            'term_months' => 60,
            'debt_to_income_ratio' => 0.42,
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseCount('applicants', 1);
        $this->assertDatabaseCount('loan_applications', 1);
        $this->assertDatabaseCount('risk_assessments', 1);
        $this->assertDatabaseHas('risk_assessments', [
            'credit_grade' => 'AA',
            'approval_signal' => 'AUTO_APPROVE',
            'status' => 'COMPLETED',
        ]);

        $risk = $response->json('data.risk_assessments.0');
        $this->assertSame('AA', $risk['credit_grade']);
        $this->assertSame('AUTO_APPROVE', $risk['approval_signal']);
    }

    public function test_store_falls_back_to_local_scorecard_when_ml_service_is_down(): void
    {
        Http::fake([
            '*' => Http::response('', 503),
        ]);

        $response = $this->postJson('/api/v1/loan-applications', [
            'full_name' => 'John Smith',
            'email' => 'john@example.com',
            'monthly_income' => 4000,
            'employment_years' => 2,
            'home_ownership' => 'RENT',
            'credit_history_length' => 3,
            'loan_amount' => 10000,
            'loan_purpose' => 'Home improvement',
            'interest_rate' => 12.0,
            'term_months' => 36,
            'debt_to_income_ratio' => 0.20,
        ]);

        // Persistence + graceful JSON with a locally-computed scorecard assessment.
        $response->assertStatus(201);

        $this->assertDatabaseCount('loan_applications', 1);
        $this->assertDatabaseHas('risk_assessments', [
            'status' => 'COMPLETED',
        ]);

        $risk = $response->json('data.risk_assessments.0');
        $this->assertNotNull($risk['credit_grade']);
        $this->assertNotEmpty($risk['key_risk_drivers']);
    }
}