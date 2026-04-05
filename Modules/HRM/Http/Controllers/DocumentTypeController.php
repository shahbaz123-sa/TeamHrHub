<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\StoreDocumentTypeRequest;
use Modules\HRM\Http\Requests\UpdateDocumentTypeRequest;
use Modules\HRM\Http\Resources\DocumentTypeResource;
use Modules\HRM\Contracts\DocumentTypeRepositoryInterface;

class DocumentTypeController extends Controller
{
    protected $repository;

    public function __construct(DocumentTypeRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $documentTypes = $this->repository->getAll($request->all());
        return DocumentTypeResource::collection($documentTypes);
    }

    public function store(StoreDocumentTypeRequest $request)
    {
        $documentType = $this->repository->create($request->validated());
        return new DocumentTypeResource($documentType);
    }

    public function show($id)
    {
        $documentType = $this->repository->find($id);
        return new DocumentTypeResource($documentType);
    }

    public function update(UpdateDocumentTypeRequest $request, $id)
    {
        $documentType = $this->repository->update($id, $request->validated());
        return new DocumentTypeResource($documentType);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
