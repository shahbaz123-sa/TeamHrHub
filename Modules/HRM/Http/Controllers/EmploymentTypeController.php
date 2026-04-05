<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use Modules\HRM\Http\Resources\EmploymentTypeResource;
use Modules\HRM\Contracts\EmploymentTypeRepositoryInterface;

class EmploymentTypeController extends Controller
{
    protected $repository;

    public function __construct(EmploymentTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    public function index()
    {
        return EmploymentTypeResource::collection($this->repository->all());
    }
    public function show($id)
    {
        return new EmploymentTypeResource($this->repository->find($id));
    }
    public function store(Request $request)
    {
        $type = $this->repository->create($request->all());
        return new EmploymentTypeResource($type);
    }
    public function update(Request $request, $id)
    {
        $type = $this->repository->update($id, $request->all());
        return new EmploymentTypeResource($type);
    }
    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->json(['message' => 'Deleted']);
    }
}
