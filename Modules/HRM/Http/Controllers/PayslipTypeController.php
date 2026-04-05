<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\StorePayslipTypeRequest;
use Modules\HRM\Http\Requests\UpdatePayslipTypeRequest;
use Modules\HRM\Http\Resources\PayslipTypeResource;
use Modules\HRM\Contracts\PayslipTypeRepositoryInterface;

class PayslipTypeController extends Controller
{
    protected $repository;

    public function __construct(PayslipTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $payslipTypes = $this->repository->getAll($request->all());
        return PayslipTypeResource::collection($payslipTypes);
    }

    public function store(StorePayslipTypeRequest $request)
    {
        $payslipType = $this->repository->create($request->validated());
        return new PayslipTypeResource($payslipType);
    }

    public function show($id)
    {
        $payslipType = $this->repository->find($id);
        return new PayslipTypeResource($payslipType);
    }

    public function update(UpdatePayslipTypeRequest $request, $id)
    {
        $payslipType = $this->repository->update($id, $request->validated());
        return new PayslipTypeResource($payslipType);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
