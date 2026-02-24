"""
seed_raw_lake.py — Direct MongoDB seed for raw_data_lake collection
"""
import random
import string
from datetime import datetime, timezone, timedelta
from pymongo import MongoClient

client = MongoClient("127.0.0.1", 27017)
db = client["edwh_analytics"]
col = db["raw_data_lake"]

levels = ["INFO", "WARN", "ERROR", "DEBUG", "CRITICAL"]
methods = ["GET", "POST", "PUT", "DELETE", "PATCH"]
endpoints = ["/api/orders", "/api/products", "/api/users", "/auth/login", "/api/payments", "/api/reports"]
services = ["order-service", "product-catalog", "auth-service", "payment-gateway", "analytics-engine"]
feedback_types = ["complaint", "praise", "suggestion", "question", "bug_report"]

def rand_str(n=8):
    return ''.join(random.choices(string.ascii_uppercase, k=n))

docs = []
for i in range(100):
    now = datetime.now(timezone.utc) - timedelta(minutes=random.randint(0, 10080))
    log_type = "customer_feedback" if i % 3 == 0 else "server_log"

    if log_type == "server_log":
        level   = random.choice(levels)
        method  = random.choice(methods)
        path    = random.choice(endpoints)
        service = random.choice(services)
        status  = random.randint(400, 503) if level in ["ERROR", "CRITICAL"] else random.randint(200, 201)
        latency = random.randint(5, 5000)
        payload = {
            "source":      service,
            "timestamp":   now.isoformat(),
            "level":       level,
            "message":     f"[{level}] {method} {path} → HTTP {status} ({latency}ms)",
            "http_method": method,
            "path":        path,
            "status_code": status,
            "latency_ms":  latency,
            "request_id":  rand_str(16),
            "ip_address":  f"{random.randint(10,254)}.{random.randint(0,255)}.{random.randint(0,255)}.{random.randint(1,254)}",
            "extra": {
                "memory_mb":   random.randint(128, 4096),
                "cpu_percent": round(random.uniform(1, 100), 2),
                "thread_count": random.randint(4, 128),
            }
        }
    else:
        fb_type   = random.choice(feedback_types)
        sentiment = "positive" if fb_type == "praise" else ("negative" if fb_type == "complaint" else "neutral")
        rating    = random.randint(4,5) if sentiment == "positive" else (random.randint(1,2) if sentiment == "negative" else 3)
        payload = {
            "source":        "customer-feedback-portal",
            "feedback_id":   "FB-" + rand_str(8),
            "customer_id":   "CUST-" + str(random.randint(1, 80)).zfill(5),
            "feedback_type": fb_type,
            "sentiment":     sentiment,
            "rating":        rating,
            "product_id":    "PROD-" + str(random.randint(1, 50)).zfill(4),
            "channel":       random.choice(["web", "mobile", "email", "chat"]),
            "language":      random.choice(["en", "de", "fr", "es"]),
            "submitted_at":  now.isoformat(),
            "tags":          random.sample(["urgent", "billing", "shipping", "quality", "ui", "performance"], k=random.randint(1,3)),
            "resolved":      False,
        }

    docs.append({
        "type":        log_type,
        "source":      payload.get("source", "unknown"),
        "payload":     payload,
        "ingested_at": now,
        "processed":   False,
    })

result = col.insert_many(docs)
print(f"Inserted {len(result.inserted_ids)} raw records into raw_data_lake")
print(f"Total in collection: {col.count_documents({})}")
