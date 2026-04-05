<?php

namespace Modules\CRM\Console\Commands;

use Illuminate\Console\Command;
use Modules\CRM\Models\Product;
use Modules\CRM\Helper\ProductHelper;

class RegenerateProductSkuCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'product-sku:regenerate';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Regenerate product SKUs for all products in the CRM module';

    public function handle()
    {
        Product::each(function (Product $product) {
            $product->sku = ProductHelper::generateSku($product);
            $product->save();
        });

        $this->info("Products sku generated.");
    }
}
