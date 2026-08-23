<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreApplicantRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:applicants,email'],
            'monthly_income' => ['required', 'numeric', 'min:0'],
            'employment_years' => ['required', 'numeric', 'min:0', 'max:60'],
            'home_ownership' => ['required', 'in:RENT,OWN,MORTGAGE'],
            'credit_history_length' => ['required', 'integer', 'min:0', 'max:80'],
        ];
    }
}