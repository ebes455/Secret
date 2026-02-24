<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;
use MongoDB\Client as MongoClient;

class IngestUnstructuredData extends Command
{
    protected $signature = 'etl:ingest-unstructured {--count=100}';
    protected $description = 'ETL: Ingest unstructured server logs and customer feedback into the Raw Data Lake';

    private array $logLevels = ['INFO', 'WARN', 'ERROR', 'DEBUG', 'CRITICAL'];
    private array $httpMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'];
    private array $endpoints = ['/api/orders', '/api/products', '/api/users', '/auth/login', '/api/payments', '/api/reports'];
    private array $services = ['order-service', 'product-catalog', 'auth-service', 'payment-gateway', 'analytics-engine'];
    private array $feedbackTypes = ['complaint', 'praise', 'suggestion', 'question', 'bug_report'];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $this->info("🌊 ETL: Starting unstructured Lakehouse ingestion ({$count} records)...");

        // Use the MongoDB PHP driver directly to avoid Eloquent BSON cast issues
        $mongoUri = sprintf(
            'mongodb://%s:%s',
            config('database.connections.mongodb.host', '127.0.0.1'),
            config('database.connections.mongodb.port', 27017)
        );
        $dbName = config('database.connections.mongodb.database', 'edwh_analytics');
        $client = new MongoClient($mongoUri);
        $collection = $client->selectDatabase($dbName)->selectCollection('raw_data_lake');

        $bar = $this->output->createProgressBar($count);
        $bar->setOverwrite(false);  // avoid STDERR issues on Windows non-interactive terminals
        $bar->start();

        $batch = [];
        $loaded = 0;

        for ($i = 0; $i < $count; $i++) {
            $type = $i % 3 === 0 ? 'customer_feedback' : 'server_log';
            $now = Carbon::now()->subMinutes(rand(0, 10080));

            $payload = $type === 'server_log'
                ? $this->generateServerLog()
                : $this->generateCustomerFeedback();

            $batch[] = [
                'type' => $type,
                'source' => $payload['source'] ?? 'unknown',
                'payload' => $payload,
                'ingested_at' => new \MongoDB\BSON\UTCDateTime($now->getTimestampMs()),
                'processed' => false,
            ];

            $loaded++;
            $bar->advance();

            // Batch insert every 50 records for efficiency
            if (count($batch) >= 50) {
                $collection->insertMany($batch);
                $batch = [];
            }
        }

        // Insert any remaining records
        if (!empty($batch)) {
            $collection->insertMany($batch);
        }

        try {
            $bar->finish();
        } catch (\Throwable) {
        }
        $this->newLine();
        $this->info("✅ Unstructured Lakehouse ETL complete. Ingested: {$loaded} raw records.");

        return Command::SUCCESS;
    }

    private function generateServerLog(): array
    {
        $level = $this->logLevels[array_rand($this->logLevels)];
        $method = $this->httpMethods[array_rand($this->httpMethods)];
        $path = $this->endpoints[array_rand($this->endpoints)];
        $service = $this->services[array_rand($this->services)];
        $status = in_array($level, ['ERROR', 'CRITICAL']) ? rand(400, 503) : rand(200, 201);
        $latency = rand(5, 5000); // ms

        return [
            'source' => $service,
            'timestamp' => Carbon::now()->subSeconds(rand(0, 86400))->toIso8601String(),
            'level' => $level,
            'message' => "[{$level}] {$method} {$path} → HTTP {$status} ({$latency}ms)",
            'http_method' => $method,
            'path' => $path,
            'status_code' => $status,
            'latency_ms' => $latency,
            'request_id' => strtoupper(Str::uuid()),
            'ip_address' => rand(10, 254) . '.' . rand(0, 255) . '.' . rand(0, 255) . '.' . rand(1, 254),
            'user_agent' => 'EnterpriseClient/' . rand(1, 5) . '.' . rand(0, 9),
            'server' => 'node-' . rand(1, 12) . '.prod.cluster',
            'trace_id' => strtolower(Str::random(32)),
            'extra' => [
                'memory_mb' => rand(128, 4096),
                'cpu_percent' => round(rand(1, 100) + (rand(0, 99) / 100), 2),
                'thread_count' => rand(4, 128),
            ],
        ];
    }

    private function generateCustomerFeedback(): array
    {
        $feedbackType = $this->feedbackTypes[array_rand($this->feedbackTypes)];
        $sentiments = ['positive', 'neutral', 'negative'];
        $sentiment = $feedbackType === 'praise' ? 'positive'
            : ($feedbackType === 'complaint' ? 'negative' : $sentiments[array_rand($sentiments)]);

        $messages = [
            'complaint' => ['Terrible experience with order delivery.', 'Product arrived damaged.', 'Support was unhelpful.'],
            'praise' => ['Amazing quality! Will buy again.', 'Fastest delivery ever!', 'Customer service was exceptional.'],
            'suggestion' => ['Please add dark mode.', 'Would love bulk order discounts.', 'API docs need improvement.'],
            'question' => ['How do I cancel my subscription?', 'Is this compatible with iOS 17?', 'What is your return policy?'],
            'bug_report' => ['Login button unresponsive on mobile.', 'Cart total miscalculated.', 'Export CSV produces empty file.'],
        ];

        $msgList = $messages[$feedbackType];

        return [
            'source' => 'customer-feedback-portal',
            'feedback_id' => 'FB-' . strtoupper(Str::random(8)),
            'customer_id' => 'CUST-' . str_pad(rand(1, 80), 5, '0', STR_PAD_LEFT),
            'feedback_type' => $feedbackType,
            'sentiment' => $sentiment,
            'message' => $msgList[array_rand($msgList)],
            'rating' => $sentiment === 'positive' ? rand(4, 5) : ($sentiment === 'negative' ? rand(1, 2) : 3),
            'product_id' => 'PROD-' . str_pad(rand(1, 50), 4, '0', STR_PAD_LEFT),
            'channel' => ['web', 'mobile', 'email', 'chat'][rand(0, 3)],
            'language' => ['en', 'de', 'fr', 'es'][rand(0, 3)],
            'submitted_at' => Carbon::now()->subDays(rand(0, 30))->toIso8601String(),
            'tags' => array_slice(['urgent', 'billing', 'shipping', 'quality', 'ui', 'performance'], 0, rand(1, 3)),
            'resolved' => false,
        ];
    }
}
