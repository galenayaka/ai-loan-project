<?php

namespace App\Http\Controllers;

use App\Services\FinancialRatioCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialCalculatorController extends Controller
{
    public function __construct(
        private readonly FinancialRatioCalculator $ratios,
    ) {
    }

    /**
     * Compute live loan payment and financial ratios for the dashboard
     * calculator. Mirrors the server-side underwriting math.
     */
    public function compute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'loan_amount' => ['required', 'numeric', 'min:0.01'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'term_months' => ['required', 'integer', 'min:1', 'max:480'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'monthly_debt' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $monthlyIncome = (float) $validated['monthly_income'];
        $monthlyDebt = (float) ($validated['monthly_debt'] ?? 0);

        $monthlyPayment = $this->ratios->monthlyPayment(
            (float) $validated['loan_amount'],
            (float) $validated['interest_rate'],
            (int) $validated['term_months'],
        );

        return response()->json([
            'monthly_payment' => $monthlyPayment,
            'debt_to_income' => $this->ratios->debtToIncome($monthlyDebt, $monthlyIncome),
            'payment_to_income' => $this->ratios->paymentToIncome($monthlyPayment, $monthlyIncome),
            'cash_flow_coverage' => $this->ratios->cashFlowCoverage($monthlyIncome, $monthlyDebt, $monthlyPayment),
        ]);
    }
}