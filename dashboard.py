"""
UDW Research Dashboard — Streamlit web app.
Run with: streamlit run dashboard.py
"""
import os
import json
import streamlit as st
import pandas as pd
import matplotlib
matplotlib.use("Agg")
import matplotlib.pyplot as plt
import seaborn as sns

# ─────────────────────────────────────────────────────────────────────────────
BASE = os.path.dirname(__file__)
RESULTS = os.path.join(BASE, "results")
CHARTS = {
    "benchmark":  os.path.join(RESULTS, "chart_benchmark.png"),
    "dq":         os.path.join(RESULTS, "chart_dq_heatmap.png"),
    "scalability": os.path.join(RESULTS, "chart_scalability.png"),
    "ai":         os.path.join(RESULTS, "chart_ai_summary.png"),
}

st.set_page_config(
    page_title="UDW Research Dashboard",
    page_icon="🏗️",
    layout="wide",
    initial_sidebar_state="expanded"
)

# ─────────────────────────────────────────────────────────────────────────────
# Sidebar
# ─────────────────────────────────────────────────────────────────────────────
st.sidebar.image("https://img.icons8.com/fluency/96/database.png", width=64)
st.sidebar.title("UDW Research")
st.sidebar.markdown("**Evaluating a Unified Data Warehouse Model**")
st.sidebar.markdown("---")
page = st.sidebar.radio("Navigate", [
    "🏠 Overview",
    "📊 Query Benchmark",
    "⚖️ Scalability",
    "✅ Data Quality",
    "🤖 AI Capabilities",
    "📋 Raw Data",
])
st.sidebar.markdown("---")
st.sidebar.markdown("**Models Compared**")
for m in ["Kimball Star Schema", "Inmon 3NF", "Data Vault 2.0", "Unified DW + AI"]:
    st.sidebar.markdown(f"- {m}")


# ─────────────────────────────────────────────────────────────────────────────
# Data loaders
# ─────────────────────────────────────────────────────────────────────────────
@st.cache_data
def load_csv(name):
    path = os.path.join(RESULTS, name)
    return pd.read_csv(path) if os.path.exists(path) else pd.DataFrame()

@st.cache_data
def load_json(name):
    path = os.path.join(RESULTS, name)
    if os.path.exists(path):
        with open(path) as f:
            return json.load(f)
    return {}

etl        = load_csv("etl_results.csv")
benchmark  = load_csv("benchmark_results.csv")
scalability = load_csv("scalability_results.csv")
dq         = load_csv("data_quality_results.csv")
anomaly    = load_json("anomaly_metrics.json")
prediction = load_json("prediction_metrics.json")


# ─────────────────────────────────────────────────────────────────────────────
# OVERVIEW PAGE
# ─────────────────────────────────────────────────────────────────────────────
if page == "🏠 Overview":
    st.title("🏗️ Unified Data Warehouse — Research Evaluation Dashboard")
    st.markdown("""
    > Evaluating **four DW architectures** against 6 evaluation dimensions using **50+ heterogeneous data sources**.
    """)

    col1, col2, col3, col4 = st.columns(4)
    with col1:
        st.metric("Data Sources", "55+", help="CSV, JSON, XML, logs, text files")
    with col2:
        if not etl.empty and "integration_success_pct" in etl.columns:
            st.metric("Integration Success", f"{etl['integration_success_pct'].iloc[0]}%")
        else:
            st.metric("Integration Success", "—")
    with col3:
        if not etl.empty and "historical_tracking_pct" in etl.columns:
            st.metric("Historical Tracking", f"{etl['historical_tracking_pct'].iloc[0]}%")
        else:
            st.metric("Historical Tracking", "—")
    with col4:
        if anomaly:
            st.metric("Anomaly F1 Score", anomaly.get("f1_anomaly", "—"))
        else:
            st.metric("Anomaly F1 Score", "—")

    st.markdown("---")

    # ETL Results
    st.subheader("ETL Pipeline Results")
    if not etl.empty:
        display_cols = [c for c in ["model", "status", "build_time_s"] if c in etl.columns]
        styled = etl[display_cols].copy()
        st.dataframe(styled, use_container_width=True)
    else:
        st.info("No ETL results yet. Run `python run_all.py` first.")

    # Quick model overview
    st.subheader("Models Evaluated")
    c1, c2, c3, c4 = st.columns(4)
    cards = [
        ("⭐ Kimball Star Schema", "Fact + Dimension tables. Optimized for BI & OLAP. Fast query response."),
        ("🏢 Inmon 3NF", "Normalized CIF. Reduces redundancy. Better for data integrity."),
        ("🔗 Data Vault 2.0", "Hub + Link + Satellite. Excellent auditability & historical tracking."),
        ("🚀 Unified DW + AI", "Hybrid model + SCD2 + AI-ready analytics layer. Best overall."),
    ]
    for col, (title, desc) in zip([c1, c2, c3, c4], cards):
        with col:
            st.info(f"**{title}**\n\n{desc}")


