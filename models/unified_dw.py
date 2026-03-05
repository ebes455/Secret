"""
Unified DW + AI Model
Hybrid: integrates Kimball dimensional layer, Inmon 3NF core, and a flat 
AI-ready analytics layer. Also exposes a wide denormalized fact table 
optimized for ML feature extraction.
"""
import sqlite3
import pandas as pd
import os
import time

DB_PATH = os.path.join(os.path.dirname(__file__), "..", "data", "unified.db")
STRUCT = os.path.join(os.path.dirname(__file__), "..", "data", "raw", "structured")


def get_connection():
    return sqlite3.connect(DB_PATH)


def create_schema(conn):
    conn.executescript("""
        -- Core normalized tables (Inmon layer)
        DROP TABLE IF EXISTS core_customers;
        DROP TABLE IF EXISTS core_products;
        DROP TABLE IF EXISTS core_employees;
        DROP TABLE IF EXISTS core_sales;
        DROP TABLE IF EXISTS core_finance;
        DROP TABLE IF EXISTS core_inventory;

        CREATE TABLE core_customers (
            customer_id INTEGER PRIMARY KEY,
            name TEXT, email TEXT, country TEXT, tier TEXT, signup_date TEXT
        );
        CREATE TABLE core_products (
            product_id INTEGER PRIMARY KEY,
            name TEXT, category TEXT, price REAL, stock INTEGER
        );
        CREATE TABLE core_employees (
            emp_id INTEGER PRIMARY KEY,
            name TEXT, department TEXT, salary REAL, hire_date TEXT, manager_id INTEGER
        );
        CREATE TABLE core_sales (
            sale_id INTEGER PRIMARY KEY,
            customer_id INTEGER, product_id INTEGER,
            quantity INTEGER, sale_date TEXT, total_amount REAL, region TEXT
        );
        CREATE TABLE core_finance (
            txn_id INTEGER PRIMARY KEY,
            account_id INTEGER, type TEXT, amount REAL,
            currency TEXT, txn_date TEXT, is_anomaly INTEGER
        );
        CREATE TABLE core_inventory (
            inv_id INTEGER PRIMARY KEY,
            product_id INTEGER, warehouse TEXT,
            quantity INTEGER, last_updated TEXT
        );

        -- AI-ready analytics layer (wide denormalized table)
        DROP TABLE IF EXISTS analytics_sales_wide;
        CREATE TABLE analytics_sales_wide (
            sale_id         INTEGER PRIMARY KEY,
            sale_date       TEXT,
            year            INTEGER,
            month           INTEGER,
            quarter         INTEGER,
            region          TEXT,
            quantity        INTEGER,
            total_amount    REAL,
            customer_id     INTEGER,
            customer_name   TEXT,
            customer_country TEXT,
            customer_tier   TEXT,
            product_id      INTEGER,
            product_name    TEXT,
            product_category TEXT,
            unit_price      REAL,
            discount_rate   REAL,
            is_high_value   INTEGER
        );

        -- Historical tracking (SCD Type 2 style)
        DROP TABLE IF EXISTS hist_customer_scd2;
        CREATE TABLE hist_customer_scd2 (
            surrogate_key   INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id     INTEGER,
            name            TEXT,
            tier            TEXT,
            country         TEXT,
            effective_date  TEXT,
            expiry_date     TEXT,
            is_current      INTEGER DEFAULT 1
        );
    """)
    conn.commit()


def load_core_tables(conn):
    for table, file in [
        ("core_customers", "customers.csv"),
        ("core_products", "products.csv"),
        ("core_employees", "employees.csv"),
        ("core_sales", "sales.csv"),
        ("core_finance", "finance_transactions.csv"),
        ("core_inventory", "inventory.csv"),
    ]:
        df = pd.read_csv(os.path.join(STRUCT, file))
        # Keep only columns that exist in the schema
        col_map = {
            "core_customers": ["customer_id","name","email","country","tier","signup_date"],
            "core_products": ["product_id","name","category","price","stock"],
            "core_employees": ["emp_id","name","department","salary","hire_date","manager_id"],
            "core_sales": ["sale_id","customer_id","product_id","quantity","sale_date","total_amount","region"],
            "core_finance": ["txn_id","account_id","type","amount","currency","txn_date","is_anomaly"],
            "core_inventory": ["inv_id","product_id","warehouse","quantity","last_updated"],
        }
        df[col_map[table]].to_sql(table, conn, if_exists="append", index=False)


def build_analytics_layer(conn):
    """Build the wide denormalized AI-ready table by joining core tables."""
    sales = pd.read_sql("SELECT * FROM core_sales", conn)
    custs = pd.read_sql("SELECT customer_id, name AS customer_name, country AS customer_country, tier AS customer_tier FROM core_customers", conn)
    prods = pd.read_sql("SELECT product_id, name AS product_name, category AS product_category, price AS unit_price FROM core_products", conn)

    wide = sales.merge(custs, on="customer_id", how="left").merge(prods, on="product_id", how="left")
    wide["sale_date"] = pd.to_datetime(wide["sale_date"])
    wide["year"]    = wide["sale_date"].dt.year
    wide["month"]   = wide["sale_date"].dt.month
    wide["quarter"] = wide["sale_date"].dt.quarter
    wide["sale_date"] = wide["sale_date"].dt.strftime("%Y-%m-%d")
    wide["discount_rate"] = (1 - wide["total_amount"] / (wide["quantity"] * wide["unit_price"].clip(lower=0.01))).clip(0, 1).round(4)
    wide["is_high_value"] = (wide["total_amount"] > wide["total_amount"].quantile(0.9)).astype(int)

    cols = ["sale_id","sale_date","year","month","quarter","region","quantity","total_amount",
            "customer_id","customer_name","customer_country","customer_tier",
            "product_id","product_name","product_category","unit_price","discount_rate","is_high_value"]
    wide[cols].to_sql("analytics_sales_wide", conn, if_exists="append", index=False)


def build_scd2_history(conn):
    """Simulate SCD2 by loading two 'snapshots' of customer data."""
    custs = pd.read_sql("SELECT customer_id, name, tier, country FROM core_customers", conn)
    # Version 1
    v1 = custs.copy()
    v1["effective_date"] = "2023-01-01"
    v1["expiry_date"]    = "2024-06-30"
    v1["is_current"]     = 0
    # Version 2 (tier upgrade simulation)
    v2 = custs.copy()
    tier_upgrade = {"Bronze": "Silver", "Silver": "Gold", "Gold": "Platinum", "Platinum": "Platinum"}
    v2["tier"] = v2["tier"].map(tier_upgrade)
    v2["effective_date"] = "2024-07-01"
    v2["expiry_date"]    = "9999-12-31"
    v2["is_current"]     = 1
    pd.concat([v1, v2])[["customer_id","name","tier","country","effective_date","expiry_date","is_current"]].to_sql(
        "hist_customer_scd2", conn, if_exists="append", index=False)


def build(verbose=True):
    t0 = time.time()
    conn = get_connection()
    create_schema(conn)
    load_core_tables(conn)
    build_analytics_layer(conn)
    build_scd2_history(conn)
    conn.close()
    elapsed = round(time.time() - t0, 3)
    if verbose:
        print(f"  [Unified DW + AI] Built in {elapsed}s → {DB_PATH}")
    return elapsed


if __name__ == "__main__":
    build()
