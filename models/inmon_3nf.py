"""
Inmon 3NF (Corporate Information Factory) Model
Normalized enterprise-wide DW. All entities normalized to 3NF.
"""
import sqlite3
import pandas as pd
import os
import time

DB_PATH = os.path.join(os.path.dirname(__file__), "..", "data", "inmon.db")
STRUCT = os.path.join(os.path.dirname(__file__), "..", "data", "raw", "structured")


def get_connection():
    return sqlite3.connect(DB_PATH)


def create_schema(conn):
    conn.executescript("""
        DROP TABLE IF EXISTS sales;
        DROP TABLE IF EXISTS orders;
        DROP TABLE IF EXISTS customers;
        DROP TABLE IF EXISTS products;
        DROP TABLE IF EXISTS categories;
        DROP TABLE IF EXISTS employees;
        DROP TABLE IF EXISTS departments;
        DROP TABLE IF EXISTS hr_leave;
        DROP TABLE IF EXISTS finance_transactions;
        DROP TABLE IF EXISTS inventory;
        DROP TABLE IF EXISTS suppliers;

        CREATE TABLE categories (
            category_id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT UNIQUE
        );

        CREATE TABLE customers (
            customer_id   INTEGER PRIMARY KEY,
            name          TEXT NOT NULL,
            email         TEXT,
            country       TEXT,
            tier          TEXT,
            signup_date   TEXT
        );

        CREATE TABLE products (
            product_id    INTEGER PRIMARY KEY,
            name          TEXT,
            category_id   INTEGER,
            price         REAL,
            stock         INTEGER,
            FOREIGN KEY (category_id) REFERENCES categories(category_id)
        );

        CREATE TABLE departments (
            department_id   INTEGER PRIMARY KEY AUTOINCREMENT,
            name            TEXT UNIQUE
        );

        CREATE TABLE employees (
            emp_id        INTEGER PRIMARY KEY,
            name          TEXT,
            department_id INTEGER,
            salary        REAL,
            hire_date     TEXT,
            manager_id    INTEGER,
            FOREIGN KEY (department_id) REFERENCES departments(department_id)
        );

        CREATE TABLE hr_leave (
            record_id   INTEGER PRIMARY KEY,
            emp_id      INTEGER,
            leave_type  TEXT,
            days        INTEGER,
            start_date  TEXT,
            status      TEXT,
            FOREIGN KEY (emp_id) REFERENCES employees(emp_id)
        );

        CREATE TABLE finance_transactions (
            txn_id      INTEGER PRIMARY KEY,
            account_id  INTEGER,
            type        TEXT,
            amount      REAL,
            currency    TEXT,
            txn_date    TEXT,
            is_anomaly  INTEGER
        );

        CREATE TABLE sales (
            sale_id       INTEGER PRIMARY KEY,
            customer_id   INTEGER,
            product_id    INTEGER,
            quantity      INTEGER,
            sale_date     TEXT,
            total_amount  REAL,
            region        TEXT,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
            FOREIGN KEY (product_id)  REFERENCES products(product_id)
        );

        CREATE TABLE orders (
            order_id      INTEGER PRIMARY KEY,
            customer_id   INTEGER,
            product_id    INTEGER,
            status        TEXT,
            order_date    TEXT,
            delivery_date TEXT,
            FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
            FOREIGN KEY (product_id)  REFERENCES products(product_id)
        );

        CREATE TABLE inventory (
            inv_id        INTEGER PRIMARY KEY,
            product_id    INTEGER,
            warehouse     TEXT,
            quantity      INTEGER,
            last_updated  TEXT,
            FOREIGN KEY (product_id) REFERENCES products(product_id)
        );

        CREATE TABLE suppliers (
            supplier_id     INTEGER PRIMARY KEY,
            name            TEXT,
            country         TEXT,
            contact_email   TEXT,
            rating          REAL,
            contract_start  TEXT
        );
    """)
    conn.commit()


def load_all(conn):
    # Categories
    prods = pd.read_csv(os.path.join(STRUCT, "products.csv"))
    cat_df = pd.DataFrame({"name": prods["category"].unique()})
    cat_df.to_sql("categories", conn, if_exists="append", index=False)
    cat_map = pd.read_sql("SELECT category_id, name FROM categories", conn).set_index("name")["category_id"]

    # Customers
    pd.read_csv(os.path.join(STRUCT, "customers.csv")).to_sql("customers", conn, if_exists="append", index=False)

    # Products (with category FK)
    prods["category_id"] = prods["category"].map(cat_map)
    prods[["product_id", "name", "category_id", "price", "stock"]].to_sql("products", conn, if_exists="append", index=False)

    # Departments
    emps = pd.read_csv(os.path.join(STRUCT, "employees.csv"))
    dept_df = pd.DataFrame({"name": emps["department"].dropna().unique()})
    dept_df.to_sql("departments", conn, if_exists="append", index=False)
    dept_map = pd.read_sql("SELECT department_id, name FROM departments", conn).set_index("name")["department_id"]

    # Employees
    emps["department_id"] = emps["department"].map(dept_map)
    emps[["emp_id", "name", "department_id", "salary", "hire_date", "manager_id"]].to_sql("employees", conn, if_exists="append", index=False)

    # Other tables
    for table, file in [("hr_leave", "hr_leave.csv"), ("finance_transactions", "finance_transactions.csv"),
                         ("sales", "sales.csv"), ("orders", "orders.csv"),
                         ("inventory", "inventory.csv"), ("suppliers", "suppliers.csv")]:
        pd.read_csv(os.path.join(STRUCT, file)).to_sql(table, conn, if_exists="append", index=False)


def build(verbose=True):
    t0 = time.time()
    conn = get_connection()
    create_schema(conn)
    load_all(conn)
    conn.close()
    elapsed = round(time.time() - t0, 3)
    if verbose:
        print(f"  [Inmon 3NF] Built in {elapsed}s → {DB_PATH}")
    return elapsed


if __name__ == "__main__":
    build()
