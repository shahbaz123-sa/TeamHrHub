<?php

namespace Modules\CRM\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class WooProductImportPostCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'woo-importer:post-actions';

    /**
     * The console command description.
     *
     * @var string|null
     */
    protected $description = 'Actions perform after products import';

    public function handle()
    {
        DB::connection('crm')->statement('update product_city_wise_prices pcwp set product_id = p.parent_id from  products p where p.id = pcwp.product_id and p.type =\'variation\' and trim(replace(name, \'"\', \'\')) in (select name from product_cities);');
        DB::connection('crm')->statement('update products mp set type=\'simple\' from products p where p.parent_id = mp.id and p.type =\'variation\' and trim(replace(p.name, \'"\', \'\')) in (select name from product_cities);');
        DB::connection('crm')->statement('delete from product_category_map where product_id in (select id from products p where p.type =\'variation\' and trim(replace(name, \'"\', \'\')) in (select name from product_cities));');
        DB::connection('crm')->statement('delete from product_tag_map where product_id in (select id from products p where p.type =\'variation\' and trim(replace(name, \'"\', \'\')) in (select name from product_cities));');
        DB::connection('crm')->statement('delete from product_images where product_id in (select id from products p where p.type =\'variation\' and trim(replace(name, \'"\', \'\')) in (select name from product_cities));');
        DB::connection('crm')->statement('delete from products p where p.type =\'variation\' and trim(replace(name, \'"\', \'\')) in (select name from product_cities);');

        $this->info("Products post import actions ran successfully.");
    }
}
