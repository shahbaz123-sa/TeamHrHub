<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\EmployeeAllowanceRequest;
use Modules\HRM\Http\Resources\EmployeeAllowanceResource;
use Modules\HRM\Contracts\EmployeeAllowanceRepositoryInterface;

class EmployeeAllowanceController extends Controller
{
    protected $repository;

    public function __construct(EmployeeAllowanceRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $employeeAllowances = $this->repository->paginate($request->all());
        return EmployeeAllowanceResource::collection($employeeAllowances);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeAllowanceRequest $request)
    {
        $employeeAllowance = $this->repository->create($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee allowance created successfully',
            'data' => new EmployeeAllowanceResource($employeeAllowance->load(['employee', 'allowance']))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employeeAllowance = $this->repository->find($id);
        return response()->json([
            'status' => 'success',
            'data' => new EmployeeAllowanceResource($employeeAllowance)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeAllowanceRequest $request, $id)
    {
        $employeeAllowance = $this->repository->update($id, $request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee allowance updated successfully',
            'data' => new EmployeeAllowanceResource($employeeAllowance)
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
            'message' => 'Employee allowance deleted successfully'
        ]);
    }

    /**
     * Get allowances for a specific employee.
     */
    public function getByEmployee($employeeId)
    {
        $allowances = $this->repository->getByEmployee($employeeId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeAllowanceResource::collection($allowances)
        ]);
    }

    /**
     * Get employees for a specific allowance.
     */
    public function getByAllowance($allowanceId)
    {
        $employees = $this->repository->getByAllowance($allowanceId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeAllowanceResource::collection($employees)
        ]);
    }
}
