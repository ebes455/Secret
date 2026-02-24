<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Raw_Data_Lake — Lakehouse unstructured store
 * Stores raw, semi-structured JSON payloads as-is.
 */
class RawDataLake extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'raw_data_lake';

    protected $fillable = [
        'type',        // 'server_log', 'customer_feedback', 'api_event'
        'source',      // System origin
        'payload',     // Raw JSON document (nested)
        'ingested_at',
        'processed',   // boolean — whether ETL has processed this record
    ];

    protected $casts = [
        'ingested_at' => 'datetime',
        'processed' => 'boolean',
        // NOTE: 'payload' cast omitted — MongoDB BSON stores nested documents natively
    ];
}
