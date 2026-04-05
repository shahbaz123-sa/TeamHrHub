<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\RfqRepositoryInterface;
use Modules\CRM\Http\Requests\Rfq\QuotationRequest;
use Modules\CRM\Http\Resources\RfqResource;

class RfqController extends Controller
{
    protected $repository;

    public function __construct(RfqRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return RfqResource::collection($data);
    }

    public function listFormSubmissions(Request $request)
    {
        $request->merge(['form_type' => 'rfq']);

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

    public function show($id)
    {
        $rfq = $this->repository->find($id);
        return new RfqResource($rfq);
    }

    public function widgetStatusCounts()
    {
        return response()->json(['data' => $this->repository->getWidgetCounts()]);
    }

    public function assignManager(Request $request, $id)
    {
        $data = $request->validate([
            'assigned_to' => 'required|integer|exists:users,id',
        ]);

        $rfq = $this->repository->assignManager((int) $id, (int) $data['assigned_to']);

        return new RfqResource($rfq);
    }

    public function sendQuotation(QuotationRequest $request, $id)
    {
        $rfq = $this->repository->createQuotation((int) $id, $request->validated());
        return new RfqResource($rfq);
    }
}
