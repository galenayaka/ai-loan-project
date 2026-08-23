"""Pydantic request/response schemas for the CreditScore AI risk service."""

from __future__ import annotations

from typing import Literal

from pydantic import BaseModel, EmailStr, Field


class ApplicantMetrics(BaseModel):
    """Demographic and employment metrics for the applicant."""

    full_name: str = Field(..., max_length=255)
    email: EmailStr
    monthly_income: float = Field(..., gt=0, description="Gross monthly income")
    employment_years: float = Field(..., ge=0, le=60)
    home_ownership: Literal["RENT", "OWN", "MORTGAGE"]
    credit_history_length: int = Field(..., ge=0, le=80, description="Years of credit history")


class LoanTerms(BaseModel):
    """Loan product terms being requested."""

    loan_amount: float = Field(..., gt=0)
    loan_purpose: str = Field(..., max_length=120)
    interest_rate: float = Field(..., ge=0, le=100, description="Annual rate in percent")
    term_months: int = Field(..., ge=1, le=480)


class FinancialRatios(BaseModel):
    """Derived financial ratios computed upstream (or by this service)."""

    debt_to_income: float = Field(..., ge=0)
    payment_to_income: float = Field(..., ge=0)
    cash_flow_coverage: float = Field(..., ge=-10)
    monthly_payment: float = Field(..., ge=0)


class RiskAssessmentRequest(BaseModel):
    """Full risk assessment request payload."""

    applicant: ApplicantMetrics
    loan: LoanTerms
    financial_ratios: FinancialRatios


class RiskDriver(BaseModel):
    """A single explainability factor (positive or negative)."""

    factor: str
    direction: Literal["positive", "negative"]
    impact: float = Field(..., description="Relative impact magnitude (0-1)")
    description: str


class RiskAssessmentResponse(BaseModel):
    """Full risk assessment response payload."""

    default_probability: float = Field(..., ge=0, le=1, description="Probability of default (PD)")
    credit_score: int = Field(..., ge=300, le=850)
    credit_grade: str = Field(..., description="Credit grade from AAA to D")
    approval_signal: Literal["AUTO_APPROVE", "MANUAL_REVIEW", "AUTO_REJECT"]
    key_risk_drivers: list[RiskDriver]
    model_source: Literal["xgboost", "scorecard"] = "xgboost"