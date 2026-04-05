<?php

namespace Modules\HRM\Http\Controllers;

use Modules\HRM\Models\ExpenseType;
use App\Http\Controllers\Controller;

use Modules\HRM\Http\Requests\StoreExpenseTypeRequest;
use Modules\HRM\Http\Requests\UpdateExpenseTypeRequest;
use Modules\HRM\Http\Resources\ExpenseTypeResource;
use Modules\HRM\Repositories\ExpenseTypeRepository;

class ExpenseTypeController extends Controller
{
    public function index(ExpenseTypeRepository $repo)
    {
        return ExpenseTypeResource::collection($repo->all());
    }

    public function store(StoreExpenseTypeRequest $request, ExpenseTypeRepository $repo)
    {
        return new ExpenseTypeResource($repo->create($request->validated()));
    }

    public function show($id, ExpenseTypeRepository $repo)
    {
        return new ExpenseTypeResource($repo->find($id));
    }

    public function update(UpdateExpenseTypeRequest $request, $id, ExpenseTypeRepository $repo)
    {
        return new ExpenseTypeResource($repo->update($id, $request->validated()));
    }

    public function destroy($id, ExpenseTypeRepository $repo)
    {
        $repo->delete($id);
        return response()->noContent();
    }
}
