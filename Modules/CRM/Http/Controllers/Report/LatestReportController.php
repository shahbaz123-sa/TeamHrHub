<?php

namespace Modules\CRM\Http\Controllers\Report;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\Report\LatestReportRequest;
use Modules\CRM\Http\Resources\Report\LatestReportResource;
use Modules\CRM\Contracts\Report\LatestReportRepositoryInterface;

class LatestReportController extends Controller
{
    protected $repository;

    public function __construct(LatestReportRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $items = $this->repository->paginate($request->all());
        return LatestReportResource::collection($items);
    }

    public function store(LatestReportRequest $request)
    {
        $item = $this->repository->create($request->validated());
        return new LatestReportResource($item);
    }

    public function show($id)
    {
        $item = $this->repository->find($id);
        return new LatestReportResource($item);
    }

    public function update(LatestReportRequest $request, $id)
    {
        $item = $this->repository->update($id, $request->validated());
        return new LatestReportResource($item);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
