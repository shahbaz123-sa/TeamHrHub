<?php

namespace Modules\CRM\Imports\Woocommerce\Product;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Modules\CRM\Models\Product\Attribute;
use Codexshaper\WooCommerce\Facades\Attribute as WooAttribute;
use Modules\CRM\Jobs\Woocommerce\Product\AttributeWIthValuesImporterJob;
use Codexshaper\WooCommerce\Facades\Term as WooTerm;
use Modules\CRM\Models\Product\Brand;
use Modules\CRM\Models\Product\City;

class AttributesImporter
{
    public function execute()
    {
        $page = 1;
        $perPage = 25;
        $items = [];

        do {
            $items = retry(2, function () use ($page, $perPage) {
                return WooAttribute::all([
                    'page' => $page,
                    'per_page' => $perPage,
                    'order' => 'asc',
                    'orderby' => 'id',
                ]);
            }, 5);

            AttributeWIthValuesImporterJob::dispatch($items);

            $page++;
        } while (count($items) === $perPage);
    }

    public function handle($wooAttribute)
    {
        $attribute = Attribute::updateOrCreate([
            'wc_id' => $wooAttribute->id,
        ], [
            'name' => Str::replace([':', '"'], '', $wooAttribute->name),
            'slug' => $wooAttribute->slug,
        ]);

        $page = 1;
        $perPage = 50;
        $terms = [];

        do {
            $terms = WooTerm::all($attribute->wc_id, [
                'page' => $page,
                'per_page' => $perPage,
                'order' => 'asc',
                'orderby' => 'id',
            ]);

            if (filled($terms)) {
                $this->importAttributeTerms($terms, $attribute);
            }
            $page++;
        } while (count($terms) ===  $perPage);

        Attribute::whereIn('name', ['Brand', 'City'])->delete();
    }

    protected function importAttributeTerms(Collection $terms, Attribute $attribute)
    {
        $excludeIds = [];

        $terms->each(function ($wooTerm) use ($attribute, &$excludeIds) {

            $excludeIds[] = $wooTerm->id;

            if ($attribute->name === 'Brand') {
                Brand::firstOrCreate([
                    'name' => $wooTerm->name,
                ], [
                    'slug' => $wooTerm->slug,
                ]);

                return;
            }

            if ($attribute->name === 'City') {
                City::firstOrCreate([
                    'name' => $wooTerm->name,
                ], [
                    'slug' => $wooTerm->slug,
                ]);

                return;
            }

            $attribute->values()->updateOrCreate([
                'wc_id' => $wooTerm->id,
            ], [
                'name' => $wooTerm->name,
                'slug' => $wooTerm->slug,
                'description' => $wooTerm->description,
            ]);
        });

        return $excludeIds;
    }
}
