<?php

namespace Modules\CRM\Imports\Woocommerce\Product;

use Modules\CRM\Models\Product\Category;
use Modules\CRM\Traits\File\FileManager;
use Codexshaper\WooCommerce\Facades\Category as WooCategory;
use Modules\CRM\Jobs\Woocommerce\Product\CategoriesImporterJob;

class CategoriesImporter
{
    use FileManager;

    public function execute()
    {
        $page = 1;
        $perPage = 50;
        $items = [];

        do {
            $items = retry(2, function () use ($page, $perPage) {
                return WooCategory::all([
                    'page' => $page,
                    'per_page' => $perPage,
                    'order' => 'asc',
                    'orderby' => 'id',
                ]);
            }, 5);

            CategoriesImporterJob::dispatch($items);

            $page++;
        } while (count($items) === $perPage);
    }

    public function handle($wooCategory, &$existingCategories)
    {
        $parentId = null;
        if (!empty($wooCategory->parent)) {
            $parentId = $existingCategories->firstWhere('wc_id', $wooCategory->parent)->id ?? null;
        }

        $image = null;
        if (!empty($wooCategory->image->src)) {
            $image = $this->fetchFromUrlAndUploadImage(
                $wooCategory->image->src,
                "product/category/$wooCategory->slug"
            );
        }

        $category = Category::updateOrCreate([
            'wc_id' => $wooCategory->id,
            'slug' => $wooCategory->slug,
        ], [
            'name' => $wooCategory->name,
            'description' => $wooCategory->description,
            'parent_id' => $parentId,
            'image' => $image,
        ]);

        $existingCategories->add($category);
    }
}
