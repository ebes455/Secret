"""
Data Vault 2.0 Model
Hub, Link, and Satellite tables using SQLite.
Pattern: Raw Vault → Business Vault
"""
import sqlite3
import pandas as pd
import hashlib
import os
import time
from datetime import datetime

DB_PATH = os.path.join(os.path.dirname(__file__), "..", "data", "datavault.db")
STRUCT = os.path.join(os.path.dirname(__file__), "..", "data", "raw", "structured")

LOAD_DTS = datetime.now().isoformat()
RECORD_SOURCE = "UDW_RESEARCH_SIM"


def get_connection():
    return sqlite3.connect(DB_PATH)


def hash_key(*args):
    val = "|".join(str(a) for a in args)
    return hashlib.md5(val.encode()).hexdigest()


def create_schema(conn):
    conn.executescript("""
        -- HUBS
        DROP TABLE IF EXISTS hub_customer;
        DROP TABLE IF EXISTS hub_product;
        DROP TABLE IF EXISTS hub_employee;
        DROP TABLE IF EXISTS hub_transaction;

        CREATE TABLE hub_customer (
            hk_customer     TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            customer_id     INTEGER
        );

        CREATE TABLE hub_product (
            hk_product      TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            product_id      INTEGER
        );

        CREATE TABLE hub_employee (
            hk_employee     TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            emp_id          INTEGER
        );

        CREATE TABLE hub_transaction (
            hk_transaction  TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            txn_id          INTEGER
        );

        -- LINKS
        DROP TABLE IF EXISTS lnk_sale;
        DROP TABLE IF EXISTS lnk_order;

        CREATE TABLE lnk_sale (
            hk_sale         TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            hk_customer     TEXT,
            hk_product      TEXT,
            sale_id         INTEGER
        );

        CREATE TABLE lnk_order (
            hk_order        TEXT PRIMARY KEY,
            load_dts        TEXT,
            record_source   TEXT,
            hk_customer     TEXT,
            hk_product      TEXT,
            order_id        INTEGER
        );

        -- SATELLITES
        DROP TABLE IF EXISTS sat_customer_details;
        DROP TABLE IF EXISTS sat_product_details;
        DROP TABLE IF EXISTS sat_employee_details;
        DROP TABLE IF EXISTS sat_sale_details;
        DROP TABLE IF EXISTS sat_finance_details;

        CREATE TABLE sat_customer_details (
            hk_customer     TEXT,
            load_dts        TEXT,
            record_source   TEXT,
            name            TEXT,
            email           TEXT,
            country         TEXT,
            tier            TEXT,
            signup_date     TEXT,
            PRIMARY KEY (hk_customer, load_dts)
        );

        CREATE TABLE sat_product_details (
            hk_product      TEXT,
            load_dts        TEXT,
            record_source   TEXT,
            name            TEXT,
            category        TEXT,
            price           REAL,
            stock           INTEGER,
            PRIMARY KEY (hk_product, load_dts)
        );

        CREATE TABLE sat_employee_details (
            hk_employee     TEXT,
            load_dts        TEXT,
            record_source   TEXT,
            name            TEXT,
            department      TEXT,
            salary          REAL,
            hire_date       TEXT,
            PRIMARY KEY (hk_employee, load_dts)
        );

        CREATE TABLE sat_sale_details (
            hk_sale         TEXT,
            load_dts        TEXT,
            record_source   TEXT,
            quantity        INTEGER,
            total_amount    REAL,
            region          TEXT,
            sale_date       TEXT,
            PRIMARY KEY (hk_sale, load_dts)
        );

        CREATE TABLE sat_finance_details (
            hk_transaction  TEXT,
            load_dts        TEXT,
            record_source   TEXT,
            account_id      INTEGER,
            type            TEXT,
            amount          REAL,
            currency        TEXT,
            txn_date        TEXT,
            is_anomaly      INTEGER,
            PRIMARY KEY (hk_transaction, load_dts)
        );
    """)
    conn.commit()


