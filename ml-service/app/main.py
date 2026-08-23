"""FastAPI entrypoint for the CreditScore AI risk assessment microservice."""

from __future__ import annotations

import logging

from fastapi import FastAPI, HTTPException, status
from fastapi.responses import JSONResponse

from . import __version__
from .schemas import RiskAssessmentRequest, RiskAssessmentResponse
from .service import assess_risk

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(name)s %(message)s",
)

app = FastAPI(
    title="CreditScore AI — Risk Assessment Microservice",
    description=(
        "ML-powered probability-of-default estimation with XGBoost and a "
        "transparent heuristic scorecard fallback. Exposes explainable risk "
        "drivers for downstream SHAP-style attribution."
    ),
    version=__version__,
)


@app.get("/health", tags=["ops"])
async def health() -> dict:
    """Liveness/readiness probe."""
    return {"status": "ok", "service": "risk-assessment", "version": __version__}


@app.post(
    "/api/v1/assess/risk",
    response_model=RiskAssessmentResponse,
    status_code=status.HTTP_200_OK,
    tags=["risk"],
)
async def assess_risk_endpoint(payload: RiskAssessmentRequest) -> RiskAssessmentResponse:
    """Assess the probability of default for a loan application."""
    if payload.financial_ratios.monthly_payment <= 0:
        raise HTTPException(
            status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
            detail="monthly_payment must be greater than zero.",
        )

    try:
        return assess_risk(payload)
    except Exception as exc:  # noqa: BLE001
        logging.exception("Unhandled error during risk assessment")
        return JSONResponse(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            content={"detail": "Risk assessment failed", "error": str(exc)},
        )