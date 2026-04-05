<?php

namespace Modules\CRM\Repositories;

use Exception;
use Modules\CRM\Models\Order;
use Modules\CRM\Traits\File\FileManager;
use Illuminate\Support\Facades\Notification;
use Modules\CRM\Contracts\OrderRepositoryInterface;
use Modules\CRM\Notifications\Order\StatusUpdatedNotification;

class OrderRepository implements OrderRepositoryInterface
{
  use FileManager;

  protected $model;

  public function __construct(Order $model)
  {
    $this->model = $model;
  }

  public function paginate(array $filters = [])
  {
    return $this->model
      ->with('customer')
      ->when(isset($filters['q']), function ($query) use ($filters) {
        $query->whereAny(['id', 'status', 'payment_method', 'payment_status', 'unique_order_id'], 'ilike', '%' . $filters['q'] . '%');
      })
      ->when(isset($filters['customer_type']), function ($query) use ($filters) {
        $query->where('order_type', $filters['customer_type']);
      })
      ->when(isset($filters['method']), function ($query) use ($filters) {
        $query->where('payment_method', $filters['method']);
      })
      ->when(isset($filters['payment_status']), function ($query) use ($filters) {
        $query->where('payment_status', $filters['payment_status']);
      })
      ->when(isset($filters['status']), function ($query) use ($filters) {
        $query->where('status', $filters['status']);
      })
      ->when(isset($filters['customer_id']), function ($query) use ($filters) {
        $query->where('user_id', $filters['customer_id']);
      })
      ->when(isset($filters['start_date']), function ($query) use ($filters) {
        $query->whereDate('createdAt', '>=', $filters['start_date']);
      })
      ->when(isset($filters['end_date']), function ($query) use ($filters) {
        $query->whereDate('createdAt', '<=', $filters['end_date']);
      })
      ->orderBy(
        data_get($filters, 'sort_by', 'id'),
        data_get($filters, 'order', 'desc')
      )
      ->paginate($filters['per_page'] ?? 10);
  }

  public function find(int $id)
  {
    return $this->model
      ->with(['customer', 'rfq:id,reference_no', 'items.product', 'items.variation', 'histories', 'documents'])
      ->findOrFail($id);
  }

  public function create(array $data)
  {
    // 
  }

  public function update(int $id, array $data)
  {
    // 
  }

  public function delete(int $id)
  {
    // 
  }

  public function changeStatus(int $id, array $data)
  {
    $allowed = [
      'pending' => ['awaiting_payment', 'cancelled'],
      'awaiting_payment' => ['processing', 'refunded', 'cancelled'],
      'processing' => ['completed', 'cancelled', 'refunded'],
    ];

    $order = $this->find($id);
    $current = $order->status;

    throw_if(
      in_array($current, ['completed', 'cancelled', 'refunded']),
      new Exception('Cannot change status for a finalized order', 422)
    );

    $target = $data['status'];
    $allowedTargets = $allowed[$current] ?? [];

    // Allow direct transition from pending -> processing for COD orders
    $isCodDirectAllowed = ($current === 'pending' && $target === 'processing' && isset($order->payment_method) && strtolower($order->payment_method) === 'cod');

    throw_if(
      !in_array($target, $allowedTargets) && !$isCodDirectAllowed,
      new Exception('Invalid status transition', 422)
    );

    if ($target == 'refunded') {
      $order->payment_status = 'refunded';
    }

    // Save cancel reason if order is being cancelled
    if ($target === 'cancelled' && isset($data['cancel_reason'])) {
      $order->cancel_reason = $data['cancel_reason'];
    }

    $order->status = $target;
    $order->save();

    $order->histories()->create([
      'status' => $target,
    ]);

    $order = $order->fresh();

    Notification::route('webhook', null)->notify(new StatusUpdatedNotification($order));

    return $order;
  }

  public function bulkChangeStatus(array $orderIds, string $targetStatus, $cancelReason = null)
  {
    $allowed = [
      'pending' => ['awaiting_payment', 'cancelled'],
      'awaiting_payment' => ['processing', 'refunded', 'cancelled'],
      'processing' => ['completed', 'cancelled', 'refunded'],
    ];

    $results = [
      'updated' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    foreach ($orderIds as $orderId) {
      try {
        $order = $this->model->findOrFail($orderId);
        $current = $order->status;

        if (in_array($current, ['completed', 'cancelled', 'refunded'])) {
          $results['skipped']++;
          continue;
        }

        $allowedTargets = $allowed[$current] ?? [];
        // Allow pending -> processing for COD orders in bulk
        $isCodDirectAllowedBulk = ($current === 'pending' && $targetStatus === 'processing' && isset($order->payment_method) && strtolower($order->payment_method) === 'cod');
        if (!in_array($targetStatus, $allowedTargets) && !$isCodDirectAllowedBulk) {
          $results['skipped']++;
          continue;
        }

        if ($targetStatus == 'refunded') {
          $order->payment_status = 'refunded';
        }

        // Save cancel reason if cancelling
        if ($targetStatus === 'cancelled' && $cancelReason) {
          $order->cancel_reason = $cancelReason;
        }
        $order->status = $targetStatus;
        $order->save();

        $order->histories()->create([
          'status' => $targetStatus,
        ]);

        $order = $order->fresh();

        $results['updated']++;
      } catch (Exception $e) {
        $results['errors'][] = [
          'order_id' => $orderId,
          'message' => $e->getMessage(),
        ];
      }
    }

    return $results;
  }

  public function bulkMarkPaymentReceived(array $orderIds)
  {
    $results = [
      'updated' => 0,
      'skipped' => 0,
      'errors' => [],
    ];

    foreach ($orderIds as $orderId) {
      try {
        $order = $this->model->findOrFail($orderId);

        // if (in_array($order->status, ['completed', 'cancelled', 'refunded'])) {
        //   $results['skipped']++;
        //   continue;
        // }

        if ($order->payment_status === 'paid') {
          $results['skipped']++;
          continue;
        }

        $order->payment_status = 'paid';
        $order->save();

        $results['updated']++;
      } catch (Exception $e) {
        $results['errors'][] = [
          'order_id' => $orderId,
          'message' => $e->getMessage(),
        ];
      }
    }

    return $results;
  }

  public function markPaymentReceived(int $id)
  {
    $order = $this->find($id);

    // throw_if(
    //   in_array($order->status, ['completed', 'cancelled', 'refunded']),
    //   new Exception('Cannot update payment for a finalized order', 422)
    // );

    $order->payment_status = 'paid';
    $order->save();

    Notification::route('webhook', null)->notify(new StatusUpdatedNotification($order));

    return $order->fresh();
  }

  public function getWidgetCounts()
  {
    $pendingPayment = $this->model->clone()->where('status', 'awaiting_payment')->count();
    $completed = $this->model->clone()->where('status', 'completed')->count();
    $refunded = $this->model->clone()->where('status', 'refunded')->count();
    $failed = $this->model->clone()->where('payment_status', 'failed')->count();

    return [
      'pending_payment' => $pendingPayment,
      'completed' => $completed,
      'refunded' => $refunded,
      'failed' => $failed,
    ];
  }
}
