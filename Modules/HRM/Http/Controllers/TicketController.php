<?php

namespace Modules\HRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\HRM\Http\Requests\TicketRequest;
use Modules\HRM\Http\Resources\TicketResource;
use Modules\HRM\Contracts\TicketRepositoryInterface;

class TicketController extends Controller
{
    protected $repository;

    public function __construct(TicketRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $tickets = $this->repository->paginate($request->all());
        return TicketResource::collection($tickets);
    }

    public function store(TicketRequest $request)
    {
        $ticket = $this->repository->create($request->validated());
        return new TicketResource($ticket);
    }

    public function show($id)
    {
        $ticket = $this->repository->find($id);
        return new TicketResource($ticket);
    }

    public function update(TicketRequest $request, $id)
    {
        $ticket = $this->repository->update($id, $request->validated());
        return new TicketResource($ticket);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
