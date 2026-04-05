<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\EmployeeDeductionRequest;
use Modules\HRM\Http\Resources\EmployeeDeductionResource;
use Modules\HRM\Contracts\EmployeeDeductionRepositoryInterface;

class EmployeeDeductionController extends Controller
{
    protected $repository;

    public function __construct(EmployeeDeductionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $employeeDeductions = $this->repository->paginate($request->all());
        return EmployeeDeductionResource::collection($employeeDeductions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeDeductionRequest $request)
    {
        $employeeDeduction = $this->repository->create($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee deduction created successfully',
            'data' => new EmployeeDeductionResource($employeeDeduction->load(['employee', 'deduction']))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employeeDeduction = $this->repository->find($id);
        return response()->json([
            'status' => 'success',
            'data' => new EmployeeDeductionResource($employeeDeduction)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeDeductionRequest $request, $id)
    {
        $employeeDeduction = $this->repository->update($id, $request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee deduction updated successfully',
            'data' => new EmployeeDeductionResource($employeeDeduction)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json([
            'status' => 'success',
            'message' => 'Employee deduction deleted successfully'
        ], 204);
    }

    /**
     * Get deductions for a specific employee.
     */
    public function getByEmployee($employeeId)
    {
        $deductions = $this->repository->getByEmployee($employeeId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeDeductionResource::collection($deductions)
        ]);
    }

    /**
     * Get employees for a specific deduction.
     */
    public function getByDeduction($deductionId)
    {
        $employees = $this->repository->getByDeduction($deductionId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeDeductionResource::collection($employees)
        ]);
    }
}
