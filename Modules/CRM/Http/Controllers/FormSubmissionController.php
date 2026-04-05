<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\FormSubmissionRepositoryInterface;
use Modules\CRM\Http\Resources\FormSubmissionResource;

class FormSubmissionController extends Controller
{
    protected $repository;

    public function __construct(FormSubmissionRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $data = $this->repository->paginate($request->all());
        return FormSubmissionResource::collection($data);
    }

    public function show($id)
    {
        $rfq = $this->repository->find($id);
        return new FormSubmissionResource($rfq);
    }
}
