<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vault\AnomalyReport;
use App\Models\Vault\HubCustomer;
use App\Models\Vault\LinkTransaction;
use App\Models\Vault\RawDataLake;
use App\Models\Vault\SatCustomerDetail;
use App\Models\Vault\SatProductDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    /**
     * GET /api/analytics/summary
     * Returns total record counts across all collections.
     */
    public function summary(): JsonResponse
    {
        $structured = LinkTransaction::count() + HubCustomer::count() + SatCustomerDetail::count() + SatProductDetail::count();
        $unstructured = RawDataLake::count();
        $anomalies = AnomalyReport::count();
        $transactions = LinkTransaction::count();
        $customers = HubCustomer::count();

        return response()->json([
            'success' => true,
            'data' => [
                'structured_records' => $structured,
                'unstructured_records' => $unstructured,
                'total_records' => $structured + $unstructured,
                'transactions' => $transactions,
                'customers' => $customers,
                'anomaly_count' => $anomalies,
                'collections' => [
                    'hub_customers' => HubCustomer::count(),
                    'link_transactions' => $transactions,
                    'sat_customer_details' => SatCustomerDetail::count(),
                    'sat_product_details' => SatProductDetail::count(),
                    'raw_data_lake' => $unstructured,
                    'anomaly_reports' => $anomalies,
                ],
            ],
        ]);
    }

    /**
     * GET /api/analytics/transactions
     * Time-series chart data: transactions aggregated by day.
     */
    public function transactions(): JsonResponse
    {
        // Aggregate transactions by date using MongoDB pipeline
        $pipeline = [
            [
                '$project' => [
                    'date' => [
                        '$dateToString' => [
                            'format' => '%Y-%m-%d',
                            'date' => '$load_dts',
                        ],
                    ],
                    'amount' => 1,
                    'quantity' => 1,
                    'status' => 1,
                ],
            ],
            [
                '$group' => [
                    '_id' => '$date',
                    'total_revenue' => ['$sum' => '$amount'],
                    'count' => ['$sum' => 1],
                    'avg_amount' => ['$avg' => '$amount'],
                    'total_qty' => ['$sum' => '$quantity'],
                ],
            ],
            ['$sort' => ['_id' => 1]],
            ['$limit' => 30],
        ];

        $results = LinkTransaction::raw(function ($collection) use ($pipeline) {
            return $collection->aggregate($pipeline);
        });

        $labels = [];
        $revenue = [];
        $counts = [];

        foreach ($results as $row) {
            $labels[] = $row['_id'];
            $revenue[] = round($row['total_revenue'], 2);
            $counts[] = $row['count'];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'labels' => $labels,
                'revenue' => $revenue,
                'transaction_counts' => $counts,
            ],
        ]);
    }

    /**
     * GET /api/analytics/anomalies
     * List of AI-detected anomaly reports.
     */
    public function anomalies(Request $request): JsonResponse
    {
        $severity = $request->query('severity');
        $query = AnomalyReport::orderBy('flagged_at', 'desc');

        if ($severity) {
            $query->where('severity', $severity);
        }

        $anomalies = $query->limit(50)->get()->map(fn($a) => [
            'id' => (string) $a->_id,
            'source_collection' => $a->source_collection,
            'source_id' => $a->source_id,
            'anomaly_score' => round((float) $a->anomaly_score, 4),
            'severity' => $a->severity,
            'reason' => $a->reason,
            'field_values' => $a->field_values,
            'flagged_at' => $a->flagged_at?->toIso8601String(),
            'reviewed' => (bool) $a->reviewed,
        ]);

        return response()->json([
            'success' => true,
            'count' => $anomalies->count(),
            'data' => $anomalies,
        ]);
    }

    /**
     * GET /api/analytics/data-mart/sales
     * Star-schema style aggregated sales data mart.
     * Simulates a dimensional query across hubs, satellites, and the link.
     */
    public function salesMart(): JsonResponse
    {
        // Category breakdown (join-like aggregation in MongoDB)
        $categoryPipeline = [
            [
                '$lookup' => [
                    'from' => 'sat_product_details',
                    'localField' => 'hk_product',
                    'foreignField' => 'hk_product',
                    'as' => 'product',
                ],
            ],
            ['$unwind' => ['path' => '$product', 'preserveNullAndEmptyArrays' => true]],
            [
                '$group' => [
                    '_id' => '$product.category',
                    'total_revenue' => ['$sum' => '$amount'],
                    'total_orders' => ['$sum' => 1],
                    'avg_order_value' => ['$avg' => '$amount'],
                    'total_units' => ['$sum' => '$quantity'],
                ],
            ],
            ['$sort' => ['total_revenue' => -1]],
        ];

        $categoryResults = LinkTransaction::raw(function ($col) use ($categoryPipeline) {
            return $col->aggregate($categoryPipeline);
        });

        // Status breakdown
        $statusPipeline = [
            [
                '$group' => [
                    '_id' => '$status',
                    'count' => ['$sum' => 1],
                    'total' => ['$sum' => '$amount'],
                ],
            ],
            ['$sort' => ['count' => -1]],
        ];

        $statusResults = LinkTransaction::raw(function ($col) use ($statusPipeline) {
            return $col->aggregate($statusPipeline);
        });

        // Top customers by revenue
        $topCustomerPipeline = [
            [
                '$group' => [
                    '_id' => '$hk_customer',
                    'revenue' => ['$sum' => '$amount'],
                    'orders' => ['$sum' => 1],
                ],
            ],
            ['$sort' => ['revenue' => -1]],
            ['$limit' => 10],
            [
                '$lookup' => [
                    'from' => 'sat_customer_details',
                    'localField' => '_id',
                    'foreignField' => 'hk_customer',
                    'as' => 'customer',
                ],
            ],
            ['$unwind' => ['path' => '$customer', 'preserveNullAndEmptyArrays' => true]],
            [
                '$project' => [
                    'name' => ['$ifNull' => ['$customer.name', 'Unknown']],
                    'segment' => ['$ifNull' => ['$customer.segment', 'N/A']],
                    'revenue' => ['$round' => ['$revenue', 2]],
                    'orders' => 1,
                ],
            ],
        ];

        $topCustomers = LinkTransaction::raw(function ($col) use ($topCustomerPipeline) {
            return $col->aggregate($topCustomerPipeline);
        });

        return response()->json([
            'success' => true,
            'mart' => 'sales_mart',
            'schema' => 'star',
            'data' => [
                'by_category' => collect($categoryResults)->map(fn($r) => [
                    'category' => $r['_id'] ?? 'Unknown',
                    'total_revenue' => round($r['total_revenue'], 2),
                    'total_orders' => $r['total_orders'],
                    'avg_order_value' => round($r['avg_order_value'], 2),
                    'total_units' => $r['total_units'],
                ])->values(),
                'by_status' => collect($statusResults)->map(fn($r) => [
                    'status' => $r['_id'],
                    'count' => $r['count'],
                    'total' => round($r['total'], 2),
                ])->values(),
                'top_customers' => collect($topCustomers)->map(fn($r) => [
                    'name' => $r['name'],
                    'segment' => $r['segment'],
                    'revenue' => $r['revenue'],
                    'orders' => $r['orders'],
                ])->values(),
            ],
        ]);
    }
}
