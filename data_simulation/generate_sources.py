"""
Data Source Simulation — generates 50+ heterogeneous sources.
Output structure:
  data/raw/structured/       → 10 CSV tables
  data/raw/semi_structured/  → 20 JSON + 10 XML files
  data/raw/unstructured/     → 10 log + 5 text files
"""

import os
import csv
import json
import random
import string
from datetime import datetime, timedelta
from faker import Faker

fake = Faker()
random.seed(42)
Faker.seed(42)

BASE = os.path.join(os.path.dirname(__file__), "..", "data", "raw")
STRUCT = os.path.join(BASE, "structured")
SEMI = os.path.join(BASE, "semi_structured")
UNSTRUCT = os.path.join(BASE, "unstructured")

os.makedirs(STRUCT, exist_ok=True)
os.makedirs(SEMI, exist_ok=True)
os.makedirs(UNSTRUCT, exist_ok=True)

N = 500  # rows per structured table


# ─────────────────────────────────────────────────────────────────────────────
# STRUCTURED — 10 CSV tables
# ─────────────────────────────────────────────────────────────────────────────

def write_csv(name, headers, rows):
    path = os.path.join(STRUCT, f"{name}.csv")
    with open(path, "w", newline="", encoding="utf-8") as f:
        w = csv.DictWriter(f, fieldnames=headers)
        w.writeheader()
        w.writerows(rows)
    print(f"  [CSV] {name}.csv ({len(rows)} rows)")


def gen_customers():
    rows = [{"customer_id": i, "name": fake.name(), "email": fake.email(),
             "country": fake.country(), "signup_date": fake.date_between("-5y", "today").isoformat(),
             "tier": random.choice(["Bronze", "Silver", "Gold", "Platinum"])}
            for i in range(1, N + 1)]
    write_csv("customers", list(rows[0].keys()), rows)
    return rows


def gen_products():
    categories = ["Electronics", "Clothing", "Food", "Furniture", "Sports"]
    rows = [{"product_id": i, "name": fake.catch_phrase(), "category": random.choice(categories),
             "price": round(random.uniform(5, 2000), 2), "stock": random.randint(0, 1000)}
            for i in range(1, N + 1)]
    write_csv("products", list(rows[0].keys()), rows)
    return rows


def gen_sales(customer_ids, product_ids):
    rows = [{"sale_id": i, "customer_id": random.choice(customer_ids),
             "product_id": random.choice(product_ids),
             "quantity": random.randint(1, 20),
             "sale_date": fake.date_between("-3y", "today").isoformat(),
             "total_amount": round(random.uniform(10, 50000), 2),
             "region": fake.state()}
            for i in range(1, N * 2 + 1)]
    write_csv("sales", list(rows[0].keys()), rows)


def gen_employees():
    departments = ["Engineering", "Sales", "HR", "Finance", "Marketing", "Ops"]
    rows = [{"emp_id": i, "name": fake.name(), "department": random.choice(departments),
             "salary": round(random.uniform(30000, 150000), 2),
             "hire_date": fake.date_between("-10y", "today").isoformat(),
             "manager_id": random.randint(1, 50) if i > 50 else None}
            for i in range(1, N + 1)]
    write_csv("employees", list(rows[0].keys()), rows)


def gen_hr():
    rows = [{"record_id": i, "emp_id": random.randint(1, N),
             "leave_type": random.choice(["Annual", "Sick", "Maternity", "Unpaid"]),
             "days": random.randint(1, 30),
             "start_date": fake.date_between("-2y", "today").isoformat(),
             "status": random.choice(["Approved", "Pending", "Rejected"])}
            for i in range(1, N + 1)]
    write_csv("hr_leave", list(rows[0].keys()), rows)


def gen_finance():
    rows = [{"txn_id": i, "account_id": random.randint(1000, 9999),
             "type": random.choice(["Credit", "Debit"]),
             "amount": round(random.uniform(100, 100000), 2),
             "currency": random.choice(["USD", "EUR", "GBP", "PKR"]),
             "txn_date": fake.date_between("-2y", "today").isoformat(),
             "is_anomaly": random.choices([0, 1], weights=[95, 5])[0]}
            for i in range(1, N + 1)]
    write_csv("finance_transactions", list(rows[0].keys()), rows)


