# AI-Loan / CreditScore AI

An AI-powered loan underwriting and credit-risk assessment platform. It pairs a
**Laravel 12** web application (public application dashboard, admin console, and
JSON API) with a **Python FastAPI** microservice that estimates the probability of
default (PD) using an **XGBoost** classifier — with a transparent heuristic
scorecard fallback and human-readable risk drivers.

> Runs out of the box: the ML model trains itself lazily on a realistic synthetic
> credit dataset, so no external data files or model artifacts are required.

## What it does

- Accepts loan applications through a dashboard form or a REST API.
- Computes traditional financial ratios (monthly payment, DTI, PTI, cash-flow coverage).
- Calls the ML microservice for a probability of default, credit score, credit grade, and approval signal.
- Explains every decision with key risk drivers (SHAP-style, built from feature importances).
- Falls back to a local scorecard when the ML service is unreachable.
- Persists applicants, loan applications, and assessments in MySQL or SQLite.
- Provides an admin console to review, edit, delete, and re-run assessments.

## Architecture

```
┌───────────────────────────────┐        HTTP / JSON          ┌────────────────────────────────┐
│      Laravel 12 (PHP 8.2)     │  ─────────────────────────► │     FastAPI (Python 3.11)      │
│  • Dashboard / Admin / API    │  POST /api/v1/assess/risk   │  • XGBoost classifier (PD)     │
│  • LoanUnderwritingService    │  ◄───────────────────────── │  • Heuristic scorecard         │
│  • FinancialRatioCalculator   │   PD / score / risk drivers │  • Explainability (SHAP-style) │
│  • MySQL / SQLite persistence │                             │  • port 8002                   │
└───────────────────────────────┘                             └────────────────────────────────┘
```

End-to-end workflow:

1. The dashboard (or API) collects applicant and loan data.
2. `FinancialRatioCalculator` derives DTI, PTI, cash-flow coverage, and the monthly payment.
3. `LoanUnderwritingService` builds a normalized payload and POSTs it to the ML microservice.
4. The microservice returns PD, credit score, grade, approval signal, and risk drivers.
5. Laravel persists a `RiskAssessment` record — or uses a local scorecard on failure.

## Tech stack

- **Web app:** Laravel 12, PHP 8.2, Eloquent ORM, MySQL (or SQLite)
- **Frontend:** Blade, Tailwind CSS (CDN), Alpine.js, Vite
- **ML service:** Python 3.11, FastAPI, Pydantic v2, XGBoost, scikit-learn, SHAP, NumPy, pandas

## Repository layout

```
app/
  Http/Controllers/          # dashboard, calculator, loan apps, admin, auth
  Http/Requests/             # form/API validation
  Http/Resources/            # JSON API transformers
  Models/                    # Applicant, LoanApplication, RiskAssessment, User
  Services/                  # LoanUnderwritingService, FinancialRatioCalculator
database/migrations/         # schema
database/seeders/            # seeds test + admin users
resources/views/             # dashboard + admin Blade templates
routes/web.php               # pages + calculator + admin routes
routes/api.php               # JSON API (prefix /api/v1)
ml-service/                  # Python FastAPI ML microservice
ml-service/app/              # main, service, ml_model, features, scorecard, explain, schemas
docs/TUTORIAL.md             # step-by-step usage tutorial
```

## Prerequisites

- PHP 8.2+ and Composer
- Node.js + npm (for the Vite/Tailwind asset build)
- MySQL (or SQLite for a quick zero-config local run)
- Python 3.11 and pip
- XAMPP is convenient on Windows — this repo currently lives under `c:\xampp\htdocs`

## Getting started

### 1. Laravel application

```bash
composer install
npm install
copy .env.example .env      # Windows  (cp .env.example .env on macOS/Linux)
php artisan key:generate
```

Configure the database in `.env`. The shipped `.env` uses MySQL:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ai_loan_project
DB_USERNAME=root
DB_PASSWORD=
```

For a zero-config run, use SQLite instead:

```env
DB_CONNECTION=sqlite
# comment out or remove DB_HOST / DB_PORT / DB_DATABASE / DB_USERNAME / DB_PASSWORD
```

Then create the SQLite file (`database/database.sqlite`) and run:

```bash
php artisan migrate --seed
php artisan serve
# http://127.0.0.1:8000
```

> `--seed` creates the admin login (see **Admin login** below).

### 2. ML microservice

```bash
cd ml-service
python -m venv .venv
.venv\Scripts\activate       # Windows
# source .venv/bin/activate  # macOS/Linux
pip install -r requirements.txt
uvicorn app.main:app --host 0.0.0.0 --port 8002 --reload
```

Verify it is running:

- Health check: http://127.0.0.1:8002/health
- Interactive API docs: http://127.0.0.1:8002/docs

### 3. Point Laravel at the ML service

In the Laravel `.env`:

```env
RISK_ML_BASE_URL=http://127.0.0.1:8002
RISK_ML_API_KEY=
```

(`RISK_ML_API_KEY` is optional and not enforced by the ML service in this build.)

## Admin login

The seeder creates an administrator:

- **Email:** `admin@example.com`
- **Password:** `password`

Log in at http://127.0.0.1:8000/admin/login.

## API reference

### Laravel REST API — prefix `/api/v1`

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/api/v1/loan-applications` | List applications (paginated) |
| `POST` | `/api/v1/loan-applications` | Create an application and run the risk assessment |
| `GET` | `/api/v1/loan-applications/{id}` | Show a single application |

### Financial calculator

| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/api/calculator/compute` | Live monthly-payment and ratio calculation |

### ML microservice — port 8002

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/health` | Liveness/readiness probe |
| `POST` | `/api/v1/assess/risk` | Risk assessment |
| `GET` | `/docs` | Interactive OpenAPI documentation |

Example risk request (see `ml-service/sample_payload.json`):

```json
{
  "applicant": {
    "full_name": "Jane Doe",
    "email": "jane.doe@example.com",
    "monthly_income": 8500.0,
    "employment_years": 8.5,
    "home_ownership": "MORTGAGE",
    "credit_history_length": 12
  },
  "loan": {
    "loan_amount": 35000.0,
    "loan_purpose": "Debt consolidation",
    "interest_rate": 9.75,
    "term_months": 60
  },
  "financial_ratios": {
    "debt_to_income": 0.42,
    "payment_to_income": 0.18,
    "cash_flow_coverage": 1.45,
    "monthly_payment": 749.32
  }
}
```

Example response:

```json
{
  "default_probability": 0.18432,
  "credit_score": 735,
  "credit_grade": "AA",
  "approval_signal": "AUTO_APPROVE",
  "key_risk_drivers": [
    {
      "factor": "Employment stability",
      "direction": "positive",
      "impact": 0.20,
      "description": "7+ years stable employment boosts score"
    }
  ],
  "model_source": "xgboost"
}
```

## How the machine learning works

The ML pipeline lives entirely in `ml-service/app/` and runs inside the FastAPI service.

### 1. Feature engineering — `features.py`

Raw request data is transformed into a deterministic 14-feature vector consumed by
both the XGBoost model and the scorecard. Notable transformations:

- `monthly_income` and `loan_amount` are log-transformed (`log1p`) to compress skew.
- `home_ownership` is one-hot encoded into `MORTGAGE` / `OWN` / `RENT`.
- `employment_stability = min(employment_years / 10, 1)` caps tenure influence to `0..1`.
- `loan_to_income = loan_amount / (monthly_income * 12)`.
- `debt_to_income`, `payment_to_income`, and `cash_flow_coverage` come from the
  Laravel `FinancialRatioCalculator` (or are supplied directly by API clients).

### 2. Model — `ml_model.py`

`DefaultRiskModel` wraps an `XGBClassifier` configured with:

- `n_estimators=250`, `max_depth=5`, `learning_rate=0.08`
- `subsample=0.9`, `colsample_bytree=0.9`, `reg_lambda=1.0`
- `eval_metric="logloss"`, fixed `random_state=42`

The model is **trained lazily** on first use. `predict_proba()` returns the
probability of default (class `1`), clipped to `0..1`. `feature_importance_map()`
returns normalized XGBoost feature importances used for explainability.

### 3. Explainability — `explain.py`

Feature importances are translated into human-readable `RiskDriver` objects with a
factor label, direction (`positive` / `negative`), an impact magnitude, and a short
description. The top drivers are balanced between positive and negative factors so
the output stays interpretable.

### 4. Orchestration and fallback — `service.py` + `scorecard.py`

`assess_risk()` runs the model first. If the classifier fails, it transparently
degrades to the heuristic scorecard:

- **Scorecard** (`scorecard.py`): a FICO-like 300–850 score with interpretable point
  allocations for DTI, PTI, employment stability, credit history, and home ownership.
- **Grade mapping:** `AAA` (≥800) down to `D` (<500).
- **Approval signal:** `AUTO_APPROVE` (PD ≤ 0.25), `AUTO_REJECT` (PD ≥ 0.60), otherwise `MANUAL_REVIEW`.

The Laravel side mirrors this behavior: `LoanUnderwritingService` calls the
microservice, and if the network call fails or returns an error, it runs an
identical local scorecard in PHP so the workflow always completes.

## Where the data comes from

### Training data (synthetic, generated in code)

There are **no external data files**. The XGBoost model is trained on a synthetic
dataset generated on the fly in `ml_model.py::_synthetic_dataset()`:

- 8,000 rows sampled from realistic distributions using NumPy
  (lognormal income/loan amounts, gamma employment tenure, beta DTI/PTI ratios, etc.).
- A latent log-odds formula encodes real credit behavior — higher DTI, PTI, and
  loan-to-income increase risk; longer credit history, higher income, and stable
  employment reduce it.
- A fixed seed (`42`) keeps results reproducible.

This keeps the service runnable with zero setup, but it is **not** a production model.

### Application data (user-submitted)

Real application data is submitted through the dashboard or API and stored in the
`applicants`, `loan_applications`, and `risk_assessments` tables.

### Using real training data in production

To use real historical loan data:

1. Replace the synthetic generator in `_synthetic_dataset()` with a loader that reads
   your historical loans (e.g. a CSV or your own database export).
2. Retrain and serialize the model with `joblib.dump(...)`, then load it in
   `ml_model.py` instead of training lazily.

Good public starting points for credit-risk datasets include LendingClub loan data,
Kaggle's "Give Me Some Credit", the UCI German Credit dataset, and the Home Credit
Default Risk competition.

## Testing

```bash
php artisan test
```

Feature tests (see `tests/Feature/`) cover the loan application flow, including the
graceful fallback when the ML service is down.

## Troubleshooting

- **"Risk microservice unreachable; using local scorecard fallback."** — The ML
  service isn't running. Start it with the `uvicorn` command above and confirm
  `/health` responds.
- **500 from Laravel with a database error** — confirm your `.env` database settings
  and run `php artisan migrate`.
- **Admin login redirects** — make sure you ran `php artisan migrate --seed`, which
  creates `admin@example.com`.

## License

This project uses the Laravel framework, which is open-sourced under the MIT license.
See the Laravel documentation for details.


