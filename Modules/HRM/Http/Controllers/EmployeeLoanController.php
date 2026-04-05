<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\EmployeeLoanRequest;
use Modules\HRM\Http\Resources\EmployeeLoanResource;
use Modules\HRM\Contracts\EmployeeLoanRepositoryInterface;

class EmployeeLoanController extends Controller
{
    protected $repository;

    public function __construct(EmployeeLoanRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $employeeLoans = $this->repository->paginate($request->all());
        return EmployeeLoanResource::collection($employeeLoans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(EmployeeLoanRequest $request)
    {
        $employeeLoan = $this->repository->create($request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee loan created successfully',
            'data' => new EmployeeLoanResource($employeeLoan->load(['employee', 'loan']))
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $employeeLoan = $this->repository->find($id);
        return response()->json([
            'status' => 'success',
            'data' => new EmployeeLoanResource($employeeLoan)
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(EmployeeLoanRequest $request, $id)
    {
        $employeeLoan = $this->repository->update($id, $request->validated());
        return response()->json([
            'status' => 'success',
            'message' => 'Employee loan updated successfully',
            'data' => new EmployeeLoanResource($employeeLoan)
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
            'message' => 'Employee loan deleted successfully'
        ], 204);
    }

    /**
     * Get loans for a specific employee.
     */
    public function getByEmployee($employeeId)
    {
        $loans = $this->repository->getByEmployee($employeeId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeLoanResource::collection($loans)
        ]);
    }

    /**
     * Get employees for a specific loan.
     */
    public function getByLoan($loanId)
    {
        $employees = $this->repository->getByLoan($loanId);
        return response()->json([
            'status' => 'success',
            'data' => EmployeeLoanResource::collection($employees)
        ]);
    }
}
