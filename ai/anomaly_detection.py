"""
Anomaly Detection — Isolation Forest on finance transaction data from Unified DW.
Reports: precision, recall, F1, confusion matrix.
"""
import os
import sqlite3
import pandas as pd
import numpy as np
from sklearn.ensemble import IsolationForest
from sklearn.metrics import classification_report, confusion_matrix
from sklearn.preprocessing import LabelEncoder
import json

DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")
RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)


def load_finance_data():
    db = os.path.join(DATA_DIR, "unified.db")
    conn = sqlite3.connect(db)
    df = pd.read_sql("SELECT * FROM core_finance", conn)
    conn.close()
    return df


def prepare_features(df):
    df = df.copy()
    le = LabelEncoder()
    df["type_enc"] = le.fit_transform(df["type"].fillna("Unknown"))
    df["currency_enc"] = le.fit_transform(df["currency"].fillna("USD"))
    df["txn_date"] = pd.to_datetime(df["txn_date"], errors="coerce")
    df["day_of_week"] = df["txn_date"].dt.dayofweek.fillna(0)
    df["month"] = df["txn_date"].dt.month.fillna(1)
    features = ["amount", "account_id", "type_enc", "currency_enc", "day_of_week", "month"]
    X = df[features].fillna(0)
    y = df["is_anomaly"].fillna(0).astype(int)
    return X, y


def run():
    print("=" * 65)
    print("  AI Module: Anomaly Detection (Isolation Forest)")
    print("=" * 65)

    df = load_finance_data()
    X, y_true = prepare_features(df)

    print(f"\n  Dataset: {len(df)} transactions, {y_true.sum()} labeled anomalies ({y_true.mean()*100:.1f}%)")

    model = IsolationForest(
        n_estimators=200,
        contamination=float(y_true.mean()),
        max_samples="auto",
        random_state=42
    )
    model.fit(X)

    raw_preds = model.predict(X)
    y_pred = (raw_preds == -1).astype(int)

    report = classification_report(y_true, y_pred, target_names=["Normal", "Anomaly"], output_dict=True)
    cm = confusion_matrix(y_true, y_pred).tolist()

    print("\n  Classification Report:")
    print(classification_report(y_true, y_pred, target_names=["Normal", "Anomaly"]))
    print(f"  Confusion Matrix: {cm}")

    # Anomaly scores
    scores = model.decision_function(X)
    df["anomaly_score"] = scores
    df["predicted_anomaly"] = y_pred

    # Save outputs
    df.to_csv(os.path.join(RESULTS_DIR, "anomaly_predictions.csv"), index=False)

    metrics = {
        "model": "Isolation Forest",
        "total_samples": len(df),
        "true_anomalies": int(y_true.sum()),
        "predicted_anomalies": int(y_pred.sum()),
        "precision_anomaly": round(report["Anomaly"]["precision"], 4),
        "recall_anomaly": round(report["Anomaly"]["recall"], 4),
        "f1_anomaly": round(report["Anomaly"]["f1-score"], 4),
        "accuracy": round(report["accuracy"], 4),
        "confusion_matrix": cm
    }

    with open(os.path.join(RESULTS_DIR, "anomaly_metrics.json"), "w") as f:
        json.dump(metrics, f, indent=2)

    print(f"\n✅ Anomaly detection complete.")
    print(f"   Precision: {metrics['precision_anomaly']}  "
          f"Recall: {metrics['recall_anomaly']}  "
          f"F1: {metrics['f1_anomaly']}")

    return metrics


if __name__ == "__main__":
    run()
