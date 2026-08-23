<?php

namespace App\Services;

use App\Models\Applicant;
use App\Models\LoanApplication;
use App\Models\RiskAssessment;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Orchestrates the loan underwriting workflow end-to-end:
 *
 *   1. Aggregates applicant + loan application data.
 *   2. Calculates the traditional financial ratios (DTI, PTI, Cash Flow).
 *   3. Dispatches an HTTP POST to the Python FastAPI risk microservice.
 *   4. Persists the resulting risk assessment (probability of default,
 *      credit grade, approval signal and SHAP-driven risk drivers).
 */
class LoanUnderwritingService
{
    public function __construct(
        private readonly FinancialRatioCalculator $ratios,
    ) {
    }

    /**
     * Run the full underwriting assessment for a loan application.
     *
     * @throws RuntimeException When the microservice is unreachable or returns a fatal error.
     */
    public function assess(LoanApplication $loanApplication): RiskAssessment
    {
        $loanApplication->loadMissing('applicant');

        // Track the attempt before performing the (potentially failing) call.
        $assessment = RiskAssessment::create([
            'loan_application_id' => $loanApplication->id,
            'default_probability' => 0,
            'credit_grade' => 'D',
            'approval_signal' => RiskAssessment::SIGNAL_MANUAL_REVIEW,
            'key_risk_drivers' => [],
            'status' => RiskAssessment::STATUS_PROCESSING,
        ]);

        $payload = $this->buildPayload($loanApplication, $loanApplication->applicant);

        try {
            $data = $this->requestAssessment($assessment, $loanApplication, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Risk microservice unreachable; using local scorecard fallback.', [
                'risk_assessment_id' => $assessment->id,
                'error' => $e->getMessage(),
            ]);

            $data = $this->localAssessment($loanApplication, $payload);
        }

