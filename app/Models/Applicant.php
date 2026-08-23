<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'email',
        'monthly_income',
        'employment_years',
        'home_ownership',
        'credit_history_length',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_income' => 'decimal:2',
            'employment_years' => 'decimal:1',
            'credit_history_length' => 'integer',
        ];
    }

    /**
     * An applicant can have many loan applications.
     */
    public function loanApplications(): HasMany
    {
        return $this->hasMany(LoanApplication::class);
    }
}