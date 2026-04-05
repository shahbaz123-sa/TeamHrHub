<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Http\Requests\EmailSettingRequest;
use Modules\CRM\Http\Resources\EmailSettingResource;
use Modules\CRM\Contracts\EmailSettingRepositoryInterface;

class EmailSettingController extends Controller
{
    protected $repository;

    public function __construct(EmailSettingRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $emailSettings = $this->repository->paginate($request->all());
        return EmailSettingResource::collection($emailSettings);
    }

    public function store(EmailSettingRequest $request)
    {
        $emailSetting = $this->repository->create($request->validated());
        return new EmailSettingResource($emailSetting);
    }

    public function show($id)
    {
        $emailSetting = $this->repository->find($id);
        return new EmailSettingResource($emailSetting);
    }

    public function update(EmailSettingRequest $request, $id)
    {
        $emailSetting = $this->repository->update($id, $request->validated());
        return new EmailSettingResource($emailSetting);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
