<?php

namespace Modules\CRM\Repositories\Product;

use Illuminate\Bus\Batch as BusBatch;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CRM\Contracts\Product\DailyPriceRepositoryInterface;
use Modules\CRM\Exports\Product\DailyPriceExport;
use Modules\CRM\Imports\Product\DailyPricesImport;
use Modules\CRM\Jobs\Product\DailyPriceUpdateJob;
use Modules\CRM\Models\Product\DailyPrice\Batch;
use Modules\CRM\Models\Product\DailyPrice\Product;
use Throwable;

class DailyPriceRepository implements DailyPriceRepositoryInterface
{
    protected $batchModel;
    protected $productModel;

    public function __construct(Batch $batchModel, Product $productModel)
    {
        $this->batchModel = $batchModel;
        $this->productModel = $productModel;
    }

    public function paginate(array $filters = [])
    {
        return $this->batchModel
            ->with('approver', 'rejecter', 'creater')
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['product_sku'], 'ilike', "%{$filters['q']}%");
            })
            ->orderBy(data_get($filters, 'sort_by', 'created_at'), data_get($filters, 'order_by', 'desc'))
            ->paginate($filters['itemsPerPage'] ?? 10);
    }

    public function find(int $id)
    {
        return $this->batchModel->findOrFail($id);
    }

    public function import(UploadedFile $file, array $data)
    {
        $uploadedBy = auth()->id();

        $batch = $this->batchModel->create([
            'status' => 'in-progress',
            'price_date' => $data['price_date'],
            'created_by' => $uploadedBy
        ]);

        $importer = new DailyPricesImport($batch->id);
        Excel::import($importer, $file);

        return response()->json([
            'message' => 'Import completed',
            'report' => $importer->getReport()
        ]);
    }

    public function export($batchId, array $data)
    {
        $batch = $this->batchModel->with('products')->findOrFail($batchId);

        $fileName = "daily_prices_batch_{$batchId}_" . now()->format('Ymd_His') . ".xlsx";

        return Excel::download(new DailyPriceExport($batch), $fileName);
    }

    public function getFilters()
    {
        $data = $this->productModel
            ->select('category', 'sub_category', 'brand', 'new_city')
            ->get();

        return [
            'categories' => $data->pluck('category')->filter()->unique()->sort()->values(),
            'subCategories' => $data->pluck('sub_category')->filter()->unique()->sort()->values(),
            'brands' => $data->pluck('brand')->filter()->unique()->sort()->values(),
            'cities' => $data->pluck('new_city')->filter()->unique()->sort()->values(),
        ];
    }

    public function getProducts(array $filters = [])
    {
        return $this->productModel
            ->with('batch:id,price_date,status', 'product.uom:id,name')
            ->when(isset($filters['batch_id']), function ($query) use ($filters) {
                $query->where('batch_id', $filters['batch_id']);
            })
            ->when(isset($filters['q']), function ($query) use ($filters) {
                $query->whereAny(['product_sku', 'new_product_name', 'new_variant_name'], 'ilike', "%{$filters['q']}%");
            })
            ->when(isset($filters['category']), function ($query) use ($filters) {
                $query->where('category', 'ilike', "%{$filters['category']}%");
            })
            ->when(isset($filters['sub_category']), function ($query) use ($filters) {
                $query->where('sub_category', 'ilike', "%{$filters['sub_category']}%");
            })
            ->when(isset($filters['brand']), function ($query) use ($filters) {
                $query->where('brand', 'ilike', "%{$filters['brand']}%");
            })
            ->when(isset($filters['city']), function ($query) use ($filters) {
                $query->where('new_city', 'ilike', "%{$filters['city']}%");
            })
            ->when(isset($filters['product']), function ($query) use ($filters) {
                $query->where('product_id', $filters['product']);
            })
            ->when(isset($filters['city_id']), function ($query) use ($filters) {
                $query->where('city_id', $filters['city_id']);
            })
            ->when(isset($filters['batch_id']), function ($query) use ($filters) {
                $query->where('batch_id', $filters['batch_id']);
            })
            ->when(isset($filters['is_graph_product']) && $filters['is_graph_product'] === 'true', function ($query) {
                $query->where('is_graph_product', 'true');
            })
            ->when(isset($filters['price_date_start']) || isset($filters['price_date_end']), function ($query) use ($filters) {
                $query->whereHas('batch', function ($subQuery) use ($filters) {
                    if (isset($filters['price_date_start'])) {
                        $subQuery->whereDate('price_date', '>=', date('Y-m-d', strtotime($filters['price_date_start'])));
                    }
                    if (isset($filters['price_date_end'])) {
                        $subQuery->whereDate('price_date', '<=', date('Y-m-d', strtotime($filters['price_date_end'])));
                    }
                });
            })
            ->orderBy(data_get($filters, 'sort_by', 'created_at'), data_get($filters, 'order_by', 'desc'))
            ->paginate($filters['itemsPerPage'] ?? 10);
    }

    public function getBatchProducts($batchId, array $filters = [])
    {
        $filters['batch_id'] = $batchId;
        return $this->getProducts($filters);
    }

    public function approve($batchId)
    {
        $batch = $this->batchModel->findOrFail($batchId);
        $batch->update([
            'status' => 'processing',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        $jobs = [];

        $this->productModel
            ->where('batch_id', $batchId)
            ->chunkById(1000, function ($products) use (&$jobs, $batch) {
                $jobs[] = new DailyPriceUpdateJob($batch->refresh(), $products, auth()->id());
            });

        Bus::batch($jobs)
            ->then(function (BusBatch $batchJob) use ($batch) {
                $batch->update([
                    'status' => 'approved'
                ]);
            })
            ->catch(function (BusBatch $batchJob, Throwable $e) use ($batch) {
                $batch->update([
                    'status' => 'failed'
                ]);
            })
            ->dispatch();

        return $batch->refresh();
    }

    public function reject($batchId)
    {
        $batch = $this->batchModel->findOrFail($batchId);
        $batch->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
        ]);

        return $batch->refresh();
    }
}
