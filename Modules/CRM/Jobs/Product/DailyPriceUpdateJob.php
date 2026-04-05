<?php

namespace Modules\CRM\Jobs\Product;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\CRM\Models\Product\CityWisePrice;
use Modules\CRM\Models\Product\DailyPrice\Batch;
use Modules\CRM\Models\Product\GraphPrice;

class DailyPriceUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    private Batch $batch;
    private $batchProducts;
    private $uploadedBy;

    public function __construct(Batch $batch, $batchProducts, $uploadedBy)
    {
        $this->batch = $batch;
        $this->batchProducts = $batchProducts;
        $this->uploadedBy = $uploadedBy;
    }

    public function handle()
    {
        $this->batchProducts->each(function ($product) {

            if (
                !empty($product->comments)
                || empty($product->product_id)
                || empty($product->city_id)
                || empty($product->new_delivered_price)
            ) {
                return;
            }

            $isPriceExists = CityWisePrice::where('product_id', $product->product_id)
                ->where('city_id', $product->city_id)
                ->count();

            if ($isPriceExists > 1) {
                CityWisePrice::where('product_id', $product->product_id)
                    ->where('city_id', $product->city_id)
                    ->delete();
            }

            CityWisePrice::updateOrCreate(
                [
                    'product_id' => $product->product_id,
                    'city_id' => $product->city_id
                ],
                [
                    'price' => $product->new_delivered_price,
                ]
            );

            $comment = "Price updated successfully";

            if ($product->is_graph_product == 'true') {

                $price = $product->new_delivered_price;
                if (in_array(strtolower($product->graph_product), ['wheat', 'maize (corn)'])) {
                    $price = $product->new_delivered_price / 10;
                } elseif (in_array(strtolower($product->graph_product), ['rice'])) {
                    $price = $product->new_delivered_price / 25;
                }

                GraphPrice::updateOrCreate([
                    'datetime' => $this->batch->price_date,
                    'category_name' => $product->graph_category,
                    'product_name' => $product->graph_product,
                ], [
                    'datetime_raw' => $this->batch->price_date,
                    'market' => 'Pakistan',
                    'currency' => 'PKR',
                    'price' => $price,
                    'price_raw' => $price,
                    'unit_name' => $product->graph_product_unit,
                    'uploaded_by' => $this->uploadedBy,
                ]);

                $comment .= " and added in Graph list.";
            }

            $product->update([
                'comments' => $comment,
            ]);
        });
    }
}
