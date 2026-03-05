<!DOCTYPE html>
<html lang="en" class="dark">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>EDWH Analytics — Unified BI Dashboard</title>
    <meta name="description"
        content="Enterprise Data Warehouse Hybrid Analytics: Data Vault 2.0 + Lakehouse + AI Anomaly Detection" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 400: '#818cf8', 500: '#6366f1', 600: '#4f46e5' },
                        accent: { 400: '#34d399', 500: '#10b981' },
                        danger: { 400: '#f87171', 500: '#ef4444' },
                        warn: { 400: '#fbbf24', 500: '#f59e0b' },
                        surface: { 800: '#1e1e2e', 900: '#13131f', 950: '#0d0d17' },
                    },
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.6s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    },
                    keyframes: {
                        fadeIn: { from: { opacity: 0 }, to: { opacity: 1 } },
                        slideUp: { from: { opacity: 0, transform: 'translateY(20px)' }, to: { opacity: 1, transform: 'translateY(0)' } },
                    },
                }
            }
        };
    </script>

    <!-- Google Fonts: Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet" />

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(30, 30, 46, 0.95), rgba(19, 19, 31, 0.98));
            border: 1px solid rgba(99, 102, 241, 0.15);
            backdrop-filter: blur(16px);
            transition: border-color 0.3s ease, transform 0.2s ease;
        }

        .glass-card:hover {
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-2px);
        }

        .kpi-card {
            position: relative;
            overflow: hidden;
        }

        .kpi-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            border-radius: 12px 12px 0 0;
        }

        .kpi-card.blue::before {
            background: linear-gradient(90deg, #6366f1, #818cf8);
        }

        .kpi-card.green::before {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        .kpi-card.amber::before {
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
        }

        .kpi-card.red::before {
            background: linear-gradient(90deg, #ef4444, #f87171);
        }

        .kpi-card.purple::before {
            background: linear-gradient(90deg, #8b5cf6, #a78bfa);
        }

        .severity-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .severity-high {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .severity-medium {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .severity-low {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .loading-shimmer {
            animation: shimmer 1.5s infinite;
            background: linear-gradient(90deg, rgba(99, 102, 241, 0.05) 25%, rgba(99, 102, 241, 0.15) 50%, rgba(99, 102, 241, 0.05) 75%);
            background-size: 200% 100%;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-radius: 10px;
            cursor: pointer;
            transition: background 0.2s, color 0.2s;
            color: rgba(148, 163, 184, 0.8);
            font-size: 0.88rem;
            font-weight: 500;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
        }

        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: #13131f;
        }

        ::-webkit-scrollbar-thumb {
            background: #4f46e5;
            border-radius: 3px;
        }

        .pulse-dot::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 50%;
            animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;
            background: #10b981;
        }

        @keyframes ping {

            75%,
            100% {
                transform: scale(2);
                opacity: 0;
            }
        }

        .anomaly-row {
            transition: background 0.2s;
        }

        .anomaly-row:hover {
            background: rgba(99, 102, 241, 0.06);
        }

        .chart-container {
            position: relative;
            height: 280px;
        }
    </style>
</head>

<body class="bg-surface-950 text-slate-200 min-h-screen">

    <!-- ======== SIDEBAR ======== -->
    <div class="flex min-h-screen">
        <aside class="w-64 bg-surface-900 border-r border-indigo-900/20 flex flex-col" style="min-height:100vh;">
            <!-- Logo -->
            <div class="p-6 border-b border-indigo-900/20">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-sm shadow-lg">
                        E</div>
                    <div>
                        <div class="font-bold text-white text-sm tracking-wide">EDWH Analytics</div>
                        <div class="text-xs text-slate-500">Data Vault 2.0 + Lakehouse</div>
                    </div>
                </div>
            </div>

            <!-- Nav Links -->
            <nav class="flex-1 p-4 space-y-1">
                <div class="text-xs font-semibold text-slate-600 uppercase tracking-widest mb-3 px-3">Overview</div>
                <a class="sidebar-link active" onclick="scrollToSection('kpis')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6z M14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z M4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2z M14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    KPI Metrics
                </a>
                <a class="sidebar-link" onclick="scrollToSection('transactions')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                    Transactions
                </a>
                <a class="sidebar-link" onclick="scrollToSection('lakehouse')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                    Lakehouse
                </a>
                <a class="sidebar-link" onclick="scrollToSection('anomalies')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    AI Anomalies
                    <span id="anomaly-badge"
                        class="ml-auto text-xs bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-0.5 rounded-full hidden">0</span>
                </a>
                <a class="sidebar-link" onclick="scrollToSection('sales-mart')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10a2 2 0 01-2 2H9a2 2 0 01-2-2z" />
                    </svg>
                    Sales Data Mart
                </a>

                <div class="text-xs font-semibold text-slate-600 uppercase tracking-widest mt-6 mb-3 px-3">UDW Research
                </div>
                <a class="sidebar-link" onclick="scrollToSection('udw-benchmark')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Query Benchmark
                </a>
                <a class="sidebar-link" onclick="scrollToSection('udw-scalability')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    Scalability
                </a>
                <a class="sidebar-link" onclick="scrollToSection('udw-dq')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Data Quality
                </a>
                <a class="sidebar-link" onclick="scrollToSection('udw-ai')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                    </svg>
                    AI Capabilities
                </a>

                <div class="text-xs font-semibold text-slate-600 uppercase tracking-widest mt-6 mb-3 px-3">Architecture
                </div>
                <div class="px-3 py-2 text-xs text-slate-500 space-y-2">
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-indigo-500"></span> Data
                        Vault 2.0 Hubs</div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-purple-500"></span> Links
                        &amp; Satellites</div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-cyan-500"></span> Raw
                        Lakehouse</div>
                    <div class="flex items-center gap-2"><span class="w-2 h-2 rounded-full bg-green-500"></span> AI
                        Anomaly Engine</div>
                </div>
            </nav>

            <!-- Status -->
            <div class="p-4 border-t border-indigo-900/20">
                <div class="flex items-center gap-2">
                    <div class="relative w-2 h-2 flex-shrink-0">
                        <div class="pulse-dot"></div>
                        <div class="w-2 h-2 rounded-full bg-green-500 relative z-10"></div>
                    </div>
                    <span class="text-xs text-slate-400">Live MongoDB Connected</span>
                </div>
                <div class="mt-2 text-xs text-slate-600">Last refresh: <span id="last-refresh">—</span></div>
            </div>
        </aside>

        <!-- ======== MAIN CONTENT ======== -->
        <main class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <header
                class="sticky top-0 z-30 bg-surface-950/80 backdrop-blur border-b border-indigo-900/20 px-8 py-4 flex items-center justify-between">
                <div>
                    <h1 class="text-lg font-bold text-white">Unified Analytics Dashboard</h1>
                    <p class="text-xs text-slate-500">Data Vault 2.0 · Lakehouse · AI-Augmented · MongoDB</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/research-report.pdf" target="_blank"
                        class="flex items-center gap-2 text-xs bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-2 rounded-lg transition-all font-medium shadow-lg shadow-emerald-900/30">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Download Report
                    </a>
                    <button onclick="refreshAll()" id="refresh-btn"
                        class="flex items-center gap-2 text-xs bg-indigo-600 hover:bg-indigo-500 text-white px-4 py-2 rounded-lg transition-all font-medium shadow-lg shadow-indigo-900/30">
                        <svg id="refresh-icon" class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Refresh Data
                    </button>
                    <div class="text-right">
                        <div class="text-xs text-slate-500">Environment</div>
                        <div class="text-xs font-semibold text-indigo-400">PoC · Local</div>
                    </div>
                </div>
            </header>

            <div class="p-8 space-y-8 animate-fade-in">

                <!-- ======== KPI CARDS ======== -->
                <section id="kpis">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Key Performance
                        Indicators</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
                        <!-- Total Records -->
                        <div class="glass-card kpi-card blue rounded-2xl p-5 col-span-1">
                            <div class="text-xs text-slate-400 font-medium mb-1">Total Records</div>
                            <div id="kpi-total" class="text-3xl font-bold text-white my-2 loading-shimmer rounded">—
                            </div>
                            <div class="text-xs text-indigo-400">All collections</div>
                        </div>
                        <!-- Structured -->
                        <div class="glass-card kpi-card green rounded-2xl p-5">
                            <div class="text-xs text-slate-400 font-medium mb-1">Structured (DV2)</div>
                            <div id="kpi-structured" class="text-3xl font-bold text-white my-2">—</div>
                            <div class="text-xs text-emerald-400">Hub + Link + Sat</div>
                        </div>
                        <!-- Unstructured -->
                        <div class="glass-card kpi-card amber rounded-2xl p-5">
                            <div class="text-xs text-slate-400 font-medium mb-1">Unstructured (Lake)</div>
                            <div id="kpi-unstructured" class="text-3xl font-bold text-white my-2">—</div>
                            <div class="text-xs text-amber-400">Raw Data Lake</div>
                        </div>
                        <!-- Transactions -->
                        <div class="glass-card kpi-card purple rounded-2xl p-5">
                            <div class="text-xs text-slate-400 font-medium mb-1">Transactions</div>
                            <div id="kpi-transactions" class="text-3xl font-bold text-white my-2">—</div>
                            <div class="text-xs text-purple-400">Link_Transactions</div>
                        </div>
                        <!-- Anomalies -->
                        <div class="glass-card kpi-card red rounded-2xl p-5">
                            <div class="text-xs text-slate-400 font-medium mb-1">AI Anomalies</div>
                            <div id="kpi-anomalies" class="text-3xl font-bold text-red-400 my-2">—</div>
                            <div class="text-xs text-red-400">Isolation Forest</div>
                        </div>
                    </div>

                    <!-- Collection breakdown row -->
                    <div id="collection-breakdown" class="grid grid-cols-3 lg:grid-cols-6 gap-3 mt-4"></div>
                </section>

                <!-- ======== CHARTS ROW ======== -->
                <section id="transactions">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Transaction Analysis
                    </h2>
                    <div class="grid lg:grid-cols-3 gap-6">
                        <!-- Line Chart: Revenue over time -->
                        <div class="glass-card rounded-2xl p-6 lg:col-span-2">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <h3 class="font-semibold text-white text-sm">Revenue Over Time</h3>
                                    <p class="text-xs text-slate-500 mt-0.5">Daily aggregated transaction revenue from
                                        Data Vault</p>
                                </div>
                                <div class="flex gap-3 text-xs">
                                    <span class="flex items-center gap-1.5"><span
                                            class="w-3 h-1 bg-indigo-500 rounded-full inline-block"></span>Revenue</span>
                                    <span class="flex items-center gap-1.5"><span
                                            class="w-3 h-1 bg-emerald-500 rounded-full inline-block"></span>Count</span>
                                </div>
                            </div>
                            <div class="chart-container">
                                <canvas id="revenueChart"></canvas>
                            </div>
                        </div>

                        <!-- Donut: Structured vs Unstructured -->
                        <div class="glass-card rounded-2xl p-6" id="lakehouse">
                            <div class="mb-4">
                                <h3 class="font-semibold text-white text-sm">Data Distribution</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Structured DV2 vs Raw Lakehouse</p>
                            </div>
                            <div class="chart-container" style="height:220px;">
                                <canvas id="distributionChart"></canvas>
                            </div>
                            <div id="distribution-legend" class="mt-4 space-y-2 text-xs"></div>
                        </div>
                    </div>
                </section>

                <!-- ======== SALES DATA MART + CATEGORY CHART ======== -->
                <section id="sales-mart">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Sales Data Mart
                        (Star Schema)</h2>
                    <div class="grid lg:grid-cols-2 gap-6">
                        <!-- Category bar chart -->
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-1">Revenue by Category</h3>
                            <p class="text-xs text-slate-500 mb-4">Dimensional rollup via MongoDB aggregation pipeline
                            </p>
                            <div class="chart-container">
                                <canvas id="categoryChart"></canvas>
                            </div>
                        </div>

                        <!-- Top Customers table -->
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-1">Top Customers by Revenue</h3>
                            <p class="text-xs text-slate-500 mb-4">From Satellite + Hub join (customer dimension)</p>
                            <div id="top-customers-table" class="overflow-x-auto">
                                <div class="loading-shimmer h-40 rounded-xl"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Status breakdown -->
                    <div class="glass-card rounded-2xl p-6 mt-6">
                        <h3 class="font-semibold text-white text-sm mb-1">Transaction Status Breakdown</h3>
                        <p class="text-xs text-slate-500 mb-4">Completed / Pending / Refunded distribution</p>
                        <div class="chart-container" style="height:200px;">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </section>

                <!-- ======== ANOMALY PANEL ======== -->
                <section id="anomalies">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest">AI Anomaly
                                Detection Results</h2>
                            <p class="text-xs text-slate-600 mt-1">Isolation Forest · Python scikit-learn · MongoDB
                                anomaly_reports</p>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="filterAnomalies('high')"
                                class="text-xs px-3 py-1.5 rounded-lg bg-red-500/10 text-red-400 border border-red-500/20 hover:bg-red-500/20 transition-colors">High</button>
                            <button onclick="filterAnomalies('medium')"
                                class="text-xs px-3 py-1.5 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20 hover:bg-amber-500/20 transition-colors">Medium</button>
                            <button onclick="filterAnomalies('low')"
                                class="text-xs px-3 py-1.5 rounded-lg bg-green-500/10 text-green-400 border border-green-500/20 hover:bg-green-500/20 transition-colors">Low</button>
                            <button onclick="filterAnomalies(null)"
                                class="text-xs px-3 py-1.5 rounded-lg bg-indigo-500/10 text-indigo-400 border border-indigo-500/20 hover:bg-indigo-500/20 transition-colors">All</button>
                        </div>
                    </div>

                    <div class="glass-card rounded-2xl overflow-hidden">
                        <table class="w-full text-xs">
                            <thead>
                                <tr class="border-b border-indigo-900/30 text-left">
                                    <th class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider w-20">
                                        Severity</th>
                                    <th class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider">
                                        Collection</th>
                                    <th class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider">Score
                                    </th>
                                    <th
                                        class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider hidden lg:table-cell">
                                        Reason</th>
                                    <th
                                        class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider hidden lg:table-cell">
                                        Amount</th>
                                    <th class="px-5 py-3 text-slate-500 font-semibold uppercase tracking-wider">Flagged
                                        At</th>
                                </tr>
                            </thead>
                            <tbody id="anomaly-table-body">
                                <tr>
                                    <td colspan="6" class="px-5 py-10 text-center">
                                        <div class="loading-shimmer h-6 rounded w-40 mx-auto mb-2"></div>
                                        <div class="loading-shimmer h-6 rounded w-60 mx-auto"></div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div id="anomaly-footer" class="px-5 py-3 border-t border-indigo-900/20 text-xs text-slate-600">
                        </div>
                    </div>
                </section>

                <!-- ======== ARCHITECTURE SCHEMA ======== -->
                <section class="glass-card rounded-2xl p-6">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-4">Architecture
                        Overview</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
                        <div class="bg-indigo-900/20 border border-indigo-800/30 rounded-xl p-4">
                            <div class="text-indigo-400 font-bold mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="10" stroke-width="2" />
                                    <path stroke-linecap="round" stroke-width="2" d="M12 8v4m0 4h.01" />
                                </svg>
                                Data Vault Hubs
                            </div>
                            <div class="text-slate-400 space-y-1">
                                <div>• hub_customers</div>
                                <div>• hub_products</div>
                            </div>
                            <div class="mt-3 text-slate-600">SHA-256 Hash Keys</div>
                        </div>
                        <div class="bg-purple-900/20 border border-purple-800/30 rounded-xl p-4">
                            <div class="text-purple-400 font-bold mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Links &amp; Satellites
                            </div>
                            <div class="text-slate-400 space-y-1">
                                <div>• link_transactions</div>
                                <div>• sat_customer_details</div>
                                <div>• sat_product_details</div>
                            </div>
                            <div class="mt-3 text-slate-600">Time-variant SCD</div>
                        </div>
                        <div class="bg-cyan-900/20 border border-cyan-800/30 rounded-xl p-4">
                            <div class="text-cyan-400 font-bold mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7" />
                                </svg>
                                Raw Lakehouse
                            </div>
                            <div class="text-slate-400 space-y-1">
                                <div>• raw_data_lake</div>
                                <div>• Server logs</div>
                                <div>• Customer feedback</div>
                            </div>
                            <div class="mt-3 text-slate-600">Semi-structured JSON</div>
                        </div>
                        <div class="bg-green-900/20 border border-green-800/30 rounded-xl p-4">
                            <div class="text-green-400 font-bold mb-2 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                </svg>
                                AI Engine
                            </div>
                            <div class="text-slate-400 space-y-1">
                                <div>• anomaly_reports</div>
                                <div>• Isolation Forest</div>
                                <div>• Python 3 + sklearn</div>
                            </div>
                            <div class="mt-3 text-slate-600">Automated flagging</div>
                        </div>
                    </div>
                </section>

            </div>

            <!-- ======== UDW RESEARCH: BENCHMARK ======== -->
            <div class="p-8 space-y-8">

                <section id="udw-benchmark">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-1">UDW Research — Query
                        Benchmark</h2>
                    <p class="text-xs text-slate-600 mb-4">Average query response time (ms) across 5 standardized
                        queries × 5 runs per model</p>
                    <!-- ETL KPIs -->
                    <div id="udw-etl-kpis" class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6"></div>
                    <div class="grid lg:grid-cols-2 gap-6">
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-1">Avg Query Time by Model</h3>
                            <p class="text-xs text-slate-500 mb-4">Lower is better</p>
                            <div class="chart-container"><canvas id="udwBenchmarkChart"></canvas></div>
                        </div>
                        <div class="glass-card rounded-2xl p-6 overflow-x-auto">
                            <h3 class="font-semibold text-white text-sm mb-3">Detailed Query Results</h3>
                            <table class="w-full text-xs" id="udw-benchmark-table">
                                <thead>
                                    <tr class="text-left text-slate-500 border-b border-indigo-900/20">
                                        <th class="pb-2">Model</th>
                                        <th class="pb-2">Query</th>
                                        <th class="pb-2 text-right">Avg (ms)</th>
                                    </tr>
                                </thead>
                                <tbody id="udw-benchmark-tbody">
                                    <tr>
                                        <td colspan="3" class="py-8 text-center">
                                            <div class="loading-shimmer h-4 rounded w-48 mx-auto"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======== UDW RESEARCH: SCALABILITY ======== -->
                <section id="udw-scalability">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-1">UDW Research —
                        Scalability</h2>
                    <p class="text-xs text-slate-600 mb-4">Time (ms) to integrate 5 new heterogeneous data sources per
                        model</p>
                    <div class="glass-card rounded-2xl p-6">
                        <div class="chart-container" style="height:220px;"><canvas id="udwScalabilityChart"></canvas>
                        </div>
                    </div>
                </section>

                <!-- ======== UDW RESEARCH: DATA QUALITY ======== -->
                <section id="udw-dq">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-1">UDW Research — Data
                        Quality</h2>
                    <p class="text-xs text-slate-600 mb-4">Completeness · Consistency · Accuracy scores per model</p>
                    <div class="grid lg:grid-cols-2 gap-6">
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-3">DQ Score by Model</h3>
                            <div class="chart-container"><canvas id="udwDQChart"></canvas></div>
                        </div>
                        <div class="glass-card rounded-2xl p-6 overflow-x-auto">
                            <h3 class="font-semibold text-white text-sm mb-3">Per-Table DQ Breakdown</h3>
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-left text-slate-500 border-b border-indigo-900/20">
                                        <th class="pb-2">Model</th>
                                        <th class="pb-2">Table</th>
                                        <th class="pb-2 text-right">Complete</th>
                                        <th class="pb-2 text-right">Score</th>
                                    </tr>
                                </thead>
                                <tbody id="udw-dq-tbody">
                                    <tr>
                                        <td colspan="4" class="py-8 text-center">
                                            <div class="loading-shimmer h-4 rounded w-48 mx-auto"></div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- ======== UDW RESEARCH: AI CAPABILITIES ======== -->
                <section id="udw-ai">
                    <h2 class="text-sm font-semibold text-slate-400 uppercase tracking-widest mb-1">UDW Research — AI
                        Capabilities</h2>
                    <p class="text-xs text-slate-600 mb-4">Isolation Forest anomaly detection + Random Forest prediction
                        model metrics</p>
                    <div class="grid lg:grid-cols-2 gap-6">
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-4">AI Model Metrics (Precision · Recall · F1)
                            </h3>
                            <div class="chart-container"><canvas id="udwAIChart"></canvas></div>
                        </div>
                        <div class="glass-card rounded-2xl p-6">
                            <h3 class="font-semibold text-white text-sm mb-4">Detailed Scores</h3>
                            <div id="udw-ai-details" class="space-y-3 text-xs"></div>
                        </div>
                    </div>
                </section>

            </div><!-- /UDW Research wrapper -->

        </main>
    </div>


    <script>
        const API = '/api/analytics';
        let revenueChart, distributionChart, categoryChart, statusChart;
        let allAnomalies = [];

        // ===================== CHART DEFAULTS =====================
        Chart.defaults.color = 'rgba(148,163,184,0.7)';
        Chart.defaults.borderColor = 'rgba(99,102,241,0.1)';
        Chart.defaults.font.family = 'Inter';
        Chart.defaults.font.size = 11;

        // ===================== UTILS =====================
        const fmtNum = n => n?.toLocaleString() ?? '—';
        const fmtCurrency = n => n != null ? '$' + n.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) : '—';

        function scrollToSection(id) {
            document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            document.querySelectorAll('.sidebar-link').forEach(l => l.classList.remove('active'));
            event?.target?.closest('.sidebar-link')?.classList.add('active');
        }

        function spinRefreshIcon(spinning) {
            const icon = document.getElementById('refresh-icon');
            icon.style.animation = spinning ? 'spin 1s linear infinite' : '';
        }

        function setKPI(id, value, prefix = '') {
            const el = document.getElementById(id);
            if (el) { el.innerText = prefix + fmtNum(value); el.classList.remove('loading-shimmer'); }
        }

        // ===================== FETCH SUMMARY =====================
        async function fetchSummary() {
            const res = await fetch(`${API}/summary`);
            const json = await res.json();
            const d = json.data;

            setKPI('kpi-total', d.total_records);
            setKPI('kpi-structured', d.structured_records);
            setKPI('kpi-unstructured', d.unstructured_records);
            setKPI('kpi-transactions', d.transactions);
            setKPI('kpi-anomalies', d.anomaly_count);

            // Anomaly badge in sidebar
            const badge = document.getElementById('anomaly-badge');
            if (d.anomaly_count > 0) {
                badge.innerText = d.anomaly_count;
                badge.classList.remove('hidden');
            }

            // Collection breakdown pills
            const breakdown = document.getElementById('collection-breakdown');
            const colors = { hub_customers: 'indigo', link_transactions: 'purple', sat_customer_details: 'blue', sat_product_details: 'cyan', raw_data_lake: 'amber', anomaly_reports: 'red' };
            const labels = { hub_customers: 'Hub Customers', link_transactions: 'Transactions', sat_customer_details: 'Sat Customer', sat_product_details: 'Sat Product', raw_data_lake: 'Data Lake', anomaly_reports: 'Anomalies' };

            breakdown.innerHTML = Object.entries(d.collections).map(([k, v]) => `
        <div class="glass-card rounded-xl p-3 text-center">
            <div class="text-xl font-bold text-${colors[k]}-400">${fmtNum(v)}</div>
            <div class="text-xs text-slate-500 mt-1">${labels[k] || k}</div>
        </div>
    `).join('');

            // Distribution donut
            renderDistributionChart(d.structured_records, d.unstructured_records);

            return d;
        }

        // ===================== FETCH TRANSACTIONS =====================
        async function fetchTransactions() {
            const res = await fetch(`${API}/transactions`);
            const json = await res.json();
            const d = json.data;

            if (!d.labels?.length) return;
            renderRevenueChart(d.labels, d.revenue, d.transaction_counts);
        }

        // ===================== FETCH ANOMALIES =====================
        async function fetchAnomalies(severity = null) {
            const url = severity ? `${API}/anomalies?severity=${severity}` : `${API}/anomalies`;
            const res = await fetch(url);
            const json = await res.json();
            allAnomalies = json.data || [];

            renderAnomalyTable(allAnomalies, json.count || 0);
        }

        function filterAnomalies(severity) {
            fetchAnomalies(severity);
        }

        // ===================== FETCH SALES MART =====================
        async function fetchSalesMart() {
            const res = await fetch(`${API}/data-mart/sales`);
            const json = await res.json();
            const d = json.data;

            if (d?.by_category?.length) renderCategoryChart(d.by_category);
            if (d?.by_status?.length) renderStatusChart(d.by_status);
            if (d?.top_customers?.length) renderTopCustomersTable(d.top_customers);
        }

        // ===================== CHARTS =====================
        function renderRevenueChart(labels, revenue, counts) {
            if (revenueChart) revenueChart.destroy();
            const ctx = document.getElementById('revenueChart');
            revenueChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Revenue ($)',
                            data: revenue,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99,102,241,0.1)',
                            fill: true,
                            tension: 0.4,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#818cf8',
                            borderWidth: 2,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Transactions',
                            data: counts,
                            borderColor: '#10b981',
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0.4,
                            pointRadius: 2,
                            borderWidth: 1.5,
                            borderDash: [4, 4],
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(99,102,241,0.06)' }, ticks: { maxTicksLimit: 8 } },
                        y: {
                            grid: { color: 'rgba(99,102,241,0.06)' },
                            ticks: { callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) }
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                            ticks: { color: '#10b981' }
                        }
                    }
                }
            });
        }

        function renderDistributionChart(structured, unstructured) {
            if (distributionChart) distributionChart.destroy();
            const ctx = document.getElementById('distributionChart');
            distributionChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['Structured (DV2)', 'Unstructured (Lake)'],
                    datasets: [{
                        data: [structured, unstructured],
                        backgroundColor: ['rgba(99,102,241,0.8)', 'rgba(245,158,11,0.8)'],
                        borderColor: ['rgba(99,102,241,1)', 'rgba(245,158,11,1)'],
                        borderWidth: 2,
                        hoverOffset: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '72%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` ${fmtNum(ctx.parsed)} records` } }
                    }
                }
            });

            document.getElementById('distribution-legend').innerHTML = `
        <div class="flex justify-between">
            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>Structured</span>
            <span class="font-semibold text-white">${fmtNum(structured)}</span>
        </div>
        <div class="flex justify-between">
            <span class="flex items-center gap-2"><span class="w-2.5 h-2.5 rounded-full bg-amber-500 inline-block"></span>Unstructured</span>
            <span class="font-semibold text-white">${fmtNum(unstructured)}</span>
        </div>
    `;
        }

        function renderCategoryChart(categories) {
            if (categoryChart) categoryChart.destroy();
            const ctx = document.getElementById('categoryChart');
            const labels = categories.map(c => c.category || 'N/A');
            const values = categories.map(c => c.total_revenue);
            const palette = ['rgba(99,102,241,0.85)', 'rgba(139,92,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)', 'rgba(239,68,68,0.85)', 'rgba(6,182,212,0.85)'];

            categoryChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Revenue',
                        data: values,
                        backgroundColor: palette,
                        borderColor: palette.map(c => c.replace('0.85', '1')),
                        borderWidth: 1,
                        borderRadius: 6,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false } },
                        y: {
                            grid: { color: 'rgba(99,102,241,0.06)' },
                            ticks: { callback: v => '$' + (v >= 1000 ? (v / 1000).toFixed(0) + 'k' : v) }
                        }
                    }
                }
            });
        }

        function renderStatusChart(statuses) {
            if (statusChart) statusChart.destroy();
            const ctx = document.getElementById('statusChart');
            const labels = statuses.map(s => s.status || 'N/A');
            const values = statuses.map(s => s.count);
            const colors = { completed: 'rgba(16,185,129,0.8)', pending: 'rgba(245,158,11,0.8)', refunded: 'rgba(239,68,68,0.8)' };

            statusChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Count',
                        data: values,
                        backgroundColor: labels.map(l => colors[l] || 'rgba(99,102,241,0.8)'),
                        borderRadius: 4,
                        borderSkipped: false,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: 'rgba(99,102,241,0.06)' } },
                        y: { grid: { display: false } }
                    }
                }
            });
        }

        function renderTopCustomersTable(customers) {
            const table = `
        <table class="w-full text-xs">
            <thead>
                <tr class="text-left text-slate-500 border-b border-indigo-900/20">
                    <th class="pb-2">Name</th>
                    <th class="pb-2">Segment</th>
                    <th class="pb-2 text-right">Revenue</th>
                    <th class="pb-2 text-right">Orders</th>
                </tr>
            </thead>
            <tbody>
                ${customers.slice(0, 8).map((c, i) => `
                <tr class="border-b border-indigo-900/10 hover:bg-indigo-900/10 transition-colors">
                    <td class="py-2 font-medium text-white">
                        <span class="text-indigo-400 mr-2">#${i + 1}</span>${c.name}
                    </td>
                    <td class="py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs ${c.segment === 'premium' ? 'bg-purple-900/40 text-purple-300' : c.segment === 'standard' ? 'bg-blue-900/40 text-blue-300' : 'bg-slate-800 text-slate-400'}">${c.segment}</span>
                    </td>
                    <td class="py-2 text-right font-semibold text-emerald-400">${fmtCurrency(c.revenue)}</td>
                    <td class="py-2 text-right text-slate-400">${c.orders}</td>
                </tr>`).join('')}
            </tbody>
        </table>
    `;
            document.getElementById('top-customers-table').innerHTML = table;
        }

        function renderAnomalyTable(anomalies, count) {
            const tbody = document.getElementById('anomaly-table-body');
            const footer = document.getElementById('anomaly-footer');

            if (!anomalies.length) {
                tbody.innerHTML = `
            <tr><td colspan="6" class="px-5 py-12 text-center">
                <div class="text-slate-600 text-2xl mb-2">✓</div>
                <div class="text-slate-400 font-medium">No anomalies detected</div>
                <div class="text-slate-600 text-xs mt-1">Run <code class="bg-slate-800 px-1 rounded">php artisan analytics:detect-anomalies</code> to populate</div>
            </td></tr>`;
                footer.innerText = 'No anomalies found.';
                return;
            }

            tbody.innerHTML = anomalies.map(a => {
                const sev = a.severity || 'medium';
                const fv = a.field_values || {};
                const amount = fv.amount != null ? `$${parseFloat(fv.amount).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '—';
                const ts = a.flagged_at ? new Date(a.flagged_at).toLocaleString() : '—';
                return `
            <tr class="anomaly-row border-b border-indigo-900/10 text-xs">
                <td class="px-5 py-3">
                    <span class="severity-badge severity-${sev}">${sev}</span>
                </td>
                <td class="px-5 py-3 text-slate-400 font-mono">${a.source_collection}</td>
                <td class="px-5 py-3 tabular-nums ${sev === 'high' ? 'text-red-400' : sev === 'medium' ? 'text-amber-400' : 'text-green-400'}">${parseFloat(a.anomaly_score).toFixed(4)}</td>
                <td class="px-5 py-3 text-slate-400 hidden lg:table-cell max-w-xs truncate">${a.reason}</td>
                <td class="px-5 py-3 font-mono text-indigo-300 hidden lg:table-cell">${amount}</td>
                <td class="px-5 py-3 text-slate-500">${ts}</td>
            </tr>
        `;
            }).join('');

            footer.innerText = `Showing ${anomalies.length} of ${count} total anomaly reports · Sorted by flagged time (desc)`;
        }

        // ===================== UDW RESEARCH =====================
        let udwBenchmarkChart, udwScalabilityChart, udwDQChart, udwAIChart;

        async function fetchUdwResearch() {
            try {
                const res = await fetch('/api/analytics/udw-research');
                const d = await res.json();

                // --- ETL KPI cards ---
                const etlColors = ['blue', 'green', 'amber', 'purple'];
                const etlKpis = document.getElementById('udw-etl-kpis');
                if (d.etl?.length) {
                    etlKpis.innerHTML = d.etl.map((row, i) => `
                        <div class="glass-card kpi-card ${etlColors[i] || 'blue'} rounded-2xl p-4">
                            <div class="text-xs text-slate-400 mb-1">${row.model}</div>
                            <div class="text-lg font-bold ${row.status === 'SUCCESS' ? 'text-emerald-400' : 'text-red-400'}">${row.status}</div>
                            <div class="text-xs text-slate-500 mt-1">Build: ${parseFloat(row.build_time_s || 0).toFixed(2)}s</div>
                        </div>`).join('');
                }

                // --- Benchmark chart ---
                const modelColors = ['rgba(99,102,241,0.85)', 'rgba(139,92,246,0.85)', 'rgba(16,185,129,0.85)', 'rgba(245,158,11,0.85)'];
                if (d.benchmark?.length) {
                    // Aggregate: avg per model
                    const agg = {};
                    d.benchmark.forEach(r => { agg[r.model] = agg[r.model] || []; agg[r.model].push(parseFloat(r.avg_ms)); });
                    const bLabels = Object.keys(agg);
                    const bVals = bLabels.map(m => (agg[m].reduce((a, b) => a + b, 0) / agg[m].length).toFixed(3));

                    if (udwBenchmarkChart) udwBenchmarkChart.destroy();
                    udwBenchmarkChart = new Chart(document.getElementById('udwBenchmarkChart'), {
                        type: 'bar',
                        data: { labels: bLabels, datasets: [{ label: 'Avg ms', data: bVals, backgroundColor: modelColors, borderRadius: 6, borderSkipped: false }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                            scales: { x: { grid: { display: false } }, y: { grid: { color: 'rgba(99,102,241,0.06)' }, ticks: { callback: v => v + 'ms' } } }
                        }
                    });

                    // Table
                    document.getElementById('udw-benchmark-tbody').innerHTML = d.benchmark.slice(0, 20).map(r =>
                        `<tr class="border-b border-indigo-900/10 hover:bg-indigo-900/10">
                            <td class="py-1.5 text-indigo-300">${r.model}</td>
                            <td class="py-1.5 text-slate-400">${r.query_name}</td>
                            <td class="py-1.5 text-right font-mono text-emerald-400">${parseFloat(r.avg_ms).toFixed(3)}</td>
                        </tr>`).join('');
                }

                // --- Scalability chart ---
                if (d.scalability?.length) {
                    const sLabels = d.scalability.map(r => r.model);
                    const sVals = d.scalability.map(r => parseFloat(r.avg_time_per_source_ms));
                    if (udwScalabilityChart) udwScalabilityChart.destroy();
                    udwScalabilityChart = new Chart(document.getElementById('udwScalabilityChart'), {
                        type: 'bar',
                        data: { labels: sLabels, datasets: [{ label: 'ms/source', data: sVals, backgroundColor: modelColors, borderRadius: 6, borderSkipped: false }] },
                        options: {
                            responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                            scales: { x: { grid: { display: false } }, y: { grid: { color: 'rgba(99,102,241,0.06)' }, ticks: { callback: v => v + 'ms' } } }
                        }
                    });
                }

                // --- DQ chart ---
                if (d.dq?.length) {
                    const dqAgg = {};
                    d.dq.forEach(r => {
                        if (!dqAgg[r.model]) dqAgg[r.model] = { complete: [], score: [] };
                        dqAgg[r.model].complete.push(parseFloat(r.completeness_pct || 0));
                        dqAgg[r.model].score.push(parseFloat(r.dq_score || 0));
                    });
                    const dqLabels = Object.keys(dqAgg);
                    const avg = arr => (arr.reduce((a, b) => a + b, 0) / arr.length).toFixed(1);
                    const dqScores = dqLabels.map(m => avg(dqAgg[m].score));
                    const dqComplete = dqLabels.map(m => avg(dqAgg[m].complete));

                    if (udwDQChart) udwDQChart.destroy();
                    udwDQChart = new Chart(document.getElementById('udwDQChart'), {
                        type: 'bar',
                        data: {
                            labels: dqLabels,
                            datasets: [
                                { label: 'DQ Score', data: dqScores, backgroundColor: 'rgba(16,185,129,0.8)', borderRadius: 4, borderSkipped: false },
                                { label: 'Completeness', data: dqComplete, backgroundColor: 'rgba(99,102,241,0.6)', borderRadius: 4, borderSkipped: false }
                            ]
                        },
                        options: {
                            responsive: true, maintainAspectRatio: false,
                            plugins: { legend: { labels: { color: 'rgba(148,163,184,0.8)', font: { size: 11 } } } },
                            scales: { x: { grid: { display: false } }, y: { max: 100, grid: { color: 'rgba(99,102,241,0.06)' }, ticks: { callback: v => v + '%' } } }
                        }
                    });

                    document.getElementById('udw-dq-tbody').innerHTML = d.dq.slice(0, 16).map(r =>
                        `<tr class="border-b border-indigo-900/10 hover:bg-indigo-900/10">
                            <td class="py-1.5 text-indigo-300">${r.model}</td>
                            <td class="py-1.5 text-slate-400">${r.table_name}</td>
                            <td class="py-1.5 text-right text-emerald-400">${parseFloat(r.completeness_pct).toFixed(1)}%</td>
                            <td class="py-1.5 text-right font-semibold text-white">${parseFloat(r.dq_score).toFixed(1)}%</td>
                        </tr>`).join('');
                }

                // --- AI chart ---
                const an = d.anomaly || {}, pr = d.prediction || {};
                const aiLabels = ['Anomaly Detection\n(Isolation Forest)', 'Prediction\n(Random Forest)'];
                const precision = [parseFloat(an.precision_anomaly || 0), parseFloat(pr.precision_hv || 0)];
                const recall = [parseFloat(an.recall_anomaly || 0), parseFloat(pr.recall_hv || 0)];
                const f1 = [parseFloat(an.f1_anomaly || 0), parseFloat(pr.f1_hv || 0)];

                if (udwAIChart) udwAIChart.destroy();
                udwAIChart = new Chart(document.getElementById('udwAIChart'), {
                    type: 'bar',
                    data: {
                        labels: aiLabels,
                        datasets: [
                            { label: 'Precision', data: precision, backgroundColor: 'rgba(99,102,241,0.85)', borderRadius: 4, borderSkipped: false },
                            { label: 'Recall', data: recall, backgroundColor: 'rgba(16,185,129,0.85)', borderRadius: 4, borderSkipped: false },
                            { label: 'F1 Score', data: f1, backgroundColor: 'rgba(245,158,11,0.85)', borderRadius: 4, borderSkipped: false },
                        ]
                    },
                    options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { labels: { color: 'rgba(148,163,184,0.8)', font: { size: 11 } } } },
                        scales: { x: { grid: { display: false } }, y: { max: 1, grid: { color: 'rgba(99,102,241,0.06)' } } }
                    }
                });

                document.getElementById('udw-ai-details').innerHTML = `
                    <div class="p-3 bg-indigo-900/20 border border-indigo-800/30 rounded-xl">
                        <div class="text-indigo-400 font-semibold mb-2">Anomaly Detection (Isolation Forest)</div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>Precision: <span class="text-white font-bold">${(an.precision_anomaly || 0)}</span></div>
                            <div>Recall: <span class="text-white font-bold">${(an.recall_anomaly || 0)}</span></div>
                            <div>F1: <span class="text-emerald-400 font-bold">${(an.f1_anomaly || 0)}</span></div>
                            <div>Accuracy: <span class="text-white font-bold">${(an.accuracy || 0)}</span></div>
                        </div>
                    </div>
                    <div class="p-3 bg-purple-900/20 border border-purple-800/30 rounded-xl">
                        <div class="text-purple-400 font-semibold mb-2">Prediction Model (Random Forest)</div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>Accuracy: <span class="text-white font-bold">${(pr.accuracy || 0)}</span></div>
                            <div>F1 (HV): <span class="text-white font-bold">${(pr.f1_hv || 0)}</span></div>
                            <div>CV Mean: <span class="text-emerald-400 font-bold">${(pr.cv_accuracy_mean || 0)}</span></div>
                            <div>Top Feature: <span class="text-amber-400 font-bold">${(pr.top_feature || '—')}</span></div>
                        </div>
                    </div>`;

            } catch (e) { console.warn('UDW Research data not available:', e.message); }
        }

        // ===================== MAIN LOADER =====================
        async function refreshAll() {
            spinRefreshIcon(true);
            document.getElementById('refresh-btn').disabled = true;

            try {
                await Promise.all([
                    fetchSummary(),
                    fetchTransactions(),
                    fetchAnomalies(),
                    fetchSalesMart(),
                    fetchUdwResearch(),
                ]);
                document.getElementById('last-refresh').innerText = new Date().toLocaleTimeString();
            } catch (err) {
                console.error('Dashboard refresh error:', err);
                // Show a non-blocking notification
                const toast = document.createElement('div');
                toast.className = 'fixed bottom-6 right-6 bg-red-900/90 border border-red-700 text-red-200 text-xs px-4 py-3 rounded-xl z-50 shadow-xl';
                toast.innerText = '⚠ API Error: ' + err.message;
                document.body.appendChild(toast);
                setTimeout(() => toast.remove(), 4000);
            } finally {
                spinRefreshIcon(false);
                document.getElementById('refresh-btn').disabled = false;
            }
        }

        // Add spin keyframe dynamically
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
        document.head.appendChild(style);

        // Initial load
        refreshAll();
    </script>

</body>

</html>