<?php

namespace App\Services;

use App\Models\Vault\AnomalyReport;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Carbon\Carbon;

class AnomalyDetectionService
{
    private string $pythonPath;
    private string $scriptPath;

    public function __construct()
    {
        // Find Python executable
        $this->pythonPath = $this->resolvePython();
        $this->scriptPath = base_path('scripts/anomaly_detector.py');
    }

    /**
     * Run the Python anomaly detector and store results in MongoDB.
     *
     * @return array{stored: int, errors: array}
     */
    public function run(): array
    {
        $host = config('database.connections.mongodb.host', '127.0.0.1');
        $port = config('database.connections.mongodb.port', 27017);
        $db = config('database.connections.mongodb.database', 'edwh_analytics');

        $process = new Process([
            $this->pythonPath,
            $this->scriptPath,
            '--host',
            $host,
            '--port',
            (string) $port,
            '--db',
            $db,
        ]);

        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput() ?: $process->getOutput();
            Log::error('AnomalyDetectionService: Python process failed', ['error' => $error]);
            throw new ProcessFailedException($process);
        }

        $output = trim($process->getOutput());

        if (empty($output)) {
            Log::warning('AnomalyDetectionService: Python script produced no output.');
            return ['stored' => 0, 'errors' => []];
        }

        $anomalies = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('AnomalyDetectionService: Invalid JSON from Python', ['output' => $output]);
            throw new \RuntimeException('Invalid JSON from anomaly detector: ' . json_last_error_msg());
        }

        // Check for Python-side error
        if (isset($anomalies['error'])) {
            Log::error('AnomalyDetectionService: Python reported error', $anomalies);
            throw new \RuntimeException($anomalies['error']);
        }

        $stored = 0;
        $errors = [];

        foreach ($anomalies as $anomaly) {
            try {
                AnomalyReport::updateOrCreate(
                    ['source_id' => $anomaly['source_id']],
                    [
                        'source_collection' => $anomaly['source_collection'],
                        'source_id' => $anomaly['source_id'],
                        'anomaly_score' => (float) ($anomaly['anomaly_score'] ?? 0),
                        'severity' => $anomaly['severity'] ?? 'medium',
                        'reason' => $anomaly['reason'] ?? 'Unknown',
                        'field_values' => $anomaly['field_values'] ?? [],
                        'flagged_at' => Carbon::parse($anomaly['flagged_at'] ?? now()),
                        'reviewed' => false,
                    ]
                );
                $stored++;
            } catch (\Throwable $e) {
                $errors[] = $e->getMessage();
                Log::warning('AnomalyDetectionService: Failed to store anomaly', [
                    'source_id' => $anomaly['source_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info("AnomalyDetectionService: Stored {$stored} anomalies.", ['total_detected' => count($anomalies)]);

        return ['stored' => $stored, 'errors' => $errors];
    }

    private function resolvePython(): string
    {
        foreach (['python', 'python3', 'python.exe', 'python3.exe'] as $candidate) {
            $test = new Process([$candidate, '--version']);
            $test->run();
            if ($test->isSuccessful()) {
                return $candidate;
            }
        }
        return 'python'; // fallback
    }
}
