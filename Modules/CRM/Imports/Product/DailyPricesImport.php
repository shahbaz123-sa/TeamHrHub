<?php

namespace Modules\CRM\Imports\Product;

use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Events\AfterImport;
use Modules\CRM\Models\Product as CrmProduct;
use Modules\CRM\Models\Product\City;
use Modules\CRM\Models\Product\DailyPrice\Batch;
use Modules\CRM\Models\Product\DailyPrice\Product;

class DailyPricesImport implements
    ToCollection,
    WithChunkReading,
    WithHeadingRow,
    SkipsEmptyRows,
    ShouldQueue,
    WithEvents
{
    protected $batchId;
    protected $products;
    protected $cities;

    protected $report = [
        'created' => 0,
        'skipped' => 0,
        'errors' => []
    ];

    public function __construct($batchId = null)
    {
        $this->batchId = $batchId;
        $this->products = CrmProduct::select('id', 'sku')->get()->keyBy('sku');
        $this->cities = City::select('id', 'name')->get()->keyBy('name');
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {
                Batch::where('id', $this->batchId)
                    ->update(['status' => 'pending']);
            },
        ];
    }

    public function collection(Collection $rows)
    {
        /** @var \Illuminate\Support\Collection $row */
        foreach ($rows as $index => $row) {
            try {

                $comments = '';

                $sku = data_get($row, 'sku');
                $crmProduct = $this->products->get($sku);
                if (!$crmProduct) {
                    $this->report['errors'][] = "Row {$index}: Product with SKU '{$sku}' not found.";
                    $comments .= "Row {$index}: Product with SKU '{$sku}' not found. ";
                }

                $newCity = data_get($row, 'new_city');
                $city = $this->cities->get($newCity);
                if (!$city) {
                    $this->report['errors'][] = "Row {$index}: City '{$newCity}'   not found.";
                    $comments .= "Row {$index}: City '{$newCity}' not found. ";
                }

                $isGraphProduct = data_get($row, 'is_graph_product', false);
                $isGraphProduct = (is_string($isGraphProduct) && strtolower($isGraphProduct) === 'true') || $isGraphProduct === true;

                if (
                    $isGraphProduct
                    && (
                        empty(data_get($row, 'graph_category'))
                        || empty(data_get($row, 'graph_product'))
                        || empty(data_get($row, 'graph_product_unit'))
                    )
                ) {
                    $comments .= " Graph Product details are incomplete.";
                }

                $payload = [
                    'batch_id' => $this->batchId,
                    'product_id' => optional($crmProduct)->id,
                    'city_id' => optional($city)->id,
                    'product_sku' => $sku,
                    'comments' => $comments
                ] + $row->toArray();

                $payload['is_graph_product'] = $isGraphProduct ? 'true' : 'false';

                Product::create($payload);

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
