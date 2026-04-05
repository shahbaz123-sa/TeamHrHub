<?php

namespace Modules\CRM\Contracts;

interface CreditApplicationRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
    public function findByCompanyId(int $companyId);
    public function approve(int $id, $approvedAmount);
    public function reject(int $id, $rejectionReason);
    public function bulkApprove(int $companyId);
    public function bulkReject(int $companyId);
    public function getWidgetCounts();
}
