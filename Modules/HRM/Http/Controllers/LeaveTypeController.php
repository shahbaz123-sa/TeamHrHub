<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\HRM\Http\Requests\StoreLeaveTypeRequest;
use Modules\HRM\Http\Requests\UpdateLeaveTypeRequest;
use Modules\HRM\Http\Resources\LeaveTypeResource;
use Modules\HRM\Repositories\LeaveTypeRepository;
use Modules\HRM\Models\Employee;

class LeaveTypeController extends Controller
{
    public function index(LeaveTypeRepository $repo)
    {
        $types = $repo->all();
        $employeeId = auth()->user()->employee?->id ?? null;
        $gender = null;
        if ($employeeId) {
            $emp = Employee::find($employeeId);
            if ($emp && $emp->gender) {
                $gender = mb_strtolower($emp->gender);
            }
        }
        if ($gender) {
            $types = $types->filter(function ($t) use ($gender) {
                $name = mb_strtolower($t->name ?? '');
                if (str_contains($name, 'maternity')) return $gender === 'female';
                if (str_contains($name, 'paternity')) return $gender === 'male';
                return true;
            })->values();
        }
        return LeaveTypeResource::collection($types);
    }

    public function store(StoreLeaveTypeRequest $request, LeaveTypeRepository $repo)
    {
        return new LeaveTypeResource($repo->create($request->validated()));
    }

    public function show($id, LeaveTypeRepository $repo)
    {
        return new LeaveTypeResource($repo->find($id));
    }

    public function update(UpdateLeaveTypeRequest $request, $id, LeaveTypeRepository $repo)
    {
        return new LeaveTypeResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, LeaveTypeRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }

    public function reorder(Request $request, $id, LeaveTypeRepository $repo)
    {
        $request->validate([
            'direction' => 'required|in:up,down',
        ]);

        // AuthZ: reuse update permission requirement.
        $user = $request->user();
        abort_unless($user && $user->can('leave_type.update'), 403);

        $updated = $repo->move((int) $id, $request->string('direction')->toString());

        return new LeaveTypeResource($updated);
    }
}
