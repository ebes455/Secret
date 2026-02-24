<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Sat_Product_Details — Data Vault 2.0 Satellite
 * Stores time-variant descriptive attributes of a Product.
 */
class SatProductDetail extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'sat_product_details';

    protected $fillable = [
        'hk_product',
        'name',
        'category',    // 'electronics', 'clothing', 'food', 'software'
        'price',
        'brand',
        'in_stock',
        'load_dts',
        'load_end_dts',
        'rec_src',
        'hash_diff',
    ];

    protected $casts = [
        'load_dts' => 'datetime',
        'load_end_dts' => 'datetime',
        'price' => 'float',
        'in_stock' => 'boolean',
    ];
}
