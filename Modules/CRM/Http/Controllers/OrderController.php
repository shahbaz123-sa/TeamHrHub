<?php

namespace Modules\CRM\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Modules\CRM\Contracts\OrderRepositoryInterface;
use Modules\CRM\Http\Requests\OrderRequest;
use Modules\CRM\Http\Resources\OrderResource;

class OrderController extends Controller
{
    protected $repository;

    public function __construct(OrderRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function index(Request $request)
    {
        $categories = $this->repository->paginate($request->all());
        return OrderResource::collection($categories);
    }

    public function store(OrderRequest $request)
    {
        $category = $this->repository->create($request->validated());
        return new OrderResource($category);
    }

    public function show($id)
    {
        $category = $this->repository->find($id);
        return new OrderResource($category);
    }

    public function update(OrderRequest $request, $id)
    {
        $category = $this->repository->update($id, $request->validated());
        return new OrderResource($category);
    }

    public function changeStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|string',
            'cancel_reason' => 'nullable|string',
        ]);

        return new OrderResource($this->repository->changeStatus($id, $data));
    }

    public function markPaymentReceived(Request $request, $id)
    {
        $order = $this->repository->markPaymentReceived($id);
        return new OrderResource($order);
    }

    public function bulkChangeStatus(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|min:1',
            'status' => 'required|string',
            'cancel_reason' => 'nullable|string',
        ]);

        $results = $this->repository->bulkChangeStatus(
            $data['order_ids'],
            $data['status'],
            data_get($data, 'cancel_reason')
        );

        return response()->json([
            'message' => 'Orders updated successfully',
            'data' => $results,
        ]);
    }

    public function bulkMarkPaymentReceived(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'integer|min:1',
        ]);

        $results = $this->repository->bulkMarkPaymentReceived($data['order_ids']);

        return response()->json([
            'message' => 'Orders payment status updated',
            'data' => $results,
        ]);
    }

    public function widgetStatusCounts(Request $request)
    {
        $data = $this->repository->getWidgetCounts();
        return response()->json($data);
    }

    public function destroy($id)
    {
        $this->repository->delete($id);
        return response()->noContent();
    }
}
