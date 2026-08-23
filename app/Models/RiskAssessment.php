<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiskAssessment extends Model
{
    /** @use HasFactory<\Database\Factories\RiskAssessmentFactory> */
    use HasFactory;

    public const STATUS_PROCESSING = 'PROCESSING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_FAILED = 'FAILED';

    public const SIGNAL_AUTO_APPROVE = 'AUTO_APPROVE';
    public const SIGNAL_MANUAL_REVIEW = 'MANUAL_REVIEW';
    public const SIGNAL_AUTO_REJECT = 'AUTO_REJECT';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'loan_application_id',
        'default_probability',
        'credit_grade',
        'approval_signal',
        'key_risk_drivers',
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
            'default_probability' => 'decimal:5',
            'key_risk_drivers' => 'array',
        ];
    }

    /**
     * The risk assessment belongs to a loan application.
     */
    public function loanApplication(): BelongsTo
    {
        return $this->belongsTo(LoanApplication::class);
    }
}