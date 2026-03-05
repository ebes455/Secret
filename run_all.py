"""
run_all.py — Master runner: executes all project phases in sequence.
Usage: python run_all.py
"""
import subprocess
import sys
import os

PYTHON = sys.executable
BASE = os.path.dirname(__file__)


def run(script_path, label):
    print(f"\n{'='*65}")
    print(f"  ▸ {label}")
    print(f"{'='*65}")
    result = subprocess.run([PYTHON, script_path], capture_output=False, text=True)
    if result.returncode != 0:
        print(f"  ✗ FAILED with exit code {result.returncode}")
    else:
        print(f"  ✔ Done")
    return result.returncode == 0


def main():
    steps = [
        (os.path.join(BASE, "data_simulation", "generate_sources.py"), "Phase 2: Generate 50+ Data Sources"),
        (os.path.join(BASE, "etl", "etl_runner.py"),                   "Phase 3+4: Build DW Models & ETL"),
        (os.path.join(BASE, "evaluation", "benchmark_queries.py"),     "Phase 5a: Query Benchmarks"),
        (os.path.join(BASE, "evaluation", "scalability_test.py"),      "Phase 5b: Scalability Test"),
        (os.path.join(BASE, "evaluation", "data_quality.py"),          "Phase 5c: Data Quality Assessment"),
        (os.path.join(BASE, "ai", "anomaly_detection.py"),             "Phase 6a: Anomaly Detection"),
        (os.path.join(BASE, "ai", "prediction_model.py"),              "Phase 6b: Prediction Model"),
        (os.path.join(BASE, "results", "generate_report.py"),          "Phase 7: Generate Report"),
    ]

    success = 0
    for script, label in steps:
        ok = run(script, label)
        if ok:
            success += 1

    print(f"\n{'='*65}")
    print(f"  ✅ Completed {success}/{len(steps)} phases successfully.")
    print(f"  📁 Outputs are in: results/")
    print(f"{'='*65}")


if __name__ == "__main__":
    main()
