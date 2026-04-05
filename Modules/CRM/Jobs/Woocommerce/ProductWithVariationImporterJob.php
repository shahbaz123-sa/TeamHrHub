<?php

namespace Modules\CRM\Jobs\Woocommerce;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Imports\Woocommerce\Product\ProductAndVariationImporter;

class ProductWithVariationImporterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $products;

    public function __construct($products)
    {
        $this->products = $products;
        $this->onConnection('crm');
    }

    public function handle()
    {
        DB::transaction(function () {
            $this->products->each(function ($wooProduct) {
                app(ProductAndVariationImporter::class)->handle($wooProduct);
            });
        });
    }
}
