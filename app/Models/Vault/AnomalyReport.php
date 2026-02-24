<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Anomaly_Reports — AI flagged records
 * Stores outputs from the Python anomaly detection engine.
 */
class AnomalyReport extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'anomaly_reports';

    protected $fillable = [
        'source_collection', // Which collection the anomaly came from
        'source_id',         // _id of the original record
        'anomaly_score',     // Isolation Forest anomaly score
        'reason',            // Human-readable reason
        'field_values',      // Key field values of the anomalous record
        'severity',          // 'low', 'medium', 'high'
        'flagged_at',
        'reviewed',          // boolean — reviewed by an analyst
    ];

    protected $casts = [
        'flagged_at' => 'datetime',
        'field_values' => 'array',
        'reviewed' => 'boolean',
        'anomaly_score' => 'float',
    ];
}
