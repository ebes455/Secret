"""
Data Quality Assessment — profiles completeness, consistency, and accuracy
across all 4 DW models.
"""
import sqlite3
import os
import pandas as pd

DATA_DIR = os.path.join(os.path.dirname(__file__), "..", "data")
RESULTS_DIR = os.path.join(os.path.dirname(__file__), "..", "results")
os.makedirs(RESULTS_DIR, exist_ok=True)


def profile_table(conn, table, key_cols=None):
    try:
        df = pd.read_sql(f"SELECT * FROM {table}", conn)
    except Exception:
        return None

    total_rows = len(df)
    if total_rows == 0:
        return None

    # Completeness: % non-null values across all columns
    completeness = round(df.notna().values.mean() * 100, 2)

    # Consistency: % unique rows (no exact duplicates)
    unique_rows = df.drop_duplicates().shape[0]
    consistency = round(unique_rows / total_rows * 100, 2)

    # Accuracy: % numeric values in expected range (non-negative for amounts/quantities)
    num_cols = df.select_dtypes(include="number").columns.tolist()
    if num_cols:
        neg_count = (df[num_cols] < 0).values.sum()
        total_nums = df[num_cols].size
        accuracy = round((1 - neg_count / total_nums) * 100, 2)
    else:
        accuracy = 100.0

    return {
        "table": table,
        "total_rows": total_rows,
        "completeness_pct": completeness,
        "consistency_pct": consistency,
        "accuracy_pct": accuracy,
        "dq_score": round((completeness + consistency + accuracy) / 3, 2)
    }


MODELS = {
    "Kimball Star Schema": {
        "db": os.path.join(DATA_DIR, "kimball.db"),
        "tables": ["fact_sales", "dim_customer", "dim_product", "dim_date"]
    },
    "Inmon 3NF": {
        "db": os.path.join(DATA_DIR, "inmon.db"),
        "tables": ["sales", "customers", "products", "employees", "finance_transactions"]
    },
    "Data Vault 2.0": {
        "db": os.path.join(DATA_DIR, "datavault.db"),
        "tables": ["hub_customer", "hub_product", "lnk_sale", "sat_sale_details", "sat_finance_details"]
    },
    "Unified DW + AI": {
        "db": os.path.join(DATA_DIR, "unified.db"),
        "tables": ["analytics_sales_wide", "core_customers", "core_finance", "hist_customer_scd2"]
    }
}


def run():
    print("=" * 65)
    print("  Data Quality Assessment")
    print("=" * 65)
    all_records = []

    for model_name, cfg in MODELS.items():
        db = cfg["db"]
        if not os.path.exists(db):
            print(f"\n  ⚠ DB not found: {db}")
            continue
        conn = sqlite3.connect(db)
        print(f"\n  ▸ {model_name}")
        model_records = []
        for table in cfg["tables"]:
            result = profile_table(conn, table)
            if result:
                result["model"] = model_name
                model_records.append(result)
                print(f"    {table:<35} complete={result['completeness_pct']}%  "
                      f"consist={result['consistency_pct']}%  "
                      f"accuracy={result['accuracy_pct']}%  "
                      f"DQ={result['dq_score']}")
        conn.close()
        all_records.extend(model_records)

    df = pd.DataFrame(all_records)
    out = os.path.join(RESULTS_DIR, "data_quality_results.csv")
    df.to_csv(out, index=False)
    print(f"\n✅ Data quality results saved → {out}")

    # Summary by model
    summary = df.groupby("model")[["completeness_pct", "consistency_pct", "accuracy_pct", "dq_score"]].mean().round(2)
    print("\n  📊 Model-level DQ Summary:")
    print(summary.to_string())
    return df


if __name__ == "__main__":
    run()
