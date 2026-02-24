<?php

namespace App\Console\Commands;

use App\Models\Vault\HubCustomer;
use App\Models\Vault\HubProduct;
use App\Models\Vault\LinkTransaction;
use App\Models\Vault\SatCustomerDetail;
use App\Models\Vault\SatProductDetail;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Carbon\Carbon;

class IngestStructuredData extends Command
{
    protected $signature = 'etl:ingest-structured {--count=200}';
    protected $description = 'ETL: Ingest structured e-commerce transaction data into Data Vault 2.0 collections';

    private array $categories = ['Electronics', 'Clothing', 'Food', 'Software', 'Books', 'Sports'];
    private array $segments = ['premium', 'standard', 'trial'];
    private array $statuses = ['completed', 'pending', 'refunded'];
    private array $countries = ['US', 'UK', 'DE', 'FR', 'PK', 'IN', 'AU', 'CA'];
    private array $brands = ['Nexus', 'CoreTech', 'AlphaWave', 'Pinnacle', 'Vertex', 'Orion'];

    public function handle(): int
    {
        $count = (int) $this->option('count');
        $loaded = 0;
        $dupes = 0;

        $this->info("🚀 ETL: Starting structured ingestion ({$count} records)...");
        $bar = $this->output->createProgressBar($count);
        $bar->setOverwrite(false);  // avoid STDERR issues on Windows non-interactive terminals
        $bar->start();

        $recSrc = 'ecommerce_system_v2';

        for ($i = 0; $i < $count; $i++) {
            // --- EXTRACT (generate dummy enterprise data) ---
            $customerId = 'CUST-' . str_pad(rand(1, 80), 5, '0', STR_PAD_LEFT);
            $productId = 'PROD-' . str_pad(rand(1, 50), 4, '0', STR_PAD_LEFT);
            $txId = 'TX-' . strtoupper(Str::random(10));
            $amount = round(rand(10, 9999) + (rand(0, 99) / 100), 2);
            $qty = rand(1, 20);
            $status = $this->statuses[array_rand($this->statuses)];
            $category = $this->categories[array_rand($this->categories)];
            $brand = $this->brands[array_rand($this->brands)];
            $country = $this->countries[array_rand($this->countries)];
            $segment = $this->segments[array_rand($this->segments)];
            $loadDts = Carbon::now()->subMinutes(rand(0, 43200)); // last 30 days

            // --- TRANSFORM (compute hash keys & hash diffs) ---
            $hkCustomer = hash('sha256', strtolower($customerId));
            $hkProduct = hash('sha256', strtolower($productId));
            $hkTx = hash('sha256', strtolower($txId));

            $customerName = fake()->name();
            $customerEmail = 'user_' . rand(1000, 9999) . '@enterprise.com';
            $productName = $brand . ' ' . $category . ' ' . rand(100, 999);
            $price = round(rand(5, 2000) + (rand(0, 99) / 100), 2);

            $hashDiffCustomer = hash('sha256', $customerName . $customerEmail . $country . $segment);
            $hashDiffProduct = hash('sha256', $productName . $category . $price . $brand);

            // --- LOAD Hub_Customers (upsert = deduplication) ---
            $existingHub = HubCustomer::where('hk_customer', $hkCustomer)->first();
            if (!$existingHub) {
                HubCustomer::create([
                    'hk_customer' => $hkCustomer,
                    'customer_id' => $customerId,
                    'load_dts' => $loadDts,
                    'rec_src' => $recSrc,
                ]);
            } else {
                $dupes++;
            }

            // --- LOAD Sat_Customer_Details (insert if hash_diff changed) ---
            $existingSat = SatCustomerDetail::where('hk_customer', $hkCustomer)
                ->whereNull('load_end_dts')
                ->first();

            if (!$existingSat || $existingSat->hash_diff !== $hashDiffCustomer) {
                if ($existingSat) {
                    $existingSat->load_end_dts = $loadDts;
                    $existingSat->save();
                }
                SatCustomerDetail::create([
                    'hk_customer' => $hkCustomer,
                    'name' => $customerName,
                    'email' => $customerEmail,
                    'phone' => '+1-' . rand(200, 999) . '-' . rand(100, 999) . '-' . rand(1000, 9999),
                    'country' => $country,
                    'segment' => $segment,
                    'load_dts' => $loadDts,
                    'load_end_dts' => null,
                    'rec_src' => $recSrc,
                    'hash_diff' => $hashDiffCustomer,
                ]);
            }

            // --- LOAD Hub_Products ---
            $existingProd = HubProduct::where('hk_product', $hkProduct)->first();
            if (!$existingProd) {
                HubProduct::create([
                    'hk_product' => $hkProduct,
                    'product_id' => $productId,
                    'load_dts' => $loadDts,
                    'rec_src' => $recSrc,
                ]);
            }

            // --- LOAD Sat_Product_Details ---
            $existingSatProd = SatProductDetail::where('hk_product', $hkProduct)
                ->whereNull('load_end_dts')
                ->first();
            if (!$existingSatProd || $existingSatProd->hash_diff !== $hashDiffProduct) {
                if ($existingSatProd) {
                    $existingSatProd->load_end_dts = $loadDts;
                    $existingSatProd->save();
                }
                SatProductDetail::create([
                    'hk_product' => $hkProduct,
                    'name' => $productName,
                    'category' => $category,
                    'price' => $price,
                    'brand' => $brand,
                    'in_stock' => (bool) rand(0, 1),
                    'load_dts' => $loadDts,
                    'load_end_dts' => null,
                    'rec_src' => $recSrc,
                    'hash_diff' => $hashDiffProduct,
                ]);
            }

            // --- LOAD Link_Transactions ---
            $existingTx = LinkTransaction::where('hk_transaction', $hkTx)->first();
            if (!$existingTx) {
                LinkTransaction::create([
                    'hk_transaction' => $hkTx,
                    'hk_customer' => $hkCustomer,
                    'hk_product' => $hkProduct,
                    'transaction_id' => $txId,
                    'amount' => $amount,
                    'quantity' => $qty,
                    'status' => $status,
                    'load_dts' => $loadDts,
                    'rec_src' => $recSrc,
                ]);
                $loaded++;
            }

            $bar->advance();
        }

        try {
            $bar->finish();
        } catch (\Throwable) {
        }
        $this->newLine();
        $this->info("✅ Structured ETL complete. Loaded: {$loaded} | Deduplicated (skipped): {$dupes}");

        return Command::SUCCESS;
    }
}
