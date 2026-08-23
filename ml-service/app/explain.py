"""Explainability: build top risk drivers from model feature importances.

When SHAP values are available they provide localized attribution; this module
uses global feature importances as an interpretable proxy and overlays them
with the transparent scorecard drivers so the output is always human-readable.
"""

from __future__ import annotations

import logging

from .features import build_feature_vector
from .ml_model import default_risk_model
from .schemas import RiskDriver

logger = logging.getLogger(__name__)

# Human-friendly labels and directional semantics for each model feature.
_FEATURE_META = {
    "monthly_income": ("Income level", "higher-is-better"),
    "employment_years": ("Employment tenure", "higher-is-better"),
    "credit_history_length": ("Credit history length", "higher-is-better"),
    "home_ownership_MORTGAGE": ("Home ownership (mortgage)", "neutral"),
    "home_ownership_OWN": ("Home ownership (owned)", "higher-is-better"),
    "home_ownership_RENT": ("Home ownership (renting)", "higher-is-risk"),
    "loan_amount": ("Loan amount", "higher-is-risk"),
    "interest_rate": ("Interest rate", "higher-is-risk"),
    "term_months": ("Loan term", "higher-is-risk"),
    "debt_to_income": ("Debt-to-Income ratio", "higher-is-risk"),
    "payment_to_income": ("Payment-to-Income ratio", "higher-is-risk"),
    "cash_flow_coverage": ("Cash flow coverage", "higher-is-better"),
    "employment_stability": ("Employment stability", "higher-is-better"),
    "loan_to_income": ("Loan-to-Income ratio", "higher-is-risk"),
}


def _driver_for_feature(name: str, importance: float, features: dict[str, float]) -> RiskDriver | None:
    """Translate a single feature importance into a human-readable driver."""
    if importance < 0.02:
        return None

    label, semantic = _FEATURE_META[name]
    value = features.get(name, 0.0)

    if semantic == "higher-is-risk":
        direction = "negative" if value > 0.5 else "positive"
        description = f"{label} contributes materially to default risk"
    elif semantic == "higher-is-better":
        direction = "positive"
        description = f"{label} strengthens the applicant's repayment profile"
    else:
        direction = "positive"
        description = f"{label} contributes to the overall assessment"

    # Override direction for clearly risky raw metrics.
    if name == "debt_to_income" and features.get("debt_to_income", 0) > 0.45:
        direction = "negative"
        description = f"High DTI ratio (>45%) penalizes risk score"
    if name == "payment_to_income" and features.get("payment_to_income", 0) > 0.28:
        direction = "negative"
        description = f"High payment-to-income ({features['payment_to_income']:.0%}) strains monthly budget"

    return RiskDriver(
        factor=label,
        direction=direction,
        impact=round(min(importance * 3.0, 0.5), 3),
        description=description,
    )


def model_risk_drivers(
    *,
    applicant: dict,
    loan: dict,
    financial_ratios: dict,
    top_n: int = 6,
) -> list[RiskDriver]:
    """Compute top positive/negative drivers using model importances."""
    features = build_feature_vector(
        applicant=applicant,
        loan=loan,
        financial_ratios=financial_ratios,
    )
    importances = default_risk_model.feature_importance_map()

    drivers: list[RiskDriver] = []
    for name, importance in sorted(importances.items(), key=lambda kv: kv[1], reverse=True):
        driver = _driver_for_feature(name, importance, features)
        if driver is not None:
            drivers.append(driver)

    positives = [d for d in drivers if d.direction == "positive"]
    negatives = [d for d in drivers if d.direction == "negative"]

    # Balance the selection: up to top_n total, preserving both signs.
    selected = (negatives[:top_n // 2] + positives[:top_n // 2])[:top_n]
    return selected