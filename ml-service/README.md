# CreditScore AI — ML Microservice

FastAPI microservice (Port 8002) that estimates probability of default (PD)
using an XGBoost classifier, with a transparent heuristic scorecard fallback
and explainable risk drivers.

## Stack

- Python 3.11
- FastAPI + Pydantic v2
- XGBoost (primary ML classifier)
- Scikit-learn (data splitting / preprocessing)
- SHAP (explainability; feature importances used as a dependency-free proxy)
- NumPy / pandas

## Setup

```bash
cd ml-service
python -m venv .venv
# Windows:
.venv\Scripts\activate
# macOS/Linux:
source .venv/bin/activate

pip install -r requirements.txt
```

## Run

```bash
uvicorn app.main:app --host 0.0.0.0 --port 8002 --reload
```

The service exposes:

- `GET  /health` — liveness probe
- `POST /api/v1/assess/risk` — risk assessment endpoint
- `GET  /docs` — interactive OpenAPI documentation

## Example request

See `sample_payload.json` for a ready-to-use request body:

```bash
curl -X POST http://127.0.0.1:8002/api/v1/assess/risk \
  -H "Content-Type: application/json" \
  -d @sample_payload.json
```

## Response shape

```json
{
  "default_probability": 0.18432,
  "credit_score": 735,
  "credit_grade": "AA",
  "approval_signal": "AUTO_APPROVE",
  "key_risk_drivers": [
    {
      "factor": "Debt-to-Income ratio",
      "direction": "negative",
      "impact": 0.35,
      "description": "High DTI ratio (>45%) penalizes risk score"
    }
  ],
  "model_source": "xgboost"
}
```

## Design notes

- The XGBoost model trains lazily on a realistic synthetic dataset so the
  service runs with zero external model artifacts. Swap `app/ml_model.py`
  for a serialized model in production.
- If the classifier fails at runtime, `app/service.py` gracefully degrades
  to the heuristic scorecard (`app/scorecard.py`) and still returns a valid,
  explainable response.