# ─────────────────────────────────────────────────────────────────────────────
# QUERY BENCHMARK PAGE
# ─────────────────────────────────────────────────────────────────────────────
elif page == "📊 Query Benchmark":
    st.title("📊 Query Performance Benchmark")
    st.markdown("Each model was tested with **5 standardized queries**, each run **5 times** and averaged.")

    if os.path.exists(CHARTS["benchmark"]):
        st.image(CHARTS["benchmark"], use_container_width=True)
    else:
        st.warning("Chart not found. Run generate_report.py first.")

    if not benchmark.empty:
        st.subheader("Detailed Results")
        st.dataframe(benchmark, use_container_width=True)

        st.subheader("Summary — Avg Response Time")
        summary = benchmark.groupby("model")["avg_ms"].mean().reset_index()
        summary.columns = ["Model", "Avg Response Time (ms)"]
        best = summary.loc[summary["Avg Response Time (ms)"].idxmin(), "Model"]
        st.dataframe(summary, use_container_width=True)
        st.success(f"✅ Fastest model: **{best}**")

        # Build interactive chart
        st.subheader("Interactive Chart")
        fig, ax = plt.subplots(figsize=(10, 4))
        colors = ["#4f86c6", "#e06c3e", "#5dba72", "#b05dba"]
        bars = ax.bar(summary["Model"], summary["Avg Response Time (ms)"], color=colors)
        for bar in bars:
            ax.text(bar.get_x() + bar.get_width()/2, bar.get_height(),
                    f"{bar.get_height():.2f}ms", ha="center", va="bottom", fontsize=9)
        ax.set_ylabel("Avg Response Time (ms)")
        plt.xticks(rotation=10)
        plt.tight_layout()
        st.pyplot(fig)


# ─────────────────────────────────────────────────────────────────────────────
# SCALABILITY PAGE
# ─────────────────────────────────────────────────────────────────────────────
elif page == "⚖️ Scalability":
    st.title("⚖️ Scalability Test")
    st.markdown("Simulated adding **5 new data sources** to each model and measured integration time.")

    if os.path.exists(CHARTS["scalability"]):
        st.image(CHARTS["scalability"], use_container_width=True)

    if not scalability.empty:
        st.subheader("Results Table")
        st.dataframe(scalability, use_container_width=True)

        best = scalability.loc[scalability["avg_time_per_source_ms"].idxmin(), "model"]
        st.success(f"✅ Most scalable: **{best}** (lowest avg time per new source)")