def gen_inventory():
    rows = [{"inv_id": i, "product_id": random.randint(1, N),
             "warehouse": fake.city(), "quantity": random.randint(0, 5000),
             "last_updated": fake.date_between("-1y", "today").isoformat()}
            for i in range(1, N + 1)]
    write_csv("inventory", list(rows[0].keys()), rows)


def gen_orders(customer_ids, product_ids):
    statuses = ["Pending", "Shipped", "Delivered", "Cancelled", "Returned"]
    rows = [{"order_id": i, "customer_id": random.choice(customer_ids),
             "product_id": random.choice(product_ids),
             "status": random.choice(statuses),
             "order_date": fake.date_between("-2y", "today").isoformat(),
             "delivery_date": fake.date_between("-1y", "today").isoformat()}
            for i in range(1, N + 1)]
    write_csv("orders", list(rows[0].keys()), rows)


def gen_suppliers():
    rows = [{"supplier_id": i, "name": fake.company(), "country": fake.country(),
             "contact_email": fake.email(), "rating": round(random.uniform(1, 5), 1),
             "contract_start": fake.date_between("-5y", "today").isoformat()}
            for i in range(1, 100 + 1)]
    write_csv("suppliers", list(rows[0].keys()), rows)


def gen_web_events():
    events = ["page_view", "click", "purchase", "signup", "logout", "search"]
    rows = [{"event_id": i, "user_id": random.randint(1, N),
             "event_type": random.choice(events),
             "page": "/" + fake.uri_path(),
             "timestamp": fake.date_time_between("-1y", "now").isoformat(),
             "session_id": fake.uuid4()}
            for i in range(1, N * 2 + 1)]
    write_csv("web_events", list(rows[0].keys()), rows)


# ─────────────────────────────────────────────────────────────────────────────
# SEMI-STRUCTURED — 20 JSON + 10 XML
# ─────────────────────────────────────────────────────────────────────────────

def write_json(name, data):
    path = os.path.join(SEMI, f"{name}.json")
    with open(path, "w", encoding="utf-8") as f:
        json.dump(data, f, indent=2, default=str)
    print(f"  [JSON] {name}.json")


def write_xml(name, content):
    path = os.path.join(SEMI, f"{name}.xml")
    with open(path, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"  [XML] {name}.xml")


def gen_json_sources():
    # 1-5: IoT sensor readings
    for sensor_id in range(1, 6):
        readings = [{"sensor_id": f"S{sensor_id:03d}", "timestamp": fake.date_time_between("-30d", "now").isoformat(),
                     "temperature": round(random.uniform(15, 95), 2),
                     "humidity": round(random.uniform(20, 90), 2),
                     "pressure": round(random.uniform(950, 1050), 2)}
                    for _ in range(200)]
        write_json(f"iot_sensor_{sensor_id:02d}", readings)

    # 6-10: API responses (user profiles)
    for batch in range(1, 6):
        users = [{"id": fake.uuid4(), "name": fake.name(), "email": fake.email(),
                  "created_at": fake.date_time_between("-2y", "now").isoformat(),
                  "preferences": {"newsletter": random.choice([True, False]),
                                  "theme": random.choice(["dark", "light"]),
                                  "language": random.choice(["en", "fr", "de", "ur"])}}
                 for _ in range(100)]
        write_json(f"api_user_profiles_{batch:02d}", users)

    # 11-15: Clickstream sessions
    for session_batch in range(1, 6):
        sessions = [{"session_id": fake.uuid4(), "user_id": random.randint(1, N),
                     "start": fake.date_time_between("-6m", "now").isoformat(),
                     "end": fake.date_time_between("-6m", "now").isoformat(),
                     "pages_visited": [{"url": "/" + fake.uri_path(), "dwell_ms": random.randint(500, 30000)}
                                       for _ in range(random.randint(2, 10))]}
                    for _ in range(50)]
        write_json(f"clickstream_{session_batch:02d}", sessions)

    # 16-20: Product catalog configs
    for catalog in range(1, 6):
        data = {"catalog_id": fake.uuid4(), "region": fake.country(),
                "currency": random.choice(["USD", "EUR", "GBP"]),
                "last_updated": datetime.now().isoformat(),
                "items": [{"sku": "SKU" + ''.join(random.choices(string.ascii_uppercase, k=6)),
                           "price": round(random.uniform(5, 5000), 2),
                           "in_stock": random.choice([True, False])}
                          for _ in range(50)]}
        write_json(f"product_catalog_{catalog:02d}", data)


