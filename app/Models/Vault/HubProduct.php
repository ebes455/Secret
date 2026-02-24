<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Hub_Products — Data Vault 2.0 Hub
 * Stores core business keys for Products.
 */
class HubProduct extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'hub_products';

    protected $fillable = [
        'hk_product',   // SHA-256 hash key of product_id
        'product_id',   // Natural/business key
        'load_dts',
        'rec_src',
    ];

    protected $casts = [
        'load_dts' => 'datetime',
    ];
}
