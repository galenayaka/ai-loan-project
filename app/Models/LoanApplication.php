<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoanApplication extends Model
{
    /** @use HasFactory<\Database\Factories\LoanApplicationFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_UNDER_REVIEW = 'UNDER_REVIEW';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'applicant_id',
        'loan_amount',
        'loan_purpose',
        'interest_rate',
        'term_months',
        'debt_to_income_ratio',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'loan_amount' => 'decimal:2',
            'interest_rate' => 'decimal:2',
            'term_months' => 'integer',
            'debt_to_income_ratio' => 'decimal:3',
        ];
    }

    /**
     * The loan application belongs to an applicant.
     */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * A loan application can have many risk assessments (retries / re-runs).
     */
    public function riskAssessments(): HasMany
    {
        return $this->hasMany(RiskAssessment::class);
    }

    /**
     * Get the most recent completed risk assessment.
     */
    public function latestRiskAssessment(): ?RiskAssessment
    {
        return $this->riskAssessments()
            ->where('status', RiskAssessment::STATUS_COMPLETED)
            ->latest()
            ->first();
    }
}