#!/usr/bin/env python3
"""
anomaly_detector.py
====================
EDWH Hybrid Analytics — AI-Augmented Anomaly Detection Engine
Uses Isolation Forest (scikit-learn) to detect anomalies in transaction data.

Usage:
    python scripts/anomaly_detector.py [--host 127.0.0.1] [--port 27017] [--db edwh_analytics]

Output:
    JSON array to stdout — each item is a flagged anomaly record.
"""

import sys
import json
import argparse
import hashlib
from datetime import datetime, timezone

try:
    from pymongo import MongoClient
    from sklearn.ensemble import IsolationForest
    from sklearn.preprocessing import StandardScaler
    import numpy as np
except ImportError as e:
    print(json.dumps({
        "error": f"Missing dependency: {e}. Run: pip install pymongo scikit-learn numpy"
    }))
    sys.exit(1)


def get_severity(score: float) -> str:
    """Map anomaly score to severity label."""
    if score < -0.3:
        return "high"
    elif score < -0.1:
        return "medium"
    else:
        return "low"


def detect_transaction_anomalies(db, contamination: float = 0.08) -> list:
    """Detect anomalies in link_transactions using Isolation Forest."""
    collection = db["link_transactions"]

    # Fetch all transactions with amounts
    cursor = collection.find(
        {"amount": {"$exists": True, "$ne": None}},
        {"_id": 1, "amount": 1, "quantity": 1, "hk_customer": 1, "hk_product": 1,
         "transaction_id": 1, "status": 1, "load_dts": 1}
    )

    records = list(cursor)

    if len(records) < 10:
        return []

    # Feature engineering
    ids = []
    features = []

    for r in records:
        amount = float(r.get("amount", 0) or 0)
        qty    = int(r.get("quantity", 1) or 1)
        rev    = amount * qty   # total revenue proxy

        ids.append(str(r["_id"]))
        features.append([amount, qty, rev])

    X = np.array(features)
    scaler = StandardScaler()
    X_scaled = scaler.fit_transform(X)

    # Train Isolation Forest
    clf = IsolationForest(
        n_estimators=200,
        contamination=contamination,
        max_samples="auto",
        random_state=42,
        n_jobs=-1
    )
    clf.fit(X_scaled)

    scores      = clf.decision_function(X_scaled)  # higher = more normal
    predictions = clf.predict(X_scaled)             # -1 = anomaly, 1 = normal

    anomalies = []
    for i, (record, pred, score) in enumerate(zip(records, predictions, scores)):
        if pred == -1:
            amount = float(record.get("amount", 0) or 0)
            qty    = int(record.get("quantity", 1) or 1)
            severity = get_severity(float(score))

            reasons = []
            mean_amount = float(np.mean([f[0] for f in features]))
            std_amount  = float(np.std([f[0] for f in features]))
            if abs(amount - mean_amount) > 2.5 * std_amount:
                reasons.append(f"Amount ${amount:.2f} is {abs(amount - mean_amount) / std_amount:.1f}σ from mean (${mean_amount:.2f})")
            if qty > 15:
                reasons.append(f"Unusually high quantity: {qty} units")
            if amount * qty > 50000:
                reasons.append(f"Extreme revenue per transaction: ${amount * qty:.2f}")
            if not reasons:
                reasons.append("Multi-dimensional outlier detected by Isolation Forest")

            anomalies.append({
                "source_collection": "link_transactions",
                "source_id":         str(record["_id"]),
                "anomaly_score":     round(float(score), 6),
                "severity":          severity,
                "reason":            " | ".join(reasons),
                "field_values": {
                    "transaction_id": record.get("transaction_id"),
                    "amount":         amount,
                    "quantity":       qty,
                    "status":         record.get("status"),
                    "hk_customer":    record.get("hk_customer"),
                    "hk_product":     record.get("hk_product"),
                },
                "flagged_at": datetime.now(timezone.utc).isoformat(),
                "reviewed":   False,
            })

    return anomalies


def detect_missing_field_anomalies(db) -> list:
    """Flag raw_data_lake records with missing critical fields."""
    collection = db["raw_data_lake"]
    anomalies  = []

    cursor = collection.find(
        {"type": "server_log"},
        {"_id": 1, "payload": 1, "source": 1}
    ).limit(200)

    for record in cursor:
        payload   = record.get("payload", {}) or {}
        critical  = ["status_code", "latency_ms", "http_method"]
        missing   = [f for f in critical if f not in payload or payload[f] is None]

        if missing or (payload.get("latency_ms", 0) or 0) > 4000:
            reason_parts = []
            if missing:
                reason_parts.append(f"Missing critical fields: {', '.join(missing)}")
            if (payload.get("latency_ms", 0) or 0) > 4000:
                reason_parts.append(f"High latency detected: {payload.get('latency_ms')}ms")

            anomalies.append({
                "source_collection": "raw_data_lake",
                "source_id":         str(record["_id"]),
                "anomaly_score":     -0.5,
                "severity":          "medium",
                "reason":            " | ".join(reason_parts) or "Quality anomaly",
                "field_values": {
                    "source":    record.get("source"),
                    "log_level": payload.get("level"),
                    "latency_ms": payload.get("latency_ms"),
                    "status_code": payload.get("status_code"),
                },
                "flagged_at": datetime.now(timezone.utc).isoformat(),
                "reviewed":   False,
            })

    return anomalies


def main():
    parser = argparse.ArgumentParser(description="EDWH Anomaly Detection Engine")
    parser.add_argument("--host", default="127.0.0.1")
    parser.add_argument("--port", type=int, default=27017)
    parser.add_argument("--db",   default="edwh_analytics")
    parser.add_argument("--contamination", type=float, default=0.08)
    args = parser.parse_args()

    try:
        client = MongoClient(args.host, args.port, serverSelectionTimeoutMS=5000)
        client.server_info()  # Force connection
        db = client[args.db]
    except Exception as e:
        print(json.dumps({"error": f"MongoDB connection failed: {e}"}))
        sys.exit(1)

    anomalies = []
    anomalies.extend(detect_transaction_anomalies(db, args.contamination))
    anomalies.extend(detect_missing_field_anomalies(db))

    # Output JSON to stdout for Laravel to consume
    print(json.dumps(anomalies, default=str))
    sys.exit(0)


if __name__ == "__main__":
    main()
