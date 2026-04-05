<?php

namespace Modules\CRM\Console\Commands;

use Illuminate\Console\Command;

class WooProductImportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo-importer:products';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Import products from woocommerce';

    public function handle()
    {
        app(\Modules\CRM\Imports\Woocommerce\ProductsImporter::class)->handle();
        $this->info("Products added to queue for importing.");
    }
}
