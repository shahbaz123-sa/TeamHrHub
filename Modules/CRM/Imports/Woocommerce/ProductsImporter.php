<?php

namespace Modules\CRM\Imports\Woocommerce;

use Illuminate\Support\Facades\Bus;

class ProductsImporter
{
    public function handle()
    {
        Bus::chain([
            // fn() => $this->import(\Modules\CRM\Imports\Woocommerce\Product\TagsImporter::class),
            // fn() => $this->import(\Modules\CRM\Imports\Woocommerce\Product\CategoriesImporter::class),
            // fn() => $this->import(\Modules\CRM\Imports\Woocommerce\Product\AttributesImporter::class),
            fn() => $this->import(\Modules\CRM\Imports\Woocommerce\Product\ProductAndVariationImporter::class),
        ])->onConnection('crm')->dispatch();
    }

    protected function import($importer)
    {
        app($importer)->execute();
    }
}
