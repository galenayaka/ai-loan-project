"""Orchestrates the risk assessment: ML model + scorecard + explainability."""

from __future__ import annotations

import logging

from .explain import model_risk_drivers
from .features import build_feature_vector
from .ml_model import default_risk_model
from .schemas import (
    RiskAssessmentRequest,
    RiskAssessmentResponse,
    RiskDriver,
)
from .scorecard import (
    approval_signal_from_score,
    credit_grade_from_score,
    scorecard_points,
)

logger = logging.getLogger(__name__)


def _probability_from_score(score: float) -> float:
    """Approximate PD from a 300-850 scorecard score.

    Uses an inverse-logit mapping with plausible default rates: AAA (~780+)
    maps near 1-2% PD while D (<500) maps near 60%+ PD.
    """
    # Center of the scale ~575 maps to ~18% PD; spread controls steepness.
    logit = (575.0 - score) / 90.0
    return 1.0 / (1.0 + (10.0 ** (-logit)))


def assess_risk(payload: RiskAssessmentRequest) -> RiskAssessmentResponse:
    """Run the full assessment pipeline.

    Primary: XGBoost probability of default with importances turned into
    interpretable drivers. Fallback: heuristic scorecard if the classifier
    is unavailable (model load error, etc.).
    """
    applicant = payload.applicant.model_dump()
    loan = payload.loan.model_dump()
    ratios = payload.financial_ratios.model_dump()

    features = build_feature_vector(
        applicant=applicant,
        loan=loan,
        financial_ratios=ratios,
    )

    model_source: str = "xgboost"
    probability: float
    drivers: list[RiskDriver]

    try:
        probability = default_risk_model.predict_proba(features)
        drivers = model_risk_drivers(
            applicant=applicant,
            loan=loan,
            financial_ratios=ratios,
        )

        # Convert model PD into a comparable score for grading.
        score = round(850 - probability * 550)
        grade = credit_grade_from_score(score)

        # Derive the signal from the calibrated PD thresholds.
        if probability <= 0.25:
            signal = "AUTO_APPROVE"
        elif probability >= 0.60:
            signal = "AUTO_REJECT"
        else:
            signal = "MANUAL_REVIEW"

    except Exception as exc:  # noqa: BLE001 - graceful fallback
        logger.warning("XGBoost assessment failed, using scorecard fallback: %s", exc)
        score, drivers = scorecard_points(applicant=applicant, financial_ratios=ratios)
        probability = _probability_from_score(score)
        grade = credit_grade_from_score(score)
        signal = approval_signal_from_score(score)
        model_source = "scorecard"

    return RiskAssessmentResponse(
        default_probability=round(probability, 5),
        credit_score=score,
        credit_grade=grade,
        approval_signal=signal,
        key_risk_drivers=drivers,
        model_source=model_source,
    )