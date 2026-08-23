<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLoanApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Supports two flows:
     *   1. Link to an existing applicant via `applicant_id`.
     *   2. Create the applicant inline (applicant fields become required
     *      only when no `applicant_id` is supplied).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'applicant_id' => ['nullable', 'integer', 'exists:applicants,id'],

            'loan_amount' => ['required', 'numeric', 'min:0.01'],
            'loan_purpose' => ['required', 'string', 'max:120'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'term_months' => ['required', 'integer', 'min:1', 'max:480'],
            'debt_to_income_ratio' => ['required', 'numeric', 'min:0', 'max:10'],
            'status' => ['sometimes', Rule::in(['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'])],

            // Inline applicant fields — required only when creating a new applicant.
            'full_name' => ['required_without:applicant_id', 'string', 'max:255'],
            'email' => ['required_without:applicant_id', 'email', 'max:255', 'unique:applicants,email'],
            'monthly_income' => ['required_without:applicant_id', 'numeric', 'min:0'],
            'employment_years' => ['required_without:applicant_id', 'numeric', 'min:0', 'max:60'],
            'home_ownership' => ['required_without:applicant_id', Rule::in(['RENT', 'OWN', 'MORTGAGE'])],
            'credit_history_length' => ['required_without:applicant_id', 'integer', 'min:0', 'max:80'],
        ];
    }

    /**
     * Applicant-only attributes, used when no applicant_id is supplied.
     *
     * @return array<string, mixed>
     */
    public function applicantData(): array
    {
        return $this->only([
            'full_name',
            'email',
            'monthly_income',
            'employment_years',
            'home_ownership',
            'credit_history_length',
        ]);
    }
}