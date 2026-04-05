<?php

namespace Modules\CRM\Jobs\Woocommerce\Product;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\CRM\Imports\Woocommerce\Product\ProductAndVariationImporter;

class VariationsImporterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $variations;
    private object $parentProduct;

    public function __construct($variations, $parentProduct)
    {
        $this->variations = $variations;
        $this->parentProduct = $parentProduct;
        $this->onConnection('crm');
    }

    public function handle()
    {
        $parentProduct = $this->parentProduct;

        DB::transaction(function () use ($parentProduct) {
            $this->variations->each(function ($wooProduct) use ($parentProduct) {
                app(ProductAndVariationImporter::class)->handle($wooProduct, $parentProduct);
            });
        });
    }
}
