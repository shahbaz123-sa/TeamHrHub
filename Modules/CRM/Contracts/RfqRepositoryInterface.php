<?php

namespace Modules\CRM\Contracts;

interface RfqRepositoryInterface
{
    public function paginate(array $filters = []);
    public function find(int $id);
    public function getWidgetCounts();
    public function assignManager(int $id, int $userId);
    public function createQuotation(int $id, array $data);
}
