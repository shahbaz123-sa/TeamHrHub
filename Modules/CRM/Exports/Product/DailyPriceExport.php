<?php

namespace Modules\CRM\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Modules\CRM\Models\Product\CityWisePrice;
use Modules\CRM\Models\Product\DailyPrice\Batch;
use Modules\CRM\Models\Product\DailyPrice\Product;

class DailyPriceExport implements FromCollection, WithHeadings
{
    private Batch $batch;
    private $cityWisePrice;

    public function __construct(Batch $batch)
    {
        $this->batch = $batch;
        $this->cityWisePrice = CityWisePrice::with('product.uom:id,name')
            ->whereIn(
                'product_id',
                Product::where('batch_id', $this->batch->id)->select('product_id')
            )
            ->latest('id')
            ->get()
            ->keyBy(function ($item) {
                return $item->product_id . '-' . $item->city_id;
            });
    }

    public function headings(): array
    {
        return [
            'SKU',
            'Category',
            'Sub Category',
            'Brand',
            'Old Product Name',
            'New Product Name',
            'New Variant Name',
            'Old City',
            'City',
            'Province',
            'New City',
            'Vendor Name',
            'Vendor Price',
            'Zarea Price',
            'Old Delivered Price',
            'New Delivered Price',
            'Unit of Measurement',
            'Min Order Qty',
            'Price Bulk Qty',
            'Zarea Price On Bulk',
            'Comments',
            'Is Graph Product',
            'Graph Category',
            'Graph Product',
            'Graph Product Unit',
        ];
    }

    public function collection()
    {
        $products = optional($this->batch)
            ->products()
            ->orderBy('category')
            ->orderBy('sub_category')
            ->orderBy('new_product_name')
            ->get()
            ->map(function ($item) {

                $cityWisePrice = $this->cityWisePrice->get($item->product_id . '-' . $item->city_id);
                $productUom = data_get($cityWisePrice, 'product.uom.name');

                return [
                    $item->product_sku,
                    $item->category,
                    $item->sub_category,
                    $item->brand,
                    $item->old_product_name,
                    $item->new_product_name,
                    $item->new_variant_name,
                    $item->old_city,
                    $item->city,
                    $item->province,
                    $item->new_city,
                    $item->vendor_name,
                    $item->vendor_price,
                    $item->zarea_price,
                    $item->old_delivered_price,
                    $cityWisePrice->price ?? $item->new_delivered_price,
                    $productUom,
                    $item->min_order_qty,
                    $item->price_bulk_qty,
                    $item->zarea_price_on_bulk,
                    $item->comments,
                    $item->is_graph_product,
                    $item->graph_category,
                    $item->graph_product,
                    $item->graph_product_unit,
                ];
            });

        return collect($products);
    }
}
