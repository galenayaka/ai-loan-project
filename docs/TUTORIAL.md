# AI-Loan / CreditScore AI — Tutorial

This guide walks you through running and using the application end to end:
starting the services, submitting a loan application, reading the risk result, and
managing applications from the admin console.

## Before you start

Make sure you have completed the setup in the root `README.md`:

1. `composer install` and `npm install`
2. A configured `.env` (database + `RISK_ML_BASE_URL=http://127.0.0.1:8002`)
3. `php artisan migrate --seed`
4. `ml-service/` dependencies installed in a Python virtual environment

## 1. Start the services

You need **two** processes running at the same time.

### Terminal A — the ML microservice (port 8002)

```bash
cd ml-service
.venv\Scripts\activate       # Windows (source .venv/bin/activate on macOS/Linux)
uvicorn app.main:app --host 0.0.0.0 --port 8002 --reload
```

Check it is alive:

```bash
curl http://127.0.0.1:8002/health
# {"status":"ok","service":"risk-assessment","version":"1.0.0"}
```

### Terminal B — the Laravel app (port 8000)

```bash
php artisan serve
```

Open the dashboard at http://127.0.0.1:8000.

> The app still works if the ML service is down — it silently uses a local
> scorecard fallback. But you'll want the ML service up to see `model_source:
> "xgboost"` in results.

## 2. Log in as admin (optional)

The seeder created an admin account:

- **Email:** `admin@example.com`
- **Password:** `password`

Go to http://127.0.0.1:8000/admin/login and sign in.

## 3. Submit a loan application from the dashboard

The public dashboard (http://127.0.0.1:8000) has an application form:

- **Applicant:** full name, email, monthly income, employment years,
  home ownership (`RENT` / `OWN` / `MORTGAGE`), credit history length (years).
- **Loan:** amount, interest rate, term (months), purpose, and existing monthly debt.

1. Fill in the form (sensible defaults are pre-filled).
2. Click **Submit / Run Assessment**.
3. The result panel shows the probability of default (PD), credit grade, and
   approval signal (`AUTO_APPROVE`, `MANUAL_REVIEW`, or `AUTO_REJECT`).
4. The **recent applications** list lets you click into any record to inspect its
   stored risk assessments.

> The dashboard computes `debt_to_income_ratio` from your `monthly_debt` /
> `monthly_income` values before submitting, so keep both fields consistent.

## 4. Understanding the result

A risk assessment includes:

| Field | Meaning |
|---|---|
| `default_probability` | Probability the borrower defaults (0.00–1.00) |
| `credit_score` | A FICO-like 300–850 score |
| `credit_grade` | `AAA` (best) → `D` (worst) |
| `approval_signal` | `AUTO_APPROVE`, `MANUAL_REVIEW`, or `AUTO_REJECT` |
| `key_risk_drivers` | Human-readable factors that moved the decision |
| `model_source` | `xgboost` (ML) or `scorecard` (fallback) |

## 5. Use the financial calculator

The dashboard also surfaces live ratios (monthly payment, DTI, PTI). The same math
is available as an endpoint:

```bash
curl -X POST http://127.0.0.1:8000/api/calculator/compute \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"loan_amount":20000,"interest_rate":9.5,"term_months":60,"monthly_income":5000,"monthly_debt":1000}'
```

Response:

```json
{
  "monthly_payment": 420.04,
  "debt_to_income": 0.2,
  "payment_to_income": 0.084,
  "cash_flow_coverage": 8.52
}
```

## 6. Manage applications in the admin console

After logging in at `/admin/login`:

- **`/admin`** or **`/admin/applications`** — list all applications, filter by
  status, and search by applicant name or email.
- **Click an application** — view full details and its risk assessment(s).
- **`Edit`** — change loan terms or status.
- **`Re-assess`** — re-run the ML/scorecard assessment (creates a new
  `risk_assessments` record).
- **`Delete`** — remove the application (cascades to its assessments).

## 7. Use the REST API directly

### Create an application + assessment

```bash
curl -X POST http://127.0.0.1:8000/api/v1/loan-applications \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "full_name": "Jane Doe",
    "email": "jane@example.com",
    "monthly_income": 8500,
    "employment_years": 8.5,
    "home_ownership": "MORTGAGE",
    "credit_history_length": 12,
    "loan_amount": 35000,
    "loan_purpose": "Debt consolidation",
    "interest_rate": 9.75,
    "term_months": 60,
    "debt_to_income_ratio": 0.42
  }'
```

This returns the created application (HTTP 201) with its nested
`risk_assessments`.

### List applications

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/v1/loan-applications
```

### Show one application

```bash
curl -H "Accept: application/json" http://127.0.0.1:8000/api/v1/loan-applications/1
```

## 8. Test the ML service directly

You can call the Python service without Laravel:

```bash
curl -X POST http://127.0.0.1:8002/api/v1/assess/risk \
  -H "Content-Type: application/json" \
  -d @ml-service/sample_payload.json
```

Interactive docs are at http://127.0.0.1:8002/docs.

## 9. Run the test suite

```bash
php artisan test
```

## Troubleshooting

| Symptom | Fix |
|---|---|
| Result says `scorecard` instead of `xgboost` | Start the ML service (`uvicorn ...`) and check `/health`. |
| Laravel 500 / database error | Fix `.env` DB settings, then `php artisan migrate`. |
| Admin login fails | Run `php artisan migrate --seed` to create `admin@example.com`. |
| Port already in use | Change `--port` and update `RISK_ML_BASE_URL` to match. |
