"""
Scalability Test — simulates adding 5 new data sources to each model
and measures the effort (time in seconds) and LOC (proxy for effort).
"""
import os
import sys
import sqlite3
import time
import csv
import pandas as pd
import random
from faker import Faker

sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

fake = Faker()
random.seed(99)

DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")
RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)

NEW_SOURCES = 5
NEW_ROWS = 200


def generate_new_source(i):
    """Simulate a new data source: returns a DataFrame of returns data."""
    rows = [{
        "return_id": i * 1000 + j,
        "sale_id": random.randint(1, 1000),
        "customer_id": random.randint(1, 500),
        "product_id": random.randint(1, 500),
        "return_date": fake.date_between("-6m", "today").isoformat(),
        "reason": random.choice(["Defective", "Wrong Item", "Changed Mind", "Late Delivery"]),
        "refund_amount": round(random.uniform(10, 5000), 2),
    } for j in range(NEW_ROWS)]
    return pd.DataFrame(rows)


def add_to_kimball(conn, df, source_num):
    # Add a simple returns dimension/fact
    conn.execute("""CREATE TABLE IF NOT EXISTS fact_returns (
        return_id INTEGER PRIMARY KEY, sale_id INTEGER, customer_id INTEGER,
        product_id INTEGER, return_date TEXT, reason TEXT, refund_amount REAL
    )""")
    df.to_sql("fact_returns", conn, if_exists="append", index=False)
    conn.commit()


def add_to_inmon(conn, df, source_num):
    conn.execute("""CREATE TABLE IF NOT EXISTS returns (
        return_id INTEGER PRIMARY KEY, sale_id INTEGER, customer_id INTEGER,
        product_id INTEGER, return_date TEXT, reason TEXT, refund_amount REAL
    )""")
    df.to_sql("returns", conn, if_exists="append", index=False)
    conn.commit()


def add_to_datavault(conn, df, source_num):
    conn.execute("""CREATE TABLE IF NOT EXISTS hub_return (
        hk_return TEXT PRIMARY KEY, load_dts TEXT, record_source TEXT, return_id INTEGER
    )""")
    conn.execute("""CREATE TABLE IF NOT EXISTS sat_return_details (
        hk_return TEXT, load_dts TEXT, reason TEXT, refund_amount REAL,
        return_date TEXT, PRIMARY KEY(hk_return, load_dts)
    )""")
    import hashlib
    now = "2024-01-01"
    hubs = [{"hk_return": hashlib.md5(f"RET|{r}".encode()).hexdigest(),
              "load_dts": now, "record_source": "SCALE_TEST", "return_id": r}
             for r in df["return_id"]]
    pd.DataFrame(hubs).to_sql("hub_return", conn, if_exists="append", index=False)
    sats = df.copy()
    sats["hk_return"] = sats["return_id"].apply(lambda x: hashlib.md5(f"RET|{x}".encode()).hexdigest())
    sats["load_dts"] = now
    sats[["hk_return","load_dts","reason","refund_amount","return_date"]].to_sql(
        "sat_return_details", conn, if_exists="append", index=False)
    conn.commit()


def add_to_unified(conn, df, source_num):
    conn.execute("""CREATE TABLE IF NOT EXISTS core_returns (
        return_id INTEGER PRIMARY KEY, sale_id INTEGER, customer_id INTEGER,
        product_id INTEGER, return_date TEXT, reason TEXT, refund_amount REAL
    )""")
    df.to_sql("core_returns", conn, if_exists="append", index=False)
    conn.commit()


MODELS = [
    ("Kimball Star Schema", os.path.join(DATA_DIR, "kimball.db"),    add_to_kimball),
    ("Inmon 3NF",           os.path.join(DATA_DIR, "inmon.db"),      add_to_inmon),
    ("Data Vault 2.0",      os.path.join(DATA_DIR, "datavault.db"),  add_to_datavault),
    ("Unified DW + AI",     os.path.join(DATA_DIR, "unified.db"),    add_to_unified),
]


def run():
    print("=" * 65)
    print("  Scalability Test — Adding 5 new sources to each model")
    print("=" * 65)
    records = []

    for model_name, db_path, add_fn in MODELS:
        if not os.path.exists(db_path):
            print(f"\n  ⚠ DB not found: {db_path}")
            continue
        conn = sqlite3.connect(db_path)
        timings = []
        print(f"\n  ▸ {model_name}")
        for i in range(1, NEW_SOURCES + 1):
            df = generate_new_source(i)
            t0 = time.perf_counter()
            add_fn(conn, df, i)
            elapsed = round((time.perf_counter() - t0) * 1000, 2)
            timings.append(elapsed)
            print(f"    Source {i}: {elapsed}ms")
        conn.close()
        records.append({
            "model": model_name,
            "new_sources_added": NEW_SOURCES,
            "total_time_ms": round(sum(timings), 2),
            "avg_time_per_source_ms": round(sum(timings)/len(timings), 2),
            "min_ms": min(timings),
            "max_ms": max(timings),
        })

    df = pd.DataFrame(records)
    out = os.path.join(RESULTS_DIR, "scalability_results.csv")
    df.to_csv(out, index=False)
    print(f"\n✅ Scalability results saved → {out}")
    print(df.to_string(index=False))
    return df


if __name__ == "__main__":
    run()
