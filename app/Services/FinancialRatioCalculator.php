<?php

namespace App\Services;

/**
 * Calculates the traditional financial ratios used throughout the
 * underwriting workflow. All calculations are deterministic and kept
 * free of any HTTP/ML dependency so they can be unit tested in isolation.
 */
class FinancialRatioCalculator
{
    /**
     * Standard monthly amortization payment for a fixed-rate loan.
     *
     * M = P * [ r(1+r)^n ] / [ (1+r)^n - 1 ]
     *
     * @param  float  $principal  Loan amount
     * @param  float  $annualRate  Annual interest rate in percent (e.g. 7.5)
     * @param  int  $termMonths  Term in months
     */
    public function monthlyPayment(float $principal, float $annualRate, int $termMonths): float
    {
        if ($termMonths <= 0 || $principal <= 0) {
            return 0.0;
        }

        $monthlyRate = ($annualRate / 100) / 12;

        // Zero-interest edge case (simple principal spread).
        if (abs($monthlyRate) < 0.000001) {
            return round($principal / $termMonths, 2);
        }

        $factor = pow(1 + $monthlyRate, $termMonths);
        $payment = $principal * (($monthlyRate * $factor) / ($factor - 1));

        return round($payment, 2);
    }

    /**
     * Debt-to-Income ratio (DTI): recurring monthly debt obligations
     * divided by gross monthly income.
     *
     * @return float Ratio between 0 and (theoretically) infinity, e.g. 0.43 = 43%
     */
    public function debtToIncome(float $monthlyDebt, float $monthlyIncome): float
    {
        if ($monthlyIncome <= 0) {
            return 0.0;
        }

        return round($monthlyDebt / $monthlyIncome, 4);
    }

    /**
     * Payment-to-Income ratio (PTI): the proposed loan's monthly payment
     * as a proportion of gross monthly income.
     *
     * @return float Ratio between 0 and infinity, e.g. 0.18 = 18%
     */
    public function paymentToIncome(float $monthlyPayment, float $monthlyIncome): float
    {
        if ($monthlyIncome <= 0) {
            return 0.0;
        }

        return round($monthlyPayment / $monthlyIncome, 4);
    }

    /**
     * Cash Flow Coverage: discretionary income remaining after debt
     * obligations and the proposed payment, expressed as a ratio over
     * the proposed payment. Values above 1.0 indicate the borrower's
     * residual income can cover the loan payment.
     *
     * @return float Coverage ratio. Returns a large sentinel when the
     *               proposed payment is zero, denoting "no burden".
     */
    public function cashFlowCoverage(float $monthlyIncome, float $monthlyDebt, float $monthlyPayment): float
    {
        $discretionary = $monthlyIncome - $monthlyDebt - $monthlyPayment;

        if ($monthlyPayment <= 0) {
            return $discretionary > 0 ? 999.0 : 0.0;
        }

        return round($discretionary / $monthlyPayment, 4);
    }
}