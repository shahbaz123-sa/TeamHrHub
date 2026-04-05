<?php

namespace Modules\CRM\Http\Controllers\Product;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Exception;
use Maatwebsite\Excel\Facades\Excel;
use Modules\CRM\Imports\Product\GraphPricesImport;
use Modules\CRM\Http\Requests\Product\GraphPriceRequest;
use Modules\CRM\Http\Resources\Product\GraphPriceResource;
use Modules\CRM\Contracts\Product\GraphPriceRepositoryInterface;

class GraphPriceController extends Controller
{
    protected $repository;

    public function __construct(GraphPriceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $items = $this->repository->paginate($request->all());
        return GraphPriceResource::collection($items);
    }

    public function getFilters(Request $request)
    {
        return $this->repository->getFilters();
    }

    public function store(GraphPriceRequest $request)
    {
        $item = $this->repository->create($request->validated());
        return new GraphPriceResource($item);
    }

    public function show($id)
    {
        $item = $this->repository->find($id);
        return new GraphPriceResource($item);
    }

    public function update(GraphPriceRequest $request, $id)
    {
        $item = $this->repository->update($id, $request->validated());
        return new GraphPriceResource($item);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }

    public function generateAnalytics(Request $request)
    {
        try {
            return $this->repository->generateAnalytics($request->all());
        } catch (Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function fetchDailyPrices(Request $request)
    {
        try {
            return $this->repository->fetchDailyPrices($request->all());
        } catch (Exception $e) {
            return [
                'status' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $uploadedBy = optional($request->user())->id ?: $request->input('uploaded_by');

        $importer = new GraphPricesImport($uploadedBy);

        Excel::import($importer, $request->file('file'));

        return response()->json([
            'message' => 'Import completed',
            'report' => $importer->getReport()
        ]);
    }
}
