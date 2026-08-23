"""XGBoost classifier pipeline with a heuristic scorecard fallback.

The classifier is trained lazily on a synthetic-but-realistic dataset the first
time it is needed (so the service runs with zero external model files). In
production this can be swapped for a serialized model loaded from disk.
"""

from __future__ import annotations

import logging

import numpy as np
from sklearn.model_selection import train_test_split
from xgboost import XGBClassifier

from .features import FEATURE_ORDER, build_feature_vector, feature_vector_to_array

logger = logging.getLogger(__name__)


class DefaultRiskModel:
    """Wraps the XGBoost model and exposes probability + feature importances."""

    def __init__(self, seed: int = 42) -> None:
        self.seed = seed
        self._model: XGBClassifier | None = None
        self._feature_names = FEATURE_ORDER

    def _ensure_trained(self) -> None:
        if self._model is not None:
            return

        logger.info("Training XGBoost classifier on synthetic credit dataset...")
        X, y = self._synthetic_dataset()
        X_train, X_valid, y_train, y_valid = train_test_split(
            X, y, test_size=0.2, random_state=self.seed, stratify=y,
        )

        self._model = XGBClassifier(
            n_estimators=250,
            max_depth=5,
            learning_rate=0.08,
            subsample=0.9,
            colsample_bytree=0.9,
            reg_lambda=1.0,
            eval_metric="logloss",
            random_state=self.seed,
            n_jobs=1,
        )
        self._model.fit(
            X_train,
            y_train,
            eval_set=[(X_valid, y_valid)],
            verbose=False,
        )

    def _synthetic_dataset(self) -> tuple[np.ndarray, np.ndarray]:
        """Generate a realistic synthetic dataset covering the feature space.

        The dataset encodes standard risk-orthogonal behavior: higher DTI,
        payment-to-income and loan-to-income increase default risk; longer
        credit history, higher income and employment stability reduce it.
        """
        rng = np.random.default_rng(self.seed)
        n = 8000

        monthly_income = rng.lognormal(mean=8.5, sigma=0.6, size=n)
        employment_years = rng.gamma(shape=2.0, scale=3.0, size=n)
        credit_history_length = rng.integers(0, 35, size=n).astype(float)

        home = rng.integers(0, 3, size=n)
        home_mortgage = (home == 0).astype(float)
        home_own = (home == 1).astype(float)
        home_rent = (home == 2).astype(float)

        loan_amount = rng.lognormal(mean=9.5, sigma=0.9, size=n)
        interest_rate = rng.uniform(3.0, 28.0, size=n)
        term_months = rng.choice([12, 24, 36, 48, 60, 72, 84, 120], size=n).astype(float)

        debt_to_income = rng.beta(2.0, 5.0, size=n) * 1.4
        payment_to_income = rng.beta(2.0, 6.0, size=n) * 0.7

        cash_flow_coverage = (
            1.2
            - 2.5 * debt_to_income
            - 2.0 * payment_to_income
            + rng.normal(0, 0.3, size=n)
        )

        employment_stability = np.minimum(employment_years / 10.0, 1.0)
        loan_to_income = loan_amount / (monthly_income * 12)

        X = np.column_stack(
            [
                np.log1p(monthly_income),
                employment_years,
                credit_history_length,
                home_mortgage,
                home_own,
                home_rent,
                np.log1p(loan_amount),
                interest_rate,
                term_months,
                debt_to_income,
                payment_to_income,
                cash_flow_coverage,
                employment_stability,
                loan_to_income,
            ]
        )

        # Latent log-odds — risk factors vs. protectors.
        logits = (
            -0.8 * np.log1p(monthly_income) / 8.5
            - 0.15 * np.minimum(employment_years, 10)
            - 0.12 * np.minimum(credit_history_length, 20)
            + 0.2 * home_rent
            - 0.1 * home_own
            + 0.15 * np.log1p(loan_amount) / 9.5
            + 0.05 * interest_rate
            + 4.5 * debt_to_income
            + 3.0 * payment_to_income
            - 0.35 * cash_flow_coverage
            + 1.2 * loan_to_income
            + rng.normal(0, 0.5, size=n)
        )
        prob = 1.0 / (1.0 + np.exp(-logits))
        y = (prob > rng.uniform(size=n)).astype(int)

        return X, y

    def predict_proba(self, features: dict[str, float]) -> float:
        """Return the probability of default for a single feature vector."""
        self._ensure_trained()
        X = feature_vector_to_array(features)
        proba = self._model.predict_proba(X)[0, 1]
        return float(np.clip(proba, 0.0, 1.0))

    def feature_importance_map(self) -> dict[str, float]:
        """Return normalized feature importances keyed by feature name."""
        self._ensure_trained()
        importance = self._model.feature_importances_
        total = float(importance.sum()) or 1.0
        return {name: float(v / total) for name, v in zip(self._feature_names, importance)}


default_risk_model = DefaultRiskModel()