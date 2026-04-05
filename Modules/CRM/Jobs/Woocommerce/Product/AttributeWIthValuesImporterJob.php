<?php

namespace Modules\CRM\Jobs\Woocommerce\Product;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\CRM\Imports\Woocommerce\Product\AttributesImporter;

class AttributeWIthValuesImporterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $attributes;

    public function __construct($attributes)
    {
        $this->attributes = $attributes;
        $this->onConnection('crm');
    }

    public function handle()
    {
        DB::transaction(function () {
            $this->attributes->each(function ($wooAttribute) {
                app(AttributesImporter::class)->handle($wooAttribute);
            });
        });
    }
}
