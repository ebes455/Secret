"""
Benchmark Queries — measures avg query response time (ms) across all 4 DW models.
Runs 5 standardized queries per model and records timings.
"""
import sqlite3
import time
import os
import pandas as pd

RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)

DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")

QUERIES = {
    "Kimball Star Schema": {
        "db": os.path.join(DATA_DIR, "kimball.db"),
        "queries": [
            ("Total sales by region",
             "SELECT region, SUM(total_amount) FROM fact_sales GROUP BY region"),
            ("Top 10 customers by spend",
             "SELECT c.name, SUM(f.total_amount) AS total FROM fact_sales f JOIN dim_customer c ON f.customer_sk=c.customer_sk GROUP BY c.name ORDER BY total DESC LIMIT 10"),
            ("Monthly sales trend",
             "SELECT d.year, d.month, SUM(f.total_amount) FROM fact_sales f JOIN dim_date d ON f.date_sk=d.date_sk GROUP BY d.year, d.month"),
            ("Sales by product category",
             "SELECT p.category, COUNT(*) AS sales_count, SUM(f.total_amount) FROM fact_sales f JOIN dim_product p ON f.product_sk=p.product_sk GROUP BY p.category"),
            ("Avg quantity sold per region",
             "SELECT region, AVG(quantity) FROM fact_sales GROUP BY region"),
        ]
    },
    "Inmon 3NF": {
        "db": os.path.join(DATA_DIR, "inmon.db"),
        "queries": [
            ("Total sales by region",
             "SELECT region, SUM(total_amount) FROM sales GROUP BY region"),
            ("Top 10 customers by spend",
             "SELECT c.name, SUM(s.total_amount) AS total FROM sales s JOIN customers c ON s.customer_id=c.customer_id GROUP BY c.name ORDER BY total DESC LIMIT 10"),
            ("Monthly sales trend",
             "SELECT strftime('%Y', sale_date) AS yr, strftime('%m', sale_date) AS mo, SUM(total_amount) FROM sales GROUP BY yr, mo"),
            ("Sales by product category",
             "SELECT cat.name, COUNT(*) AS sales_count FROM sales s JOIN products p ON s.product_id=p.product_id JOIN categories cat ON p.category_id=cat.category_id GROUP BY cat.name"),
            ("Avg quantity sold per region",
             "SELECT region, AVG(quantity) FROM sales GROUP BY region"),
        ]
    },
    "Data Vault 2.0": {
        "db": os.path.join(DATA_DIR, "datavault.db"),
        "queries": [
            ("Total sales by region",
             "SELECT sd.region, SUM(sd.total_amount) FROM sat_sale_details sd GROUP BY sd.region"),
            ("Top 10 customers by spend",
             "SELECT sc.name, SUM(sd.total_amount) AS total FROM lnk_sale ls JOIN sat_customer_details sc ON ls.hk_customer=sc.hk_customer JOIN sat_sale_details sd ON ls.hk_sale=sd.hk_sale GROUP BY sc.name ORDER BY total DESC LIMIT 10"),
            ("Monthly sales trend",
             "SELECT strftime('%Y', sale_date) AS yr, strftime('%m', sale_date) AS mo, SUM(total_amount) FROM sat_sale_details GROUP BY yr, mo"),
            ("Sales by product category",
             "SELECT sp.category, COUNT(*) AS cnt FROM lnk_sale ls JOIN sat_product_details sp ON ls.hk_product=sp.hk_product GROUP BY sp.category"),
            ("Avg quantity per region",
             "SELECT region, AVG(quantity) FROM sat_sale_details GROUP BY region"),
        ]
    },
    "Unified DW + AI": {
        "db": os.path.join(DATA_DIR, "unified.db"),
        "queries": [
            ("Total sales by region",
             "SELECT region, SUM(total_amount) FROM analytics_sales_wide GROUP BY region"),
            ("Top 10 customers by spend",
             "SELECT customer_name, SUM(total_amount) AS total FROM analytics_sales_wide GROUP BY customer_name ORDER BY total DESC LIMIT 10"),
            ("Monthly sales trend",
             "SELECT year, month, SUM(total_amount) FROM analytics_sales_wide GROUP BY year, month"),
            ("Sales by product category",
             "SELECT product_category, COUNT(*) AS sales_count, SUM(total_amount) FROM analytics_sales_wide GROUP BY product_category"),
            ("High-value sales by tier",
             "SELECT customer_tier, COUNT(*) AS hv_sales FROM analytics_sales_wide WHERE is_high_value=1 GROUP BY customer_tier"),
        ]
    }
}

RUNS = 5  # repeat each query N times and average


def benchmark():
    print("=" * 65)
    print("  Query Benchmark — all 4 DW Models")
    print("=" * 65)
    records = []

    for model_name, cfg in QUERIES.items():
        db = cfg["db"]
        if not os.path.exists(db):
            print(f"\n  ⚠ DB not found for {model_name}: {db}")
            continue
        conn = sqlite3.connect(db)
        print(f"\n  ▸ {model_name}")
        for q_name, sql in cfg["queries"]:
            timings = []
            for _ in range(RUNS):
                t0 = time.perf_counter()
                conn.execute(sql).fetchall()
                timings.append((time.perf_counter() - t0) * 1000)
            avg_ms = round(sum(timings) / len(timings), 3)
            min_ms = round(min(timings), 3)
            max_ms = round(max(timings), 3)
            print(f"    {q_name:<40} avg={avg_ms}ms  min={min_ms}ms  max={max_ms}ms")
            records.append({
                "model": model_name,
                "query": q_name,
                "avg_ms": avg_ms,
                "min_ms": min_ms,
                "max_ms": max_ms,
                "runs": RUNS
            })
        conn.close()

    df = pd.DataFrame(records)
    out = os.path.join(RESULTS_DIR, "benchmark_results.csv")
    df.to_csv(out, index=False)
    print(f"\n✅ Benchmark results saved → {out}")
    return df


if __name__ == "__main__":
    benchmark()
