<?php

namespace Modules\CRM\Imports\Product;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Modules\CRM\Models\Product\GraphPrice;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GraphPricesImport implements
    ToCollection,
    WithHeadingRow,
    SkipsEmptyRows //,
// ShouldQueue
{
    protected $uploadedBy;

    protected $report = [
        'created' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function __construct($uploadedBy = null)
    {
        $this->uploadedBy = $uploadedBy;
    }

    public function collection(Collection $rows)
    {

        foreach ($rows as $index => $row) {
            try {

                // Read raw values from CSV row (no normalization / matching)
                $datetimeRaw = data_get($row, 'datetime');
                $categoryName = data_get($row, 'category');
                $productName = data_get($row, 'product');
                $brandName = data_get($row, 'brand');
                $market = data_get($row, 'market');
                $currency = data_get($row, 'currency');
                $priceRaw = data_get($row, 'price');
                $unitName = data_get($row, 'unit');

                $dt = null;
                try {
                    if (!empty($datetimeRaw)) {
                        $dt = Carbon::parse($datetimeRaw);
                    }
                } catch (Exception $e) {
                    $dt = null;
                }

                $price = (float) $priceRaw;

                $gp = new GraphPrice();
                $gp->datetime = $dt;
                $gp->datetime_raw = $datetimeRaw;
                $gp->market = $market;
                $gp->currency = $currency;
                $gp->price = $price;
                $gp->price_raw = $priceRaw;

                $gp->category_name = $categoryName;
                $gp->product_name = $productName;
                $gp->brand_name = $brandName;
                $gp->unit_name = $unitName;
                $gp->uploaded_by = $this->uploadedBy;

                $gp->save();

                $this->report['created']++;
            } catch (Exception $e) {
                $this->report['skipped']++;
                $this->report['errors'][] = "Row {$index}: exception " . $e->getMessage() . " Line: " . $e->getLine();
                continue;
            }
        }
    }

    public function getReport(): array
    {
        return $this->report;
    }
}
