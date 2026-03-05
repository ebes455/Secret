"""
ETL Runner — loads all 50+ simulated sources into all 4 DW models.
Tracks: integration success rate, historical tracking %, elapsed time.
"""
import os
import sys
import time
import pandas as pd

# Make sure models are importable
sys.path.insert(0, os.path.join(os.path.dirname(__file__), ".."))

from models import kimball_star_schema, inmon_3nf, data_vault_2, unified_dw

BASE_RAW = os.path.join(os.path.dirname(__file__), "..", "data", "raw")
RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)


def count_sources():
    total = 0
    for _, _, files in os.walk(BASE_RAW):
        total += len(files)
    return total


def run_etl_model(name, build_fn):
    print(f"\n  ▸ Running ETL for [{name}]...")
    try:
        elapsed = build_fn(verbose=True)
        return {"model": name, "status": "SUCCESS", "build_time_s": elapsed, "error": None}
    except Exception as e:
        print(f"    ✗ ERROR: {e}")
        return {"model": name, "status": "FAILED", "build_time_s": None, "error": str(e)}


def compute_integration_rate(results):
    """% of models that integrated without error."""
    success = sum(1 for r in results if r["status"] == "SUCCESS")
    return round(success / len(results) * 100, 1)


def compute_historical_tracking():
    """
    Check the Unified DW SCD2 table to measure % historical tracking.
    Since we simulate 2 versions per customer, we expect 2 rows per customer.
    """
    import sqlite3
    db = os.path.join(os.path.dirname(__file__), "..", "data", "unified.db")
    if not os.path.exists(db):
        return 0.0
    conn = sqlite3.connect(db)
    total = pd.read_sql("SELECT COUNT(*) AS n FROM hist_customer_scd2", conn).iloc[0, 0]
    current = pd.read_sql("SELECT COUNT(*) AS n FROM hist_customer_scd2 WHERE is_current=1", conn).iloc[0, 0]
    conn.close()
    history_tracked = total - current
    historical_pct = round(history_tracked / max(current, 1) * 100, 1)
    return historical_pct


def main():
    print("=" * 65)
    print("  ETL Runner — Unified Data Warehouse Research Project")
    print("=" * 65)

    total_sources = count_sources()
    print(f"\n📂 Total raw data sources found: {total_sources}")

    models = [
        ("Kimball Star Schema", kimball_star_schema.build),
        ("Inmon 3NF",           inmon_3nf.build),
        ("Data Vault 2.0",      data_vault_2.build),
        ("Unified DW + AI",     unified_dw.build),
    ]

    results = []
    t_total = time.time()
    for name, fn in models:
        results.append(run_etl_model(name, fn))
    total_time = round(time.time() - t_total, 2)

    # Metrics
    integration_rate = compute_integration_rate(results)
    historical_pct   = compute_historical_tracking()

    print("\n" + "=" * 65)
    print("  ETL SUMMARY")
    print("=" * 65)
    print(f"  Sources processed     : {total_sources}")
    print(f"  Models built          : {len(results)}")
    print(f"  Integration success % : {integration_rate}%")
    print(f"  Historical tracking % : {historical_pct}%")
    print(f"  Total ETL time        : {total_time}s")
    print("=" * 65)

    # Save results
    df = pd.DataFrame(results)
    df["total_sources"] = total_sources
    df["integration_success_pct"] = integration_rate
    df["historical_tracking_pct"] = historical_pct
    out = os.path.join(RESULTS_DIR, "etl_results.csv")
    df.to_csv(out, index=False)
    print(f"\n✅ ETL results saved → {out}")


if __name__ == "__main__":
    main()
