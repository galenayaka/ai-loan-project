<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLoanApplicationRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'loan_amount' => ['required', 'numeric', 'min:0.01'],
            'loan_purpose' => ['required', 'string', 'max:120'],
            'interest_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'term_months' => ['required', 'integer', 'min:1', 'max:480'],
            'debt_to_income_ratio' => ['required', 'numeric', 'min:0', 'max:10'],
            'status' => ['required', Rule::in(['PENDING', 'APPROVED', 'REJECTED', 'UNDER_REVIEW'])],
        ];
    }
}