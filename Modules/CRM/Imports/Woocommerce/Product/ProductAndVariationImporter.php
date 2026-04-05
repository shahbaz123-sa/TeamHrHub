<?php

namespace Modules\CRM\Imports\Woocommerce\Product;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Modules\CRM\Models\Product;
use Modules\CRM\Models\Product\Tag;
use Modules\CRM\Models\Product\City;
use Modules\CRM\Models\Product\Brand;
use Modules\CRM\Helper\ProductHelper;
use Modules\CRM\Models\Product\Category;
use Modules\CRM\Traits\File\FileManager;
use Modules\CRM\Models\Product\Attribute;
use Codexshaper\WooCommerce\Facades\Variation;
use Modules\CRM\Models\Product\UnitOfMeasurement;
use Codexshaper\WooCommerce\Facades\Product as WooProduct;
use Modules\CRM\Models\Product\Pivot\AttributeValueProduct;
use Modules\CRM\Jobs\Woocommerce\Product\VariationsImporterJob;
use Modules\CRM\Jobs\Woocommerce\ProductWithVariationImporterJob;
use Modules\CRM\Models\Product\Attribute\Value as AttributeValue;

class ProductAndVariationImporter
{
    use FileManager;

    private $products;
    private $cities;
    private $brands;
    private $attributes;
    private $values;

    public function __construct()
    {
        $this->products = Product::whereIn('type', ['simple', 'variable'])->get();
        $this->cities = City::all()->keyBy('name');
        $this->brands = Brand::all()->keyBy('name');
        $this->attributes = Attribute::all()->keyBy('name');
        $this->values = AttributeValue::all()->keyBy('name');
    }

    public function execute()
    {
        $page = 1;
        $perPage = 5;
        $items = [];

        do {
            $items = retry(2, function () use ($page, $perPage) {
                return WooProduct::all([
                    'page' => $page,
                    'per_page' => $perPage,
                    'order' => 'asc',
                    'orderby' => 'id',
                    'status' => 'publish',
                    'include_types' => 'simple,variable',
                ]);
            }, 5);

            ProductWithVariationImporterJob::dispatch($items);

            $page++;
        } while (count($items) > 0 && count($items) === $perPage);
    }

    public function handle($wooProduct, $wooParentProduct = null)
    {
        $product = $this->createUpdateProduct($wooProduct);
        $this->products->add($product);

        // $this->importProductImages($product, $wooProduct, $wooParentProduct);

        // $this->importProductCategories($product, $wooProduct);
        // $this->importProductTags($product, $wooProduct);

        $this->importProductAttributeValues($product, $wooProduct);

        if (!empty($wooProduct->variations)) {
            $this->importProductVariations($wooProduct);
        }
    }

    protected function importProductVariations($wooProduct)
    {
        $page = 1;
        $perPage = 5;
        $wooVariations = [];
        $existingsVariationIds = Product::variationsOf($wooProduct->id)->pluck('wc_id')->toArray();

        do {
            $wooVariations = retry(2, function () use ($wooProduct, &$page, &$perPage, &$existingsVariationIds) {
                return Variation::all($wooProduct->id, [
                    'page' => $page,
                    'per_page' => $perPage,
                    'order' => 'asc',
                    'orderby' => 'id',
                    'status' => 'publish',
                    'parent' => [$wooProduct->id],
                ]);
            }, 5);

            VariationsImporterJob::dispatch($wooVariations, $wooProduct);
            $existingsVariationIds += collect($wooVariations)->pluck('id')->toArray();

            $page++;
        } while (count($wooVariations) ===  $perPage);
    }

    protected function createUpdateProduct($wooProduct): Product
    {
        $parentId = data_get($wooProduct, 'parent_id', null);
        if (!empty($parentId) && $parentId > 0) {
            $parentId = $this->products->firstWhere('wc_id', $parentId)->id ?? null;
        }

        $metaData = data_get($wooProduct, 'meta_data', []);
        $metaData = collect($metaData)
            ->filter(function ($item) {
                $item = (array) $item;
                return in_array($item['key'], ['min_quantity', 'max_quantity', 'elex_ppct_custom_fields_suffix']);
            })
            ->pluck('value', 'key');

        $minQuantity = $metaData['min_quantity'] ?? 0;
        $maxQuantity = $metaData['max_quantity'] ?? 0;
        $uom = $metaData['elex_ppct_custom_fields_suffix'] ?? null;

        if ($uom) {
            $uom = $this->getUomId($uom);
        }

        $parentId = !empty($parentId) && $parentId > 0 ? $parentId : null;
        return Product::updateOrCreate(
            ['wc_id' => $wooProduct->id],
            [
                'parent_id'         => $parentId,
                'wc_slug'           => data_get($wooProduct, 'slug'),
                'wc_sku'            => $wooProduct->sku,
                'sku'               => ProductHelper::generateSku(new Product()),
                'name'              => $wooProduct->name,
                'type'              => $wooProduct->type,
                'status'            => $wooProduct->status,
                'stock_status'      => $wooProduct->stock_status,
                'price'             => (float) $wooProduct->price,
                'regular_price'     => (float) $wooProduct->regular_price,
                'sale_price'        => (float) $wooProduct->sale_price,
                'quantity'          => (int) $wooProduct->stock_quantity,
                'min_quantity'      => (int) $minQuantity,
                'max_quantity'      => (int) $maxQuantity,
                'short_description' => data_get($wooProduct, 'short_description'),
                'description'       => $wooProduct->description,
                'uom_id'            => $uom,
            ]
        );
    }

