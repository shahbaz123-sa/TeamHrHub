<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\CRM\Contracts\Product\DailyPriceRepositoryInterface;
use Modules\CRM\Http\Resources\Product\DailyPrice\BatchResource;
use Modules\CRM\Http\Resources\Product\DailyPrice\ProductResource;

class DailyPriceController extends Controller
{
    protected $repo;

    public function __construct(DailyPriceRepositoryInterface $repo)
    {
        $this->repo = $repo;
    }

    public function index(Request $request)
    {
        $batches = $this->repo->paginate($request->all());
        return BatchResource::collection($batches);
    }

    public function show($batchId)
    {
        $batch = $this->repo->find($batchId);
        return new BatchResource($batch);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file',
            'price_date' => 'required|date',
        ]);

        return $this->repo->import($request->file('file'), $request->all());
    }

    public function export(Request $request, $batchId)
    {
        return $this->repo->export($batchId, $request->all());
    }

    public function getFilters()
    {
        $filters = $this->repo->getFilters();
        return response()->json($filters);
    }

    public function getProducts(Request $request)
    {
        $products = $this->repo->getProducts($request->all());
        return ProductResource::collection($products);
    }

    public function getBatchProducts($batchId, Request $request)
    {
        $products = $this->repo->getBatchProducts($batchId, $request->all());
        return ProductResource::collection($products);
    }

    public function approve($batchId)
    {
        $batch = $this->repo->approve($batchId);
        return new BatchResource($batch);
    }

    public function reject($batchId)
    {
        $batch = $this->repo->reject($batchId);
        return new BatchResource($batch);
    }
}
