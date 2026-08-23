<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RiskAssessment
 */
class RiskAssessmentResource extends JsonResource
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
            'loan_application_id' => $this->loan_application_id,
            'default_probability' => $this->default_probability,
            'default_probability_percent' => $this->default_probability !== null
                ? round((float) $this->default_probability * 100, 2)
                : null,
            'credit_grade' => $this->credit_grade,
            'approval_signal' => $this->approval_signal,
            'key_risk_drivers' => $this->key_risk_drivers,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'loan_application' => new LoanApplicationResource($this->whenLoaded('loanApplication')),
        ];
    }
}