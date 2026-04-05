<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\HRM\Contracts\CompanyPolicyRepositoryInterface;
use Modules\HRM\Http\Requests\CompanyPolicyRequest;
use Modules\HRM\Http\Resources\CompanyPolicyResource;

class CompanyPolicyController extends Controller
{
    protected $repository;

    public function __construct(CompanyPolicyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        if ($request->hasAny(['page', 'per_page'])) {
            $policies = $this->repository->paginate($request->all());
            return CompanyPolicyResource::collection($policies);
        }
        $policies = $this->repository->all($request->all());
        return CompanyPolicyResource::collection($policies);
    }

    public function store(CompanyPolicyRequest $request)
    {
        $data = $request->validated();
        $policy = $this->repository->create($data);
        return new CompanyPolicyResource($policy);
    }

    public function show($id)
    {
        $policy = $this->repository->find($id);
        return new CompanyPolicyResource($policy);
    }

    public function update(CompanyPolicyRequest $request, $id)
    {
        $data = $request->validated();
        $policy = $this->repository->update($id, $data);
        return new CompanyPolicyResource($policy);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
