<?php

namespace Modules\CRM\Jobs\Woocommerce\Product;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Modules\CRM\Imports\Woocommerce\Product\TagsImporter;

class TagsImporterJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Collection $tags;

    public function __construct($tags)
    {
        $this->tags = $tags;
        $this->onConnection('crm');
    }

    public function handle()
    {
        DB::transaction(function () {
            $this->tags->each(function ($wooTag) {
                app(TagsImporter::class)->handle($wooTag);
            });
        });
    }
}
