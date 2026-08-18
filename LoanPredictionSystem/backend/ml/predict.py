"""
predict.py
Called by PHP: python predict.py <base64_json_features>
Loads loan_model.pkl, runs prediction, prints JSON to stdout.
"""

import sys
import json
import base64
import os

def predict(features: dict) -> dict:
    model_path = os.path.join(os.path.dirname(__file__), "loan_model.pkl")

    if os.path.exists(model_path):
        try:
            import pickle
            import numpy as np

            with open(model_path, "rb") as f:
                model = pickle.load(f)

            # Build feature vector in the order the model was trained on
            X = [[
                float(features.get("credit_score", 600)),
                float(features.get("annual_income", 50000)),
                float(features.get("amount", 10000)),
                int(features.get("term_months", 36)),
            ]]

            proba = model.predict_proba(np.array(X))[0]
            # Assumes class 0 = no default, class 1 = default
            default_prob   = round(float(proba[1]), 4)
            approval_prob  = round(float(proba[0]), 4)
            risk_score     = default_prob

            return {
                "risk_score":           risk_score,
                "approval_probability": approval_prob,
                "default_probability":  default_prob,
                "model_version":        "1.0",
            }
        except Exception as e:
            sys.stderr.write(f"Model error: {e}\n")

    # --- Fallback: rule-based heuristic (no model file needed) ---
    credit_score  = float(features.get("credit_score", 600))
    annual_income = max(float(features.get("annual_income", 1)), 1)
    amount        = float(features.get("amount", 0))
    term_months   = max(int(features.get("term_months", 12)), 1)

    monthly_payment = amount / term_months
    monthly_income  = annual_income / 12
    dti             = monthly_payment / monthly_income

    credit_factor   = 1 - ((credit_score - 300) / 550)
    dti_factor      = min(dti * 2, 1.0)

    risk_score      = round(credit_factor * 0.6 + dti_factor * 0.4, 4)
    default_prob    = round(risk_score * 0.9, 4)
    approval_prob   = round(1 - risk_score, 4)

    return {
        "risk_score":           risk_score,
        "approval_probability": approval_prob,
        "default_probability":  default_prob,
        "model_version":        "fallback-1.0",
    }


if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "No features provided"}))
        sys.exit(1)

    try:
        raw      = base64.b64decode(sys.argv[1])
        features = json.loads(raw)
        result   = predict(features)
        print(json.dumps(result))
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)
