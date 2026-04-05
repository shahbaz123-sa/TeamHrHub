<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\CreditApplicationRepositoryInterface;
use Modules\CRM\Http\Resources\CreditApplicationResource;

class CreditApplicationController extends Controller
{
    protected $repository;

    public function __construct(CreditApplicationRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return CreditApplicationResource::collection($data);
    }

    public function show($id)
    {
        $creditApplication = $this->repository->find($id);
        return new CreditApplicationResource($creditApplication);
    }

    public function listFormSubmissions(Request $request)
    {
        $request->merge(['form_type' => 'credit_request']);

        return redirect()->route(
            'form-submissions.index',
            $request->all()
        );
    }

    public function findFormSubmissionById($id)
    {
        return redirect()->route(
            'form-submissions.show',
            ['form_submission' => $id]
        );
    }

    public function widgetStatusCounts()
    {
        return response()->json(['data' => $this->repository->getWidgetCounts()]);
    }

    public function getByCompanyId($companyId)
    {
        $applications = $this->repository->findByCompanyId($companyId);
        return CreditApplicationResource::collection($applications);
    }

    public function approve(Request $request, $id)
    {
        $data = $request->validate([
            'approved_credit_limit' => ['required', 'numeric', 'min:0'],
        ]);

        $creditApplication = $this->repository->approve((int) $id, $data['approved_credit_limit']);

        return new CreditApplicationResource($creditApplication);
    }

    public function reject(Request $request, $id)
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $creditApplication = $this->repository->reject((int) $id, $data['rejection_reason']);

        return new CreditApplicationResource($creditApplication);
    }

    public function bulkApprove($companyId)
    {
        $this->repository->bulkApprove((int) $companyId);
        return response()->json(['message' => 'All applications approved successfully']);
    }

    public function bulkReject($companyId)
    {
        $this->repository->bulkReject((int) $companyId);
        return response()->json(['message' => 'All applications rejected successfully']);
    }
}
