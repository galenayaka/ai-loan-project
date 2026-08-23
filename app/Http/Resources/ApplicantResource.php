<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Applicant
 */
class ApplicantResource extends JsonResource
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
            'full_name' => $this->full_name,
            'email' => $this->email,
            'monthly_income' => $this->monthly_income,
            'employment_years' => $this->employment_years,
            'home_ownership' => $this->home_ownership,
            'credit_history_length' => $this->credit_history_length,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}