def load_all(conn):
    # Customers → Hub + Satellite
    cust = pd.read_csv(os.path.join(STRUCT, "customers.csv"))
    cust["hk_customer"] = cust["customer_id"].apply(lambda x: hash_key("CUST", x))
    cust["load_dts"] = LOAD_DTS
    cust["record_source"] = RECORD_SOURCE
    cust[["hk_customer", "load_dts", "record_source", "customer_id"]].to_sql("hub_customer", conn, if_exists="append", index=False)
    cust[["hk_customer", "load_dts", "record_source", "name", "email", "country", "tier", "signup_date"]].to_sql(
        "sat_customer_details", conn, if_exists="append", index=False)

    # Products
    prods = pd.read_csv(os.path.join(STRUCT, "products.csv"))
    prods["hk_product"] = prods["product_id"].apply(lambda x: hash_key("PROD", x))
    prods["load_dts"] = LOAD_DTS
    prods["record_source"] = RECORD_SOURCE
    prods[["hk_product", "load_dts", "record_source", "product_id"]].to_sql("hub_product", conn, if_exists="append", index=False)
    prods[["hk_product", "load_dts", "record_source", "name", "category", "price", "stock"]].to_sql(
        "sat_product_details", conn, if_exists="append", index=False)

    # Employees
    emps = pd.read_csv(os.path.join(STRUCT, "employees.csv"))
    emps["hk_employee"] = emps["emp_id"].apply(lambda x: hash_key("EMP", x))
    emps["load_dts"] = LOAD_DTS
    emps["record_source"] = RECORD_SOURCE
    emps[["hk_employee", "load_dts", "record_source", "emp_id"]].to_sql("hub_employee", conn, if_exists="append", index=False)
    emps[["hk_employee", "load_dts", "record_source", "name", "department", "salary", "hire_date"]].to_sql(
        "sat_employee_details", conn, if_exists="append", index=False)

    # Sales → Link + Satellite
    sales = pd.read_csv(os.path.join(STRUCT, "sales.csv"))
    cust_hk = cust.set_index("customer_id")["hk_customer"]
    prod_hk = prods.set_index("product_id")["hk_product"]
    sales["hk_customer"] = sales["customer_id"].map(cust_hk)
    sales["hk_product"]  = sales["product_id"].map(prod_hk)
    sales["hk_sale"]     = sales["sale_id"].apply(lambda x: hash_key("SALE", x))
    sales["load_dts"]    = LOAD_DTS
    sales["record_source"] = RECORD_SOURCE
    sales[["hk_sale", "load_dts", "record_source", "hk_customer", "hk_product", "sale_id"]].to_sql(
        "lnk_sale", conn, if_exists="append", index=False)
    sales[["hk_sale", "load_dts", "record_source", "quantity", "total_amount", "region", "sale_date"]].to_sql(
        "sat_sale_details", conn, if_exists="append", index=False)

    # Finance transactions
    fin = pd.read_csv(os.path.join(STRUCT, "finance_transactions.csv"))
    fin["hk_transaction"] = fin["txn_id"].apply(lambda x: hash_key("TXN", x))
    fin["load_dts"] = LOAD_DTS
    fin["record_source"] = RECORD_SOURCE
    fin[["hk_transaction", "load_dts", "record_source", "txn_id"]].to_sql("hub_transaction", conn, if_exists="append", index=False)
    fin[["hk_transaction", "load_dts", "record_source", "account_id", "type", "amount", "currency", "txn_date", "is_anomaly"]].to_sql(
        "sat_finance_details", conn, if_exists="append", index=False)


def build(verbose=True):
    t0 = time.time()
    conn = get_connection()
    create_schema(conn)
    load_all(conn)
    conn.close()
    elapsed = round(time.time() - t0, 3)
    if verbose:
        print(f"  [Data Vault 2.0] Built in {elapsed}s → {DB_PATH}")
    return elapsed


if __name__ == "__main__":
    build()
