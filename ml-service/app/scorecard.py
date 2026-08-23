"""Transparent heuristic scorecard fallback.

Computes a FICO-like credit score on the 300-850 scale using interpretable
point allocations across traditional credit risk dimensions. Also produces
human-readable risk drivers (positive and negative) suitable for SHAP-style
explainability when the ML model is unavailable.
"""

from __future__ import annotations

from .schemas import RiskDriver

# Grade boundaries on the 300-850 score scale.
GRADE_BOUNDS = [
    (800, "AAA"),
    (740, "AA"),
    (700, "A"),
    (660, "BBB"),
    (620, "BB"),
    (580, "B"),
    (500, "C"),
    (0, "D"),
]


def credit_grade_from_score(score: float) -> str:
    """Map a numeric score to a credit grade (AAA lowest risk -> D highest)."""
    for threshold, grade in GRADE_BOUNDS:
        if score >= threshold:
            return grade
    return "D"


def scorecard_points(
    *,
    applicant: dict,
    financial_ratios: dict,
) -> tuple[float, list[RiskDriver]]:
    """Compute a scorecard score (300-850) plus transparent risk drivers.

    Returns:
        A tuple of (score, risk_drivers).
    """

    employment_years = float(applicant["employment_years"])
    credit_history_length = int(applicant["credit_history_length"])
    home_ownership = applicant["home_ownership"]
    dti = float(financial_ratios["debt_to_income"])
    pti = float(financial_ratios["payment_to_income"])

    drivers: list[RiskDriver] = []

    # --- Base score -----------------------------------------------------
    score = 550.0

    # --- Debt-to-Income (largest single weight) -------------------------
    if dti <= 0.20:
        score += 90
        drivers.append(RiskDriver(
            factor="DTI ratio",
            direction="positive",
            impact=0.30,
            description=f"Low DTI ratio ({dti:.0%}) signals strong repayment capacity",
        ))
    elif dti <= 0.35:
        score += 55
        drivers.append(RiskDriver(
            factor="DTI ratio",
            direction="positive",
            impact=0.15,
            description=f"Acceptable DTI ratio ({dti:.0%})",
        ))
    elif dti <= 0.45:
        score += 10
        drivers.append(RiskDriver(
            factor="DTI ratio",
            direction="negative",
            impact=0.20,
            description=f"Elevated DTI ratio ({dti:.0%}) reduces available cash flow",
        ))
    else:
        score -= 70
        drivers.append(RiskDriver(
            factor="DTI ratio",
            direction="negative",
            impact=0.35,
            description=f"High DTI ratio (>45%) penalizes risk score",
        ))

    # --- Payment-to-Income ----------------------------------------------
    if pti <= 0.15:
        score += 45
        drivers.append(RiskDriver(
            factor="Payment-to-Income",
            direction="positive",
            impact=0.15,
            description=f"Low payment burden ({pti:.0%}) keeps income flexible",
        ))
    elif pti <= 0.28:
        score += 20
    else:
        score -= 50
        drivers.append(RiskDriver(
            factor="Payment-to-Income",
            direction="negative",
            impact=0.25,
            description=f"High payment-to-income ({pti:.0%}) strains monthly budget",
        ))

    # --- Employment stability -------------------------------------------
    if employment_years >= 7:
        score += 60
        drivers.append(RiskDriver(
            factor="Employment stability",
            direction="positive",
            impact=0.20,
            description="7+ years stable employment boosts score",
        ))
    elif employment_years >= 2:
        score += 30
    elif employment_years >= 1:
        score += 0
    else:
        score -= 40
        drivers.append(RiskDriver(
            factor="Employment stability",
            direction="negative",
            impact=0.15,
            description="Limited employment history (<1 year) increases uncertainty",
        ))

    # --- Credit history length ------------------------------------------
    if credit_history_length >= 10:
        score += 45
        drivers.append(RiskDriver(
            factor="Credit history",
            direction="positive",
            impact=0.10,
            description=f"{credit_history_length} years of credit history",
        ))
    elif credit_history_length >= 3:
        score += 15
    else:
        score -= 30
        drivers.append(RiskDriver(
            factor="Credit history",
            direction="negative",
            impact=0.10,
            description="Thin credit file (short history) limits confidence",
        ))

    # --- Home ownership --------------------------------------------------
    if home_ownership == "OWN":
        score += 30
    elif home_ownership == "MORTGAGE":
        score += 15
    else:
        score -= 10
        drivers.append(RiskDriver(
            factor="Home ownership",
            direction="negative",
            impact=0.05,
            description="Renting (no real-estate collateral) slightly lowers score",
        ))

    score = max(300.0, min(850.0, score))

    # Ensure at least one positive driver is present for clean UI.
    if not any(d.direction == "positive" for d in drivers):
        drivers.append(RiskDriver(
            factor="Overall profile",
            direction="positive",
            impact=0.05,
            description="Profile meets baseline underwriting criteria",
        ))

    return round(score), drivers


def approval_signal_from_score(score: float) -> str:
    """Map a credit score to an approval signal."""
    if score >= 700:
        return "AUTO_APPROVE"
    if score >= 580:
        return "MANUAL_REVIEW"
    return "AUTO_REJECT"