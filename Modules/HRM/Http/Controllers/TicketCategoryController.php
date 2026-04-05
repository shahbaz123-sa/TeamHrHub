<?php

namespace Modules\HRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\HRM\Contracts\TicketCategoryRepositoryInterface;
use Modules\HRM\Http\Requests\TicketCategoryRequest;
use Modules\HRM\Http\Resources\TicketCategoryResource;

class TicketCategoryController extends Controller
{
    protected $repository;

    public function __construct(TicketCategoryRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index()
    {
        $categories = $this->repository->getAll();
        return TicketCategoryResource::collection($categories);
    }

    public function store(TicketCategoryRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new TicketCategoryResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new TicketCategoryResource($category);
    }

    public function update(TicketCategoryRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new TicketCategoryResource($category);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}