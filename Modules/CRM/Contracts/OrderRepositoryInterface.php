<?php

namespace Modules\CRM\Contracts;

interface OrderRepositoryInterface
{
    public function paginate();
    public function find(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    public function changeStatus(int $id, array $data);
    public function bulkChangeStatus(array $orderIds, string $targetStatus, $cancelReason = null);
    public function markPaymentReceived(int $id);
    public function bulkMarkPaymentReceived(array $orderIds);
    public function getWidgetCounts();
}
