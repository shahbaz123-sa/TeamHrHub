<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\CustomerRepositoryInterface;
use Modules\CRM\Http\Requests\CustomerRequest;
use Modules\CRM\Http\Resources\CustomerResource;
use Modules\CRM\Http\Requests\CompanyDocumentRequest;

class CustomerController extends Controller
{
    protected $repository;

    public function __construct(CustomerRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $categories = $this->repository->paginate($request->all());
        return CustomerResource::collection($categories);
    }

    public function store(CustomerRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new CustomerResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new CustomerResource($category);
    }

    public function update(CustomerRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new CustomerResource($category);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|string|in:PENDING,COMPLETED'
        ]);

        $customer = $this->repository->updateStatus($id, $data['status']);
        return new CustomerResource($customer);
    }

    public function widgetStatusCounts()
    {
        return response()->json(['data' => $this->repository->getWidgetCounts()]);
    }

    public function updateCompanyStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|string|in:PENDING,APPROVED,REJECTED'
        ]);

        $customer = $this->repository->updateCompanyStatus($id, $data['status']);
        return new CustomerResource($customer);
    }

    public function uploadCompanyDocument(CompanyDocumentRequest $request, $id)
    {
        $file = $request->file('document');
        $type = $request->get('document_type');

        $doc = $this->repository->uploadCompanyDocument($id, $file, $type);

        return response()->json(['data' => $doc], 201);
    }

    public function deleteCompanyDocument(Request $request, $id, $docId)
    {
        $this->repository->deleteCompanyDocument($id, (int) $docId);
        return response()->noContent();
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
