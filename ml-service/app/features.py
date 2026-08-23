"""Feature engineering for the credit risk model.

Transforms a raw applicant/loan/ratios payload into a deterministic feature
vector consumed by both the XGBoost classifier and the heuristic scorecard.
"""

from __future__ import annotations

import numpy as np

FEATURE_ORDER = [
    "monthly_income",
    "employment_years",
    "credit_history_length",
    "home_ownership_MORTGAGE",
    "home_ownership_OWN",
    "home_ownership_RENT",
    "loan_amount",
    "interest_rate",
    "term_months",
    "debt_to_income",
    "payment_to_income",
    "cash_flow_coverage",
    "employment_stability",
    "loan_to_income",
]


def build_feature_vector(
    *,
    applicant: dict,
    loan: dict,
    financial_ratios: dict,
) -> dict[str, float]:
    """Build a normalized feature dictionary from the request payload."""

    monthly_income = float(applicant["monthly_income"])
    loan_amount = float(loan["loan_amount"])

    # Employment stability proxy: smooths long tenure so extreme values
    # don't dominate. Range 0..1.
    employment_stability = min(float(applicant["employment_years"]) / 10.0, 1.0)

    # Loan-to-Income ratio: total requested principal vs. annual income.
    annual_income = monthly_income * 12
    loan_to_income = (loan_amount / annual_income) if annual_income > 0 else 10.0

    home_ownership = applicant["home_ownership"]

    features: dict[str, float] = {
        "monthly_income": np.log1p(monthly_income),
        "employment_years": float(applicant["employment_years"]),
        "credit_history_length": float(applicant["credit_history_length"]),
        "home_ownership_MORTGAGE": 1.0 if home_ownership == "MORTGAGE" else 0.0,
        "home_ownership_OWN": 1.0 if home_ownership == "OWN" else 0.0,
        "home_ownership_RENT": 1.0 if home_ownership == "RENT" else 0.0,
        "loan_amount": np.log1p(loan_amount),
        "interest_rate": float(loan["interest_rate"]),
        "term_months": float(loan["term_months"]),
        "debt_to_income": float(financial_ratios["debt_to_income"]),
        "payment_to_income": float(financial_ratios["payment_to_income"]),
        "cash_flow_coverage": float(financial_ratios["cash_flow_coverage"]),
        "employment_stability": employment_stability,
        "loan_to_income": loan_to_income,
    }

    return {k: features[k] for k in FEATURE_ORDER}


def feature_vector_to_array(features: dict[str, float]) -> np.ndarray:
    """Convert an ordered feature dictionary into a 2D numpy array."""
    return np.asarray([[features[k] for k in FEATURE_ORDER]], dtype=np.float64)