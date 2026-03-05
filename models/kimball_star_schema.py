"""
Kimball Star Schema Model
Creates Fact + Dimension tables in SQLite.
"""
import sqlite3
import pandas as pd
import os
import time

DB_PATH = os.path.join(os.path.dirname(__file__), "..", "data", "kimball.db")
STRUCT = os.path.join(os.path.dirname(__file__), "..", "data", "raw", "structured")


def get_connection():
    return sqlite3.connect(DB_PATH)


def create_schema(conn):
    cursor = conn.cursor()
    cursor.executescript("""
        DROP TABLE IF EXISTS fact_sales;
        DROP TABLE IF EXISTS dim_customer;
        DROP TABLE IF EXISTS dim_product;
        DROP TABLE IF EXISTS dim_date;

        CREATE TABLE dim_customer (
            customer_sk   INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_id   INTEGER,
            name          TEXT,
            email         TEXT,
            country       TEXT,
            tier          TEXT,
            signup_date   TEXT
        );

        CREATE TABLE dim_product (
            product_sk  INTEGER PRIMARY KEY AUTOINCREMENT,
            product_id  INTEGER,
            name        TEXT,
            category    TEXT,
            price       REAL
        );

        CREATE TABLE dim_date (
            date_sk   INTEGER PRIMARY KEY,
            full_date TEXT,
            year      INTEGER,
            quarter   INTEGER,
            month     INTEGER,
            day       INTEGER,
            weekday   TEXT
        );

        CREATE TABLE fact_sales (
            sale_id       INTEGER PRIMARY KEY,
            customer_sk   INTEGER,
            product_sk    INTEGER,
            date_sk       INTEGER,
            quantity      INTEGER,
            total_amount  REAL,
            region        TEXT,
            FOREIGN KEY (customer_sk) REFERENCES dim_customer(customer_sk),
            FOREIGN KEY (product_sk)  REFERENCES dim_product(product_sk),
            FOREIGN KEY (date_sk)     REFERENCES dim_date(date_sk)
        );
    """)
    conn.commit()


def load_dim_customer(conn):
    df = pd.read_csv(os.path.join(STRUCT, "customers.csv"))
    df.to_sql("dim_customer", conn, if_exists="append", index=False)
    return df.set_index("customer_id")["rowid"] if "rowid" in df.columns else df


def load_dim_product(conn):
    df = pd.read_csv(os.path.join(STRUCT, "products.csv"))
    df[["product_id", "name", "category", "price"]].to_sql("dim_product", conn, if_exists="append", index=False)


def load_dim_date(conn):
    dates_df = pd.read_csv(os.path.join(STRUCT, "sales.csv"), usecols=["sale_date"])
    dates_df["sale_date"] = pd.to_datetime(dates_df["sale_date"])
    unique_dates = dates_df["sale_date"].drop_duplicates().sort_values()
    rows = []
    for d in unique_dates:
        rows.append({
            "date_sk": int(d.strftime("%Y%m%d")),
            "full_date": d.strftime("%Y-%m-%d"),
            "year": d.year, "quarter": d.quarter,
            "month": d.month, "day": d.day,
            "weekday": d.strftime("%A")
        })
    pd.DataFrame(rows).to_sql("dim_date", conn, if_exists="append", index=False)


def load_fact_sales(conn):
    sales = pd.read_csv(os.path.join(STRUCT, "sales.csv"))
    cust_map = pd.read_sql("SELECT customer_sk, customer_id FROM dim_customer", conn).set_index("customer_id")["customer_sk"]
    prod_map = pd.read_sql("SELECT product_sk, product_id FROM dim_product", conn).set_index("product_id")["product_sk"]
    sales["sale_date"] = pd.to_datetime(sales["sale_date"])
    sales["date_sk"] = sales["sale_date"].dt.strftime("%Y%m%d").astype(int)
    sales["customer_sk"] = sales["customer_id"].map(cust_map)
    sales["product_sk"]  = sales["product_id"].map(prod_map)
    sales[["sale_id", "customer_sk", "product_sk", "date_sk", "quantity", "total_amount", "region"]].to_sql(
        "fact_sales", conn, if_exists="append", index=False)


def build(verbose=True):
    t0 = time.time()
    conn = get_connection()
    create_schema(conn)
    load_dim_customer(conn)
    load_dim_product(conn)
    load_dim_date(conn)
    load_fact_sales(conn)
    conn.close()
    elapsed = round(time.time() - t0, 3)
    if verbose:
        print(f"  [Kimball Star Schema] Built in {elapsed}s → {DB_PATH}")
    return elapsed


if __name__ == "__main__":
    build()
