<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\LoanApplication
 */
class LoanApplicationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'applicant_id' => $this->applicant_id,
            'loan_amount' => $this->loan_amount,
            'loan_purpose' => $this->loan_purpose,
            'interest_rate' => $this->interest_rate,
            'term_months' => $this->term_months,
            'debt_to_income_ratio' => $this->debt_to_income_ratio,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'applicant' => new ApplicantResource($this->whenLoaded('applicant')),
            'risk_assessments' => RiskAssessmentResource::collection($this->whenLoaded('riskAssessments')),
        ];
    }
}