def gen_xml_sources():
    for i in range(1, 11):
        employees_xml = '<?xml version="1.0" encoding="UTF-8"?>\n<employees>\n'
        for _ in range(50):
            employees_xml += f"""  <employee>
    <id>{random.randint(1000, 9999)}</id>
    <name>{fake.name()}</name>
    <department>{random.choice(["Engineering","Sales","HR","Finance"])}</department>
    <salary>{round(random.uniform(30000, 120000), 2)}</salary>
    <country>{fake.country()}</country>
  </employee>\n"""
        employees_xml += "</employees>"
        write_xml(f"hr_employees_{i:02d}", employees_xml)


# ─────────────────────────────────────────────────────────────────────────────
# UNSTRUCTURED — 10 logs + 5 text files
# ─────────────────────────────────────────────────────────────────────────────

def gen_unstructured():
    # Web server logs
    levels = ["INFO", "WARN", "ERROR", "DEBUG"]
    methods = ["GET", "POST", "PUT", "DELETE"]
    status_codes = [200, 200, 200, 301, 404, 500, 403]
    for log_num in range(1, 11):
        lines = []
        for _ in range(300):
            dt = fake.date_time_between("-30d", "now").strftime("%d/%b/%Y:%H:%M:%S +0000")
            ip = fake.ipv4()
            method = random.choice(methods)
            path = "/" + fake.uri_path()
            code = random.choice(status_codes)
            size = random.randint(200, 50000)
            level = random.choice(levels)
            lines.append(f'{ip} - - [{dt}] "{method} {path} HTTP/1.1" {code} {size} [{level}]')
        path = os.path.join(UNSTRUCT, f"access_log_{log_num:02d}.log")
        with open(path, "w", encoding="utf-8") as f:
            f.write("\n".join(lines))
        print(f"  [LOG] access_log_{log_num:02d}.log")

    # Free-text notes / reports
    titles = ["customer_feedback", "analyst_notes", "quarterly_summary", "incident_report", "data_audit"]
    for title in titles:
        content = f"# {title.replace('_', ' ').title()}\n\n"
        content += f"Generated: {datetime.now().isoformat()}\n\n"
        for _ in range(10):
            content += fake.paragraph(nb_sentences=6) + "\n\n"
        path = os.path.join(UNSTRUCT, f"{title}.txt")
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"  [TXT] {title}.txt")


# ─────────────────────────────────────────────────────────────────────────────
# MAIN
# ─────────────────────────────────────────────────────────────────────────────

if __name__ == "__main__":
    print("=" * 60)
    print("Generating 50+ heterogeneous data sources...")
    print("=" * 60)

    print("\n📂 STRUCTURED (10 CSV tables):")
    customers = gen_customers()
    c_ids = [c["customer_id"] for c in customers]
    products = gen_products()
    p_ids = [p["product_id"] for p in products]
    gen_sales(c_ids, p_ids)
    gen_employees()
    gen_hr()
    gen_finance()
    gen_inventory()
    gen_orders(c_ids, p_ids)
    gen_suppliers()
    gen_web_events()

    print("\n📂 SEMI-STRUCTURED (20 JSON + 10 XML):")
    gen_json_sources()
    gen_xml_sources()

    print("\n📂 UNSTRUCTURED (10 logs + 5 text files):")
    gen_unstructured()

    # Count files
    total = sum(len(files) for _, _, files in os.walk(BASE))
    print(f"\n✅ Done! {total} source files generated in data/raw/")