        return $this->persistResult($assessment, $data);
    }

    /**
     * Call the remote microservice, falling back to the local scorecard when
     * the service returns an error response.
     *
     * @return array<string, mixed>
     */
    protected function requestAssessment(
        RiskAssessment $assessment,
        LoanApplication $application,
        array $payload,
    ): array {
        $response = $this->dispatchToMicroservice($payload);

        if ($response->failed()) {
            Log::warning('Risk microservice returned HTTP '.$response->status().'; using local scorecard fallback.', [
                'risk_assessment_id' => $assessment->id,
            ]);

            return $this->localAssessment($application, $payload);
        }

        return $response->json();
    }

    /**
     * Transparent local heuristic scorecard mirroring the Python service,
     * used when the ML microservice is unavailable so the workflow still
     * produces a complete, explainable assessment.
     *
     * @return array<string, mixed>
     */
    protected function localAssessment(LoanApplication $application, array $payload): array
    {
        $applicant = $application->applicant;
        $ratios = $payload['financial_ratios'];
        $dti = (float) $ratios['debt_to_income'];
        $pti = (float) $ratios['payment_to_income'];
        $employmentYears = (float) $applicant->employment_years;
        $creditHistory = (int) $applicant->credit_history_length;
        $homeOwnership = $applicant->home_ownership;

        $score = 550.0;
        $drivers = [];

        if ($dti <= 0.20) {
            $score += 90;
            $drivers[] = $this->driver('DTI ratio', 'positive', 0.30, sprintf('Low DTI ratio (%.0f%%) signals strong repayment capacity', $dti * 100));
        } elseif ($dti <= 0.35) {
            $score += 55;
            $drivers[] = $this->driver('DTI ratio', 'positive', 0.15, sprintf('Acceptable DTI ratio (%.0f%%)', $dti * 100));
        } elseif ($dti <= 0.45) {
            $score += 10;
            $drivers[] = $this->driver('DTI ratio', 'negative', 0.20, sprintf('Elevated DTI ratio (%.0f%%) reduces available cash flow', $dti * 100));
        } else {
            $score -= 70;
            $drivers[] = $this->driver('DTI ratio', 'negative', 0.35, 'High DTI ratio (>45%) penalizes risk score');
        }

        if ($pti <= 0.15) {
            $score += 45;
            $drivers[] = $this->driver('Payment-to-Income', 'positive', 0.15, sprintf('Low payment burden (%.0f%%) keeps income flexible', $pti * 100));
        } elseif ($pti <= 0.28) {
            $score += 20;
        } else {
            $score -= 50;
            $drivers[] = $this->driver('Payment-to-Income', 'negative', 0.25, sprintf('High payment-to-income (%.0f%%) strains monthly budget', $pti * 100));
        }

        if ($employmentYears >= 7) {
            $score += 60;
            $drivers[] = $this->driver('Employment stability', 'positive', 0.20, '7+ years stable employment boosts score');
        } elseif ($employmentYears >= 2) {
            $score += 30;
        } elseif ($employmentYears < 1) {
            $score -= 40;
            $drivers[] = $this->driver('Employment stability', 'negative', 0.15, 'Limited employment history (<1 year) increases uncertainty');
        }

        if ($creditHistory >= 10) {
            $score += 45;
            $drivers[] = $this->driver('Credit history', 'positive', 0.10, $creditHistory.' years of credit history');
        } elseif ($creditHistory >= 3) {
            $score += 15;
        } else {
            $score -= 30;
            $drivers[] = $this->driver('Credit history', 'negative', 0.10, 'Thin credit file (short history) limits confidence');
        }

        if ($homeOwnership === 'OWN') {
            $score += 30;
        } elseif ($homeOwnership === 'MORTGAGE') {
            $score += 15;
        } else {
            $score -= 10;
            $drivers[] = $this->driver('Home ownership', 'negative', 0.05, 'Renting (no real-estate collateral) slightly lowers score');
        }

        $score = max(300.0, min(850.0, $score));

        $hasPositive = false;
        foreach ($drivers as $driver) {
            if ($driver['direction'] === 'positive') {
                $hasPositive = true;
                break;
            }
        }
        if (! $hasPositive) {
            $drivers[] = $this->driver('Overall profile', 'positive', 0.05, 'Profile meets baseline underwriting criteria');
        }

        $logit = (575.0 - $score) / 90.0;
        $probability = 1.0 / (1.0 + pow(10.0, -$logit));

        return [
            'default_probability' => round($probability, 5),
            'credit_grade' => $this->gradeFromScore($score),
            'approval_signal' => $this->signalFromScore($score),
            'key_risk_drivers' => $drivers,
        ];
    }

    /**
     * Build a normalized risk driver entry.
     *
     * @return array{factor: string, direction: string, impact: float, description: string}
     */
    protected function driver(string $factor, string $direction, float $impact, string $description): array
    {
        return [
            'factor' => $factor,
            'direction' => $direction,
            'impact' => $impact,
            'description' => $description,
        ];
    }

    /**
     * Map a 300-850 scorecard score onto a credit grade.
     */
    protected function gradeFromScore(float $score): string
    {
        $bounds = [
            [800, 'AAA'],
            [740, 'AA'],
            [700, 'A'],
            [660, 'BBB'],
            [620, 'BB'],
            [580, 'B'],
            [500, 'C'],
            [0, 'D'],
        ];

        foreach ($bounds as [$threshold, $grade]) {
            if ($score >= $threshold) {
                return $grade;
            }
        }

        return 'D';
    }

    /**
     * Map a scorecard score onto an approval signal.
     */
    protected function signalFromScore(float $score): string
    {
        if ($score >= 700) {
            return RiskAssessment::SIGNAL_AUTO_APPROVE;
        }

        if ($score >= 580) {
            return RiskAssessment::SIGNAL_MANUAL_REVIEW;
        }

        return RiskAssessment::SIGNAL_AUTO_REJECT;
    }

    /**
     * Build the normalized payload the Python microservice expects.
     *
     * @return array<string, mixed>
     */
    public function buildPayload(LoanApplication $application, Applicant $applicant): array
    {
        $monthlyPayment = $this->ratios->monthlyPayment(
            (float) $application->loan_amount,
            (float) $application->interest_rate,
            (int) $application->term_months,
        );

        // Existing obligations are derived from the stored DTI and income.
        $monthlyIncome = (float) $applicant->monthly_income;
        $monthlyDebt = (float) $application->debt_to_income_ratio * $monthlyIncome;

        return [
            'applicant' => [
                'full_name' => $applicant->full_name,
                'email' => $applicant->email,
                'monthly_income' => $monthlyIncome,
                'employment_years' => (float) $applicant->employment_years,
                'home_ownership' => $applicant->home_ownership,
                'credit_history_length' => (int) $applicant->credit_history_length,
            ],
            'loan' => [
                'loan_amount' => (float) $application->loan_amount,
                'loan_purpose' => $application->loan_purpose,
                'interest_rate' => (float) $application->interest_rate,
                'term_months' => (int) $application->term_months,
            ],
            'financial_ratios' => [
                'debt_to_income' => $this->ratios->debtToIncome($monthlyDebt, $monthlyIncome),
                'payment_to_income' => $this->ratios->paymentToIncome($monthlyPayment, $monthlyIncome),
                'cash_flow_coverage' => $this->ratios->cashFlowCoverage($monthlyIncome, $monthlyDebt, $monthlyPayment),
                'monthly_payment' => $monthlyPayment,
            ],
        ];
    }

    /**
     * POST the aggregated payload to the FastAPI microservice.
     */
    protected function dispatchToMicroservice(array $payload): Response
    {
        $baseUrl = rtrim((string) config('services.risk_ml.base_url'), '/');

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->retry(2, 100, throw: false)
            ->post('/api/v1/assess/risk', $payload);
    }

    /**
     * Persist the microservice response onto the risk assessment record.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persistResult(RiskAssessment $assessment, array $data): RiskAssessment
    {
        $probability = $this->clampProbability($data['default_probability'] ?? null);

        $assessment->update([
            'default_probability' => $probability,
            'credit_grade' => $this->normalizeGrade($data['credit_grade'] ?? 'D'),
            'approval_signal' => $this->normalizeSignal($data['approval_signal'] ?? null, $probability),
            'key_risk_drivers' => $data['key_risk_drivers'] ?? [],
            'status' => RiskAssessment::STATUS_COMPLETED,
        ]);

        return $assessment->fresh();
    }

    protected function markFailed(RiskAssessment $assessment, string $reason): void
    {
        $assessment->update([
            'status' => RiskAssessment::STATUS_FAILED,
            'key_risk_drivers' => ['error' => $reason],
        ]);

        Log::error('[LoanUnderwritingService] Assessment failed', [
            'risk_assessment_id' => $assessment->id,
            'reason' => $reason,
        ]);
    }

    /**
     * Clamp and validate a raw probability into the 0.00 - 1.00 range.
     */
    protected function clampProbability(mixed $value): float
    {
        if (! is_numeric($value)) {
            return 0.0;
        }

        return round(max(0.0, min(1.0, (float) $value)), 5);
    }

    /**
     * Normalize a credit grade string to a known value between AAA and D.
     */
    protected function normalizeGrade(mixed $grade): string
    {
        $grade = strtoupper((string) $grade);
        $allowed = ['AAA', 'AA', 'A', 'BBB', 'BB', 'B', 'C', 'D'];

        return in_array($grade, $allowed, true) ? $grade : 'D';
    }

    /**
     * Normalize or derive the approval signal from the microservice response.
     */
    protected function normalizeSignal(mixed $signal, float $probability): string
    {
        $allowed = [RiskAssessment::SIGNAL_AUTO_APPROVE, RiskAssessment::SIGNAL_MANUAL_REVIEW, RiskAssessment::SIGNAL_AUTO_REJECT];

        if (is_string($signal)) {
            $normalized = strtoupper($signal);
            if (in_array($normalized, $allowed, true)) {
                return $normalized;
            }
        }

        // Heuristic fallback if the microservice omitted a signal.
        if ($probability <= 0.25) {
            return RiskAssessment::SIGNAL_AUTO_APPROVE;
        }

        if ($probability >= 0.60) {
            return RiskAssessment::SIGNAL_AUTO_REJECT;
        }

        return RiskAssessment::SIGNAL_MANUAL_REVIEW;
    }
}