<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Sat_Customer_Details — Data Vault 2.0 Satellite
 * Stores time-variant descriptive attributes of a Customer.
 */
class SatCustomerDetail extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'sat_customer_details';

    protected $fillable = [
        'hk_customer',
        'name',
        'email',
        'phone',
        'country',
        'segment',        // 'premium', 'standard', 'trial'
        'load_dts',
        'load_end_dts',   // NULL = current record
        'rec_src',
        'hash_diff',      // Hash of all descriptive fields for change detection
    ];

    protected $casts = [
        'load_dts' => 'datetime',
        'load_end_dts' => 'datetime',
    ];
}
