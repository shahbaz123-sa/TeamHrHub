<?php

namespace Modules\CRM\Helper;

use Illuminate\Support\Str;
use Modules\CRM\Models\Product;
use Gowelle\SkuGenerator\SkuGenerator;

class ProductHelper
{
    public static function generateSku(Product $product): string
    {
        $counter = 0;
        $actualSku = $sku = Str::substr(SkuGenerator::generateProductSku($product), 1);
        while (Product::where('sku', $sku)->exists()) {
            $sku = $actualSku . '-' . ++$counter;
        }

        if (Str::startsWith($sku, '-')) {
            return Str::substr($sku, 1);
        }

        return $sku;
    }
}
