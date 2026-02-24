<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Hub_Customers — Data Vault 2.0 Hub
 * Stores core business keys for Customers.
 */
class HubCustomer extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'hub_customers';

    protected $fillable = [
        'hk_customer',   // SHA-256 hash key of customer_id
        'customer_id',   // Natural/business key
        'load_dts',      // Load date-time
        'rec_src',       // Record source (e.g., 'ecommerce_system')
    ];

    protected $casts = [
        'load_dts' => 'datetime',
    ];
}
