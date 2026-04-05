<?php

namespace Modules\CRM\Jobs\Woocommerce\Product;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Modules\CRM\Models\Product\Category;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\CRM\Imports\Woocommerce\Product\CategoriesImporter;

class CategoriesImporterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $categories;

    public function __construct($categories)
    {
        $this->categories = $categories;
        $this->onConnection('crm');
    }

    public function handle()
    {
        DB::transaction(function () {
            $existingCategories = Category::all();
            $this->categories->each(function ($wooCategory) use (&$existingCategories) {
                app(CategoriesImporter::class)->handle($wooCategory, $existingCategories);
            });
        });
    }
}
