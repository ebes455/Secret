"""
Prediction Model — Random Forest on the analytics_sales_wide table.
Task: Predict whether a sale is 'high value' (binary classification).
Reports: accuracy, precision, recall, F1, feature importance.
"""
import os
import sqlite3
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestClassifier
from sklearn.model_selection import train_test_split, cross_val_score
from sklearn.metrics import classification_report, accuracy_score
from sklearn.preprocessing import LabelEncoder
import json

DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")
RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)


def load_data():
    db = os.path.join(DATA_DIR, "unified.db")
    conn = sqlite3.connect(db)
    df = pd.read_sql("SELECT * FROM analytics_sales_wide", conn)
    conn.close()
    return df


def prepare_features(df):
    df = df.copy()
    le = LabelEncoder()
    df["region_enc"]           = le.fit_transform(df["region"].fillna("Unknown"))
    df["tier_enc"]             = le.fit_transform(df["customer_tier"].fillna("Unknown"))
    df["category_enc"]         = le.fit_transform(df["product_category"].fillna("Unknown"))
    df["country_enc"]          = le.fit_transform(df["customer_country"].fillna("Unknown"))

    features = [
        "quantity", "total_amount", "unit_price", "discount_rate",
        "year", "month", "quarter",
        "region_enc", "tier_enc", "category_enc", "country_enc"
    ]
    X = df[features].fillna(0)
    y = df["is_high_value"].fillna(0).astype(int)
    return X, y, features


def run():
    print("=" * 65)
    print("  AI Module: Prediction Model (Random Forest)")
    print("=" * 65)

    df = load_data()
    X, y, feature_names = prepare_features(df)

    print(f"\n  Dataset: {len(df)} records  |  High-value: {y.sum()} ({y.mean()*100:.1f}%)")

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=0.2, stratify=y, random_state=42
    )

    model = RandomForestClassifier(
        n_estimators=200,
        max_depth=10,
        class_weight="balanced",
        random_state=42,
        n_jobs=-1
    )
    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)
    report = classification_report(y_test, y_pred, target_names=["Normal", "High Value"], output_dict=True)

    print("\n  Classification Report:")
    print(classification_report(y_test, y_pred, target_names=["Normal", "High Value"]))

    # Cross-validation
    cv_scores = cross_val_score(model, X, y, cv=5, scoring="accuracy")
    print(f"  5-Fold CV Accuracy: {cv_scores.mean():.4f} ± {cv_scores.std():.4f}")

    # Feature importance
    importances = pd.DataFrame({
        "feature": feature_names,
        "importance": model.feature_importances_
    }).sort_values("importance", ascending=False)
    print("\n  Top Feature Importances:")
    print(importances.to_string(index=False))

    # Save outputs
    importances.to_csv(os.path.join(RESULTS_DIR, "feature_importances.csv"), index=False)

    metrics = {
        "model": "Random Forest Classifier",
        "total_samples": len(df),
        "train_size": len(X_train),
        "test_size": len(X_test),
        "accuracy": round(accuracy_score(y_test, y_pred), 4),
        "precision_hv": round(report["High Value"]["precision"], 4),
        "recall_hv": round(report["High Value"]["recall"], 4),
        "f1_hv": round(report["High Value"]["f1-score"], 4),
        "cv_accuracy_mean": round(float(cv_scores.mean()), 4),
        "cv_accuracy_std": round(float(cv_scores.std()), 4),
        "top_feature": importances.iloc[0]["feature"]
    }

    with open(os.path.join(RESULTS_DIR, "prediction_metrics.json"), "w") as f:
        json.dump(metrics, f, indent=2)

    print(f"\n✅ Prediction model complete.")
    print(f"   Accuracy: {metrics['accuracy']}  F1 (High Value): {metrics['f1_hv']}  CV: {metrics['cv_accuracy_mean']} ± {metrics['cv_accuracy_std']}")
    return metrics


if __name__ == "__main__":
    run()
