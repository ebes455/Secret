<?php

namespace App\Models\Vault;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Link_Transactions — Data Vault 2.0 Link
 * Represents the transaction relationship between Customer and Product.
 */
class LinkTransaction extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'link_transactions';

    protected $fillable = [
        'hk_transaction', // Hash key of the transaction
        'hk_customer',    // FK to hub_customers
        'hk_product',     // FK to hub_products
        'transaction_id', // Business key
        'amount',         // Transaction amount
        'quantity',       // Units purchased
        'status',         // 'completed', 'pending', 'refunded'
        'load_dts',
        'rec_src',
    ];

    protected $casts = [
        'load_dts' => 'datetime',
        'amount' => 'float',
        'quantity' => 'integer',
    ];
}