# ─────────────────────────────────────────────────────────────────────────────
# DATA QUALITY PAGE
# ─────────────────────────────────────────────────────────────────────────────
elif page == "✅ Data Quality":
    st.title("✅ Data Quality Assessment")
    st.markdown("Profiling **completeness**, **consistency**, and **accuracy** across all models.")

    if os.path.exists(CHARTS["dq"]):
        st.image(CHARTS["dq"], use_container_width=True)

    if not dq.empty:
        st.subheader("Per-Table Results")
        st.dataframe(dq, use_container_width=True)

        st.subheader("Model-Level Summary")
        summary = dq.groupby("model")[["completeness_pct", "consistency_pct", "accuracy_pct", "dq_score"]].mean().round(2)
        st.dataframe(summary, use_container_width=True)

        best = summary["dq_score"].idxmax()
        st.success(f"✅ Best overall DQ Score: **{best}** ({summary.loc[best, 'dq_score']}%)")

        # Heatmap
        st.subheader("Interactive Heatmap")
        fig, ax = plt.subplots(figsize=(10, 4))
        sns.heatmap(summary, annot=True, fmt=".1f", cmap="YlGn", linewidths=0.5, ax=ax)
        plt.tight_layout()
        st.pyplot(fig)


# ─────────────────────────────────────────────────────────────────────────────
# AI CAPABILITIES PAGE
# ─────────────────────────────────────────────────────────────────────────────
elif page == "🤖 AI Capabilities":
    st.title("🤖 AI Capabilities")

    if os.path.exists(CHARTS["ai"]):
        st.image(CHARTS["ai"], use_container_width=True)

    col1, col2 = st.columns(2)

    with col1:
        st.subheader("Anomaly Detection — Isolation Forest")
        if anomaly:
            st.metric("Precision", anomaly.get("precision_anomaly", "—"))
            st.metric("Recall",    anomaly.get("recall_anomaly", "—"))
            st.metric("F1 Score",  anomaly.get("f1_anomaly", "—"))
            st.metric("Accuracy",  anomaly.get("accuracy", "—"))
            c = anomaly.get("confusion_matrix")
            if c:
                st.markdown("**Confusion Matrix**")
                cm_df = pd.DataFrame(c, index=["Actual Normal","Actual Anomaly"], columns=["Pred Normal","Pred Anomaly"])
                st.dataframe(cm_df, use_container_width=True)
        else:
            st.info("Run `python ai/anomaly_detection.py` first.")

    with col2:
        st.subheader("Prediction Model — Random Forest")
        if prediction:
            st.metric("Accuracy",      prediction.get("accuracy", "—"))
            st.metric("F1 (High Value)", prediction.get("f1_hv", "—"))
            st.metric("CV Accuracy",   f"{prediction.get('cv_accuracy_mean','—')} ± {prediction.get('cv_accuracy_std','—')}")
            st.metric("Top Feature",   prediction.get("top_feature", "—"))
        else:
            st.info("Run `python ai/prediction_model.py` first.")


# ─────────────────────────────────────────────────────────────────────────────
# RAW DATA PAGE
# ─────────────────────────────────────────────────────────────────────────────
elif page == "📋 Raw Data":
    st.title("📋 Raw Result Data")
    tab1, tab2, tab3, tab4 = st.tabs(["ETL", "Benchmark", "Scalability", "Data Quality"])
    with tab1:
        st.dataframe(etl if not etl.empty else pd.DataFrame({"status": ["No data"]}), use_container_width=True)
    with tab2:
        st.dataframe(benchmark if not benchmark.empty else pd.DataFrame({"status": ["No data"]}), use_container_width=True)
    with tab3:
        st.dataframe(scalability if not scalability.empty else pd.DataFrame({"status": ["No data"]}), use_container_width=True)
    with tab4:
        st.dataframe(dq if not dq.empty else pd.DataFrame({"status": ["No data"]}), use_container_width=True)

    st.markdown("---")
    st.markdown("**Download Results**")
    excel_path = os.path.join(RESULTS, "results_summary.xlsx")
    if os.path.exists(excel_path):
        with open(excel_path, "rb") as f:
            st.download_button("📥 Download results_summary.xlsx", f, file_name="results_summary.xlsx")
