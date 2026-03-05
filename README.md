# Unified Data Warehouse (UDW) Research Project

> **Research Methodology: Evaluating a Unified Data Warehouse Model**

A comprehensive research project that evaluates the effectiveness, scalability, and performance of a **Unified Data Warehouse (UDW)** model integrating Inmon, Kimball, Data Vault 2.0 features, and AI capabilities — focused on large organizations with 50+ heterogeneous data sources.

---

## 📁 Project Structure

```
udw-research/
├── data_simulation/       # Scripts to generate 50+ data sources
│   └── generate_sources.py
├── data/
│   └── raw/
│       ├── structured/    # CSV/SQL tables (sales, hr, finance, etc.)
│       ├── semi_structured/ # JSON + XML files
│       └── unstructured/  # Logs and text files
├── models/                # DW model implementations
│   ├── kimball_star_schema.py
│   ├── inmon_3nf.py
│   ├── data_vault_2.py
│   └── unified_dw.py
├── etl/                   # ETL pipeline runners
│   └── etl_runner.py
├── evaluation/            # Benchmarking and quality scripts
│   ├── benchmark_queries.py
│   ├── scalability_test.py
│   └── data_quality.py
├── ai/                    # AI models (anomaly detection + prediction)
│   ├── anomaly_detection.py
│   └── prediction_model.py
├── results/               # Output charts, reports, CSVs
│   └── generate_report.py
├── requirements.txt
└── README.md
```

---

## 🎯 Research Objective

Evaluate four DW architectures against 6 evaluation dimensions:

| Evaluation Dimension | Metric | Method |
|---|---|---|
| Data Integration | % sources successfully integrated | ETL testing |
| Historical Tracking | % historical changes preserved | Snapshot comparison |
| Query Performance | Avg query response time (ms) | Benchmark queries |
| Scalability | Effort & time to add new sources | Time log |
| Maintainability | ETL/schema update effort | Man-hours |
| AI Capabilities | Prediction accuracy, anomaly detection | Model evaluation |
| Data Quality | Completeness, consistency, accuracy | Profiling reports |

---

## 🏗️ DW Models Compared

| Model | Description |
|---|---|
| **Kimball Star Schema** | Fact + dimension tables, optimized for BI |
| **Inmon 3NF (CIF)** | Normalized enterprise-wide DW |
| **Data Vault 2.0** | Hub + Link + Satellite pattern |
| **Unified DW + AI** | Hybrid model with AI-ready analytics layer |

---

## 🚀 How to Run

### 1. Install dependencies
```bash
pip install -r requirements.txt
```

### 2. Generate simulated data (50+ sources)
```bash
python data_simulation/generate_sources.py
```

### 3. Run ETL pipelines (loads all models)
```bash
python etl/etl_runner.py
```

### 4. Run evaluation benchmarks
```bash
python evaluation/benchmark_queries.py
python evaluation/scalability_test.py
python evaluation/data_quality.py
```

### 5. Run AI modules
```bash
python ai/anomaly_detection.py
python ai/prediction_model.py
```

### 6. Generate final report
```bash
python results/generate_report.py
```

---

## 📊 Results

All results are saved to the `results/` directory:
- `results_summary.xlsx` — full metrics comparison table
- `research_report.pdf` — final research report with charts

---

## 🧑‍💻 Tech Stack
- **Python 3.12**
- pandas, numpy, SQLAlchemy, scikit-learn
- matplotlib, seaborn, fpdf2, openpyxl
- SQLite (in-memory DW backends)
- Faker (data simulation)
