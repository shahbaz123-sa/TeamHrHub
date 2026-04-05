<?php

namespace Modules\HRM\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HRM\Http\Resources\Employee\EmployeeDashboardResource;
use Modules\HRM\Repositories\Employee\EmployeeDashboardRepository;

class EmployeeDashboardController extends Controller
{
    protected $repository;

    public function __construct(EmployeeDashboardRepository $repository)
    {
        $this->repository = $repository;
    }

    public function __invoke(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);
            
            // Check if user is authenticated
            $user = auth()->user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Check if user has employee relationship
            if (!$user->employee) {
                return response()->json(['error' => 'No employee record found for user'], 404);
            }

            $employeeId = $user->employee->id;

            $dashboardData = $this->repository->getEmployeeDashboardData($employeeId, $month, $year);
            
            return new EmployeeDashboardResource($dashboardData);
        } catch (\Exception $e) {
            \Log::error('Employee Dashboard API Error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
