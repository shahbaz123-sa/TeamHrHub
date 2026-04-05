<?php

namespace Modules\CRM\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Report\FinancialReportRequest;
use Modules\CRM\Http\Resources\Report\FinancialReportResource;
use Modules\CRM\Contracts\Report\FinancialReportRepositoryInterface;

class FinancialReportController extends Controller
{
    protected $repository;

    public function __construct(FinancialReportRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $items = $this->repository->paginate($request->all());
        return FinancialReportResource::collection($items);
    }

    public function store(FinancialReportRequest $request)
    {
        $item = $this->repository->create($request->validated());
        return new FinancialReportResource($item);
    }

    public function show($id)
    {
        $item = $this->repository->find($id);
        return new FinancialReportResource($item);
    }

    public function update(FinancialReportRequest $request, $id)
    {
        $item = $this->repository->update($id, $request->validated());
        return new FinancialReportResource($item);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