    protected function importProductImages(Product $product, $wooProduct, $wooParentProduct = null)
    {
        $slug = Str::slug($wooProduct->slug ?? $product->sku ?? $product->name);

        $imagesFolder = "product/images/$slug";
        if ($wooParentProduct) {
            $parentProductSlug = Str::slug($wooParentProduct->slug ?? $wooParentProduct->sku ?? $wooParentProduct->name);
            $imagesFolder = "product/images/$parentProductSlug/variation/$slug";
        }

        $images = $wooProduct->images ?? $wooProduct->image ?? [];
        $images = is_array($images) ? $images : [$images];

        collect($images)->filter(fn($img) => !empty($img->src))->each(function ($wooImage) use ($product, $imagesFolder) {
            $product->images()->updateOrCreate(
                ['wc_id' => $wooImage->id],
                ['src' => $this->fetchFromUrlAndUploadImage($wooImage->src, $imagesFolder)]
            );
        });
    }

    protected function importProductAttributeValues(Product &$product, $wooProduct)
    {
        if (empty($wooProduct->attributes)) {
            return;
        }

        foreach ($wooProduct->attributes as $wooAttribute) {

            $option = $wooAttribute->option ?? $wooAttribute->options ?? null;
            $option = is_array($option) ? Arr::first($option) : $option;

            if (empty($option)) {
                continue;
            }

            $optionName = trim(Str::replace([':', '"'], '', $option));
            $wooAttributeName = trim(Str::replace([':', '"'], '', $wooAttribute->name));

            if (in_array($wooProduct->type, ['simple', 'variable']) && $wooAttributeName === 'Brand') {
                $brand = $this->brands->get($optionName);
                if ($brand) {
                    $product->brand_id = $brand->id;
                    $product->save();
                }
            }

            if (in_array($wooProduct->type, ['simple', 'variation']) && $wooAttributeName === 'City') {
                $city = $this->cities->get($optionName);
                if ($city) {
                    $product->prices()->updateOrCreate(
                        ['city_id' => $city->id],
                        ['price' => (float) $product->price]
                    );
                }
            }

            if ($wooProduct->type === 'variation' && !in_array($wooAttributeName, ['Brand', 'City'])) {

                $attribute = $this->attributes->get($wooAttributeName);
                if (!$attribute) {
                    $attribute = Attribute::firstOrCreate([
                        'name' => $wooAttributeName,
                    ], [
                        'slug' => Str::slug($wooAttributeName),
                    ]);
                }

                $value = $this->values->get($optionName);
                if (!$value) {
                    $attribute->values()->firstOrCreate([
                        'name' => $optionName,
                    ], [
                        'slug' => Str::slug($optionName),
                        'description' => "",
                    ]);
                }

                if ($value) {
                    AttributeValueProduct::updateOrCreate([
                        'product_id'          => $product->id,
                        'attribute_id'        => $attribute->id,
                        'attribute_value_id'  => $value->id,
                    ]);
                }
            }
        }
    }

    protected function importProductCategories(Product $product, $wooProduct)
    {
        if (empty($wooProduct->categories)) {
            return;
        }

        $categoryIds = Category::whereIn('wc_id', collect($wooProduct->categories)->pluck('id'))->select('id')->pluck('id');
        $product->categories()->sync($categoryIds);
    }

    protected function importProductTags(Product $product, $wooProduct)
    {
        if (empty($wooProduct->tags)) {
            return;
        }

        $tagIds = Tag::whereIn('wc_id', collect($wooProduct->tags)->pluck('id'))->select('id')->pluck('id');
        $product->tags()->sync($tagIds);
    }

    public function getUomId($uomName)
    {
        $uomName = trim($uomName);
        if (empty($uomName)) {
            return null;
        }

        if (Str::startsWith($uomName, '/')) {
            $uomName = Str::substr($uomName, 1);
        }

        $uom = UnitOfMeasurement::firstOrCreate(
            ['slug' => Str::slug($uomName)],
            ['name' => $uomName],
        );

        return $uom->id;
    }
}
