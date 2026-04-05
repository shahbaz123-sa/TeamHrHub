<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\StoreLoanOptionRequest;
use Modules\HRM\Http\Requests\UpdateLoanOptionRequest;
use Modules\HRM\Http\Resources\LoanOptionResource;
use Modules\HRM\Contracts\LoanOptionRepositoryInterface;

class LoanOptionController extends Controller
{
    protected $repository;

    public function __construct(LoanOptionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $loanOptions = $this->repository->getAll($request->all());
        return LoanOptionResource::collection($loanOptions);
    }

    public function store(StoreLoanOptionRequest $request)
    {
        $loanOption = $this->repository->create($request->validated());
        return new LoanOptionResource($loanOption);
    }

    public function show($id)
    {
        $loanOption = $this->repository->find($id);
        return new LoanOptionResource($loanOption);
    }

    public function update(UpdateLoanOptionRequest $request, $id)
    {
        $loanOption = $this->repository->update($id, $request->validated());
        return new LoanOptionResource($loanOption);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
