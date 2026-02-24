<?php

namespace App\Console\Commands;

use App\Services\AnomalyDetectionService;
use Illuminate\Console\Command;

class DetectAnomalies extends Command
{
    protected $signature = 'analytics:detect-anomalies';
    protected $description = 'Run the Python AI anomaly detection engine and store results';

    public function handle(AnomalyDetectionService $service): int
    {
        $this->info('🤖 EDWH AI: Running anomaly detection engine...');

        try {
            $result = $service->run();

            $this->info("✅ Anomaly detection complete!");
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Anomalies Stored', $result['stored']],
                    ['Errors', count($result['errors'])],
                ]
            );

            if (!empty($result['errors'])) {
                $this->warn('Some anomalies failed to store:');
                foreach ($result['errors'] as $err) {
                    $this->line("  - {$err}");
                }
            }

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Detection failed: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
