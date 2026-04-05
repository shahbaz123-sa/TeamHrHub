<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\EmploymentStatusResource;
use Modules\HRM\Contracts\EmploymentStatusRepositoryInterface;

class EmploymentStatusController extends Controller
{
    protected $repository;

    public function __construct(EmploymentStatusRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    public function index()
    {
        return EmploymentStatusResource::collection($this->repository->all());
    }
    public function show($id)
    {
        return new EmploymentStatusResource($this->repository->find($id));
    }
    public function store(Request $request)
    {
        $status = $this->repository->create($request->all());
        return new EmploymentStatusResource($status);
    }
    public function update(Request $request, $id)
    {
        $status = $this->repository->update($id, $request->all());
        return new EmploymentStatusResource($status);
    }
    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted']);
    }
}
