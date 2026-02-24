# EDWH Hybrid Analytics PoC

> **Unified and Flexible Analytics Architecture** — A Proof of Concept combining **Data Vault 2.0**, **Lakehouse**, and **AI-Augmented anomaly detection**.

[![Laravel](https://img.shields.io/badge/Laravel-11-red?logo=laravel)](https://laravel.com)
[![MongoDB](https://img.shields.io/badge/MongoDB-7.0-green?logo=mongodb)](https://www.mongodb.com)
[![Python](https://img.shields.io/badge/Python-3.12-blue?logo=python)](https://python.org)
[![scikit-learn](https://img.shields.io/badge/scikit--learn-1.8-orange)](https://scikit-learn.org)

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    EDWH Hybrid Analytics                         │
├────────────────┬──────────────────────┬────────────────────────┤
│  Data Vault 2.0│    Raw Lakehouse      │    AI Anomaly Engine   │
│  (Structured)  │  (Unstructured)       │    (Python + sklearn)  │
├────────────────┼──────────────────────┼────────────────────────┤
│  Hub_Customers │  raw_data_lake        │  Isolation Forest      │
│  Hub_Products  │  - server_logs        │  anomaly_reports       │
│  Link_Txns     │  - customer_feedback  │  53 anomalies / 460 tx │
│  Sat_Customer  │                       │                        │
│  Sat_Product   │                       │                        │
└────────────────┴──────────────────────┴────────────────────────┘
         MongoDB 7.0 — Unified Data Store
         Laravel 11 — Orchestration + REST API
         Chart.js + Tailwind — BI Dashboard
```

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend / Orchestration | PHP 8.2, Laravel 11 |
| Database | MongoDB 7.0 |
| AI / Anomaly Detection | Python 3.12, scikit-learn (Isolation Forest) |
| Frontend Dashboard | Blade, Tailwind CSS CDN, Chart.js 4 |

---

## ✨ Features

### Data Vault 2.0
- **Hub** collections for Customers and Products (SHA-256 hash keys)
- **Link** collection for Transactions (business event tracking)
- **Satellite** collections with `hash_diff` for change detection (SCD Type 2)

### Lakehouse (Raw Data Lake)
- Unstructured JSON storage for server logs and customer feedback
- No transformation on ingest — stored as-is in MongoDB

### ETL Pipeline
```bash
php artisan etl:ingest-structured --count=200    # Data Vault ingestion
php artisan etl:ingest-unstructured --count=150  # Lakehouse ingestion
php artisan analytics:detect-anomalies           # AI anomaly detection
```

### REST API (4 endpoints)
| Endpoint | Description |
|---|---|
| `GET /api/analytics/summary` | KPI counts across all collections |
| `GET /api/analytics/transactions` | Daily revenue + transaction time-series |
| `GET /api/analytics/anomalies` | AI anomaly reports (`?severity=high\|medium\|low`) |
| `GET /api/analytics/data-mart/sales` | Star-schema sales mart |

### Premium BI Dashboard
- Dark-themed glassmorphism UI
- 5 KPI cards, revenue line chart, donut chart, category bars
- AI anomaly severity table with real-time filtering

---

## 🚀 Installation

```bash
# 1. Clone the repo
git clone https://github.com/YOUR_USERNAME/edwh-hybrid-analytics.git
cd edwh-hybrid-analytics

# 2. Install PHP dependencies
composer install

# 3. Install Python dependencies
pip install pymongo scikit-learn numpy

# 4. Setup environment
cp .env.example .env
php artisan key:generate
```

Edit `.env`:
```env
DB_CONNECTION=mongodb
DB_HOST=127.0.0.1
DB_PORT=27017
DB_DATABASE=edwh_analytics
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

## ▶️ Running

```bash
# 1. Start MongoDB
mongod --dbpath /data/db --port 27017

# 2. Seed RBAC users
php artisan db:seed --class=UserSeeder

# 3. Run ETL + AI detection
php artisan etl:ingest-structured --count=200
php artisan etl:ingest-unstructured --count=150
php artisan analytics:detect-anomalies

# 4. Start the server
php artisan serve
```

Open **http://127.0.0.1:8000/dashboard**

---

## 🔐 RBAC Test Users

| Role | Email | Password |
|---|---|---|
| Admin | admin@edwh.local | Admin@1234 |
| Viewer | viewer@edwh.local | Viewer@1234 |

---

## 📁 Project Structure

```
app/
├── Console/Commands/
│   ├── IngestStructuredData.php     # ETL: Data Vault 2.0
│   ├── IngestUnstructuredData.php   # ETL: Lakehouse
│   └── DetectAnomalies.php          # Artisan → Python
├── Http/
│   ├── Controllers/Api/AnalyticsController.php
│   └── Middleware/RoleMiddleware.php
├── Models/Vault/                    # 7 MongoDB Eloquent models
└── Services/AnomalyDetectionService.php
scripts/
├── anomaly_detector.py              # Isolation Forest engine
└── seed_raw_lake.py                 # Direct MongoDB seeder
resources/views/
└── dashboard.blade.php              # Full BI Dashboard
```

## 📄 License

MIT
