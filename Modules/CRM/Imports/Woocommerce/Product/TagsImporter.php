<?php

namespace Modules\CRM\Imports\Woocommerce\Product;

use Modules\CRM\Models\Product\Tag;
use Codexshaper\WooCommerce\Facades\Tag as WooTag;
use Modules\CRM\Jobs\Woocommerce\Product\TagsImporterJob;

class TagsImporter
{
    public function execute()
    {
        $page = 1;
        $perPage = 50;
        $items = [];

        do {
            $items = retry(2, function () use ($page, $perPage) {
                return WooTag::all([
                    'page' => $page,
                    'per_page' => $perPage,
                    'order' => 'asc',
                    'orderby' => 'id',
                ]);
            }, 5);

            TagsImporterJob::dispatch($items);

            $page++;
        } while (count($items) === $perPage);
    }

    public function handle($wooTag)
    {
        Tag::updateOrCreate([
            'wc_id' => $wooTag->id,
        ], [
            'name' => $wooTag->name,
            'slug' => $wooTag->slug,
            'description' => $wooTag->description,
        ]);
    }